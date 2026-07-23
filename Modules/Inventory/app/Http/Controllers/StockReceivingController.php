<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;

use Modules\Inventory\Models\Category;
use Modules\Inventory\Models\Procurement;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\StockLevel;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\StockReceiving;
use Modules\Inventory\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockReceivingController extends Controller
{
    private function procurementDeliveriesQuery()
    {
        $schema = Schema::connection('procurement');
        $hasPurchaseOrderWarehouse = $schema->hasColumn('purchase_orders', 'warehouse_id');
        $hasSupplierWarehouse = $schema->hasColumn('suppliers', 'warehouse_id');

        $destinationWarehouse = match (true) {
            $hasPurchaseOrderWarehouse && $hasSupplierWarehouse => DB::raw('COALESCE(purchase_orders.warehouse_id, suppliers.warehouse_id) as destination_warehouse_id'),
            $hasPurchaseOrderWarehouse => DB::raw('purchase_orders.warehouse_id as destination_warehouse_id'),
            $hasSupplierWarehouse => DB::raw('suppliers.warehouse_id as destination_warehouse_id'),
            default => DB::raw('NULL as destination_warehouse_id'),
        };

        $query = Procurement::query()
            ->leftJoin('suppliers', 'deliveries.supplier_id', '=', 'suppliers.id')
            ->leftJoin('purchase_orders', 'deliveries.purchase_order_id', '=', 'purchase_orders.id')
            ->select(
                'deliveries.*',
                'suppliers.name as supplier_name',
                $destinationWarehouse
            );

        if (! (config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin')) {
            $query->where('purchase_orders.client_id', (int) session('employee_client_id'));
        }

        return $query;
    }

    private function findDeliveryForCurrentClient(int $deliveryId): Procurement
    {
        return $this->procurementDeliveriesQuery()
            ->where('deliveries.id', $deliveryId)
            ->firstOrFail();
    }

    public function index(Request $request)
    {
        // Incoming stock is sourced from this client's Procurement deliveries.
        $baseQuery = $this->procurementDeliveriesQuery();
        $query = (clone $baseQuery)
            ->whereIn('deliveries.status', ['pending', 'intransit'])
            ->orderByDesc('deliveries.created_at');

        if ($search = $request->input('search')) {
            $search = strtolower($search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(deliveries.shipment_number) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(suppliers.name) LIKE ?', ["%{$search}%"]);
            });
        }

        if ($status = $request->input('status')) {
            $query->where('deliveries.status', $status);
        }

        $deliveries = $query->paginate(10)->appends($request->query());

        $processedShipments = StockReceiving::query()->pluck('shipment_number')->all();

        // For kpi cards
        $pendingCount = (clone $baseQuery)->whereIn('deliveries.status', ['pending', 'intransit'])->count();
        $receivedTodayCount = StockReceiving::whereDate('processed_at', today())
            ->where('status', 'approved')
            ->count();
        $rejectedCount = StockReceiving::where('status', 'rejected')->count();

        $deliveryProcessed = [];
        $warehouseNames = Warehouse::withTrashed()
            ->whereIn('id', $deliveries->pluck('destination_warehouse_id')->filter()->unique())
            ->pluck('name', 'id');

        $deliveries->getCollection()->transform(function ($delivery) use ($processedShipments, $warehouseNames) {
            $delivery->destination_warehouse_name = $warehouseNames[$delivery->destination_warehouse_id] ?? 'No warehouse assigned';
            $delivery->supplier_name = $delivery->supplier_name ?? 'Unknown supplier';

            return $delivery;
        });

        foreach ($deliveries as $delivery) {
            $deliveryProcessed[$delivery->id] = in_array($delivery->shipment_number, $processedShipments, true);
        }

        // Audit trail â€” past approved/rejected records
        $history = StockReceiving::with(['item', 'warehouse', 'processor'])
            ->orderByDesc('processed_at')
            ->limit(50)
            ->get();

        $historySuppliers = $this->procurementDeliveriesQuery()
            ->whereIn('deliveries.shipment_number', $history->pluck('shipment_number')->filter()->unique())
            ->pluck('suppliers.name', 'deliveries.shipment_number');

        return view('inventory::stock-receiving', [
            'deliveries' => $deliveries,
            'deliveryProcessed' => $deliveryProcessed,
            'pendingCount' => $pendingCount,
            'receivedTodayCount' => $receivedTodayCount,
            'rejectedCount' => $rejectedCount,
            'history' => $history,
            'historySuppliers' => $historySuppliers,
            'filters' => $request->only(['search', 'status']),
            'activePage' => 'stock-receiving',
        ]);
    }

    public function approve(Request $request, $deliveryId)
    {
        $delivery = $this->findDeliveryForCurrentClient((int) $deliveryId);
        $warehouse = Warehouse::query()
            ->whereKey($delivery->destination_warehouse_id)
            ->where('status', 'active')
            ->first();

        if (! $warehouse) {
            return back()->withErrors(["del_action_{$delivery->id}" => 'This purchase order has no active destination warehouse.']);
        }

        $result = $this->executeApproval($delivery, ['warehouse_id' => $warehouse->id]);

        if ($result === true) {
            return back()->with('success', 'Delivery approved and stock updated.');
        }

        return back()->withErrors(["del_action_{$delivery->id}" => $result]);
    }

    private function executeApproval(Procurement $delivery, array $validated): true|string
    {
        return DB::connection('inventory')->transaction(function () use ($delivery, $validated) {
            // Fetch procurement product data (sku, name, unit_price)
            $product = $delivery->getSupplierProduct();

            if (!$product) {
                return 'Could not fetch delivery from procurement.';
            }

            // Try to match existing item by SKU, or create new one
            $item = Item::where('sku', $product->sku)->first();

            if (!$item) {
                $defaultCategory = Category::query()->firstOrCreate([
                    'name' => 'Uncategorized Incoming Goods',
                ]);

                $item = Item::create([
                    'sku' => $product->sku,
                    'name' => $product->item_name,
                    'category_id' => $defaultCategory->id,
                    'unit_cost' => $product->unit_price,
                ]);
            }

            // Lock the stock level row FIRST â€” this is the serialization point.
            // Any concurrent request for the same item+warehouse will wait here.
            $stockLevel = StockLevel::where('item_id', $item->id)
                ->where('warehouse_id', $validated['warehouse_id'])
                ->lockForUpdate()
                ->first();

            if (!$stockLevel) {
                try {
                    $stockLevel = StockLevel::create([
                        'item_id' => $item->id,
                        'warehouse_id' => $validated['warehouse_id'],
                        'stock' => $delivery->qty,
                        'reorder_threshold' => 10,
                    ]);
                } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                    $stockLevel = StockLevel::where('item_id', $item->id)
                        ->where('warehouse_id', $validated['warehouse_id'])
                        ->lockForUpdate()
                        ->first();
                    $stockLevel->increment('stock', $delivery->qty);
                }
            } else {
                // NOW check if already processed â€” safe because we hold the exclusive lock.
                if (StockReceiving::where('shipment_number', $delivery->shipment_number)->where('item_id', $item->id)->exists()) {
                    return 'This delivery has already been processed.';
                }

                $stockLevel->increment('stock', $delivery->qty);
            }

            // Update warehouse activity
            Warehouse::where('id', $validated['warehouse_id'])
                ->update(['last_activity_at' => now()]);

            // Create stock movement record
            StockMovement::create([
                'type' => 'inbound',
                'item_id' => $item->id,
                'warehouse_id' => $validated['warehouse_id'],
                'quantity' => $delivery->qty,
                'reference' => $delivery->shipment_number,
                'notes' => "From delivery - Shipment: {$delivery->shipment_number}",
                'performed_by' => session('employee_id'),
                'created_at' => now(),
            ]);

            // Record the receiving
            StockReceiving::create([
                'shipment_number' => $delivery->shipment_number,
                'item_id' => $item->id,
                'warehouse_id' => $validated['warehouse_id'],
                'quantity' => $delivery->qty,
                'status' => 'approved',
                'processed_by' => session('employee_id'),
                'remarks' => $delivery->remarks,
                'processed_at' => now(),
            ]);

            // Update the procurement delivery status to delivered
            DB::connection('procurement')
                ->table('deliveries')
                ->where('id', $delivery->id)
                ->update(['status' => 'delivered']);

            return true;
        });
    }

    public function reject(Request $request, $deliveryId)
    {
        $validated = $request->validate([
            'reject_reason' => 'required|string',
        ]);

        $delivery = $this->findDeliveryForCurrentClient((int) $deliveryId);

        $result = DB::connection('inventory')->transaction(function () use ($delivery, $validated) {
            if (StockReceiving::where('shipment_number', $delivery->shipment_number)->where('status', 'rejected')->exists()) {
                return 'This delivery has already been processed.';
            }

            StockReceiving::create([
                'shipment_number' => $delivery->shipment_number,
                'item_id' => null,
                'warehouse_id' => null,
                'quantity' => $delivery->qty,
                'status' => 'rejected',
                'processed_by' => session('employee_id'),
                'remarks' => $validated['reject_reason'],
                'processed_at' => now(),
            ]);

            return true;
        });

        if ($result === true) {
            return back()->with('success', 'Delivery rejected.');
        }

        return back()->withErrors(["del_action_{$delivery->id}" => $result]);
    }
}
