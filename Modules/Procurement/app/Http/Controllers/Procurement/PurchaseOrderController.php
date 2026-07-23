<?php

namespace Modules\Procurement\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Models\Warehouse;

class PurchaseOrderController extends Controller
{
    private function table(string $name)
    {
        $query = DB::connection('procurement')->table($name);

        if (! (config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin')) {
            $query->where($name.'.client_id', (int) session('employee_client_id'));
        }

        return $query;
    }

    /**
     * Load each PO's item rows (added when multi-item PO support was
     * introduced), grouped by purchase_order_id, in the JSON shape the
     * frontend chips/cascading selects expect: {name, qty, unitPrice}.
     */
    private function itemsGroupedByPurchaseOrder($purchaseOrderIds)
    {
        if (empty($purchaseOrderIds)) {
            return collect();
        }

        return DB::connection('procurement')->table('purchase_order_items')
            ->whereIn('purchase_order_id', $purchaseOrderIds)
            ->orderBy('id')
            ->get()
            ->groupBy('purchase_order_id')
            ->map(function ($rows) {
                return $rows->map(function ($row) {
                    return [
                        'name' => $row->name,
                        'qty' => (int) $row->qty,
                        'unitPrice' => (float) $row->unit_price,
                    ];
                })->values();
            });
    }

    /**
     * Detect a unique-constraint violation (e.g. a duplicate po_number),
     * regardless of which database driver raised it.
     */
    private function isDuplicateKeyException(\Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'duplicate key')
            || str_contains($message, 'Unique violation')
            || str_contains($message, 'SQLSTATE[23505]')
            || str_contains($message, 'UNIQUE constraint failed');
    }

    /**
     * Insert the purchase order, automatically regenerating the po_number
     * if it collides with one that already exists. This is what was making
     * "Submit for Approval" silently fail: the browser pre-fills the PO
     * number from an in-memory counter that resets on every page load, so
     * once real PO numbers passed that counter, every new submission hit
     * a duplicate po_number and the insert was rejected by the database.
     */
    private function insertPurchaseOrder(array $insert): int
    {
        $attempts = 0;
        $currentInsert = $insert;

        while ($attempts < 3) {
            try {
                return DB::connection('procurement')->table('purchase_orders')->insertGetId($currentInsert);
            } catch (\Throwable $e) {
                if ($this->isDuplicateKeyException($e)) {
                    $suffix = now()->format('YmdHis') . '-' . random_int(1000, 9999);
                    $currentInsert['po_number'] = $insert['po_number'] . '-' . $suffix;
                    $attempts++;
                    continue;
                }

                throw $e;
            }
        }

        throw new \RuntimeException('Unable to save purchase order after retrying.');
    }

    /**
     * Purchase Orders list page (filters, sortable table, add PO modal).
     */
    public function index(Request $request)
    {
        $purchaseOrders = $this->table('purchase_orders')
            ->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->select('purchase_orders.*', 'suppliers.name as supplier_name')
            ->orderBy('purchase_orders.created_at', 'desc')
            ->limit(8)
            ->get();

        // Item breakdown per PO (name/qty/price), used for the View modal's
        // item chips — same "products as chips" pattern used on Suppliers.
        $poItemsByOrder = $this->itemsGroupedByPurchaseOrder($purchaseOrders->pluck('id')->all());

        $suppliers = $this->table('suppliers')
            ->orderBy('created_at', 'desc')
            ->get();

        $warehouses = Warehouse::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'address']);

        $statusCounts = $this->table('purchase_orders')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->mapWithKeys(function ($total, $status) {
                return [strtolower(str_replace([' ', '_'], '-', $status ?? 'pending')) => $total];
            });

        return view('procurement::pages.purchase-orders', compact('purchaseOrders', 'poItemsByOrder', 'suppliers', 'warehouses', 'statusCounts'));
    }

    public function approved(Request $request)
    {
        $approvedPurchaseOrders = $this->table('purchase_orders')
            ->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->select('purchase_orders.*', 'suppliers.name as supplier_name')
            ->where('purchase_orders.status', 'approved')
            ->orderBy('purchase_orders.order_date', 'desc')
            ->get();

        // Attach each PO's item breakdown so the Log Delivery modal can show
        // the purchased items as chips instead of a free-text Item field.
        $itemsByOrder = $this->itemsGroupedByPurchaseOrder($approvedPurchaseOrders->pluck('id')->all());
        $approvedPurchaseOrders->each(function ($po) use ($itemsByOrder) {
            $po->items = $itemsByOrder->get($po->id, collect())->values();
        });

        return response()->json($approvedPurchaseOrders);
    }

    /**
     * Handle the "+ New PO" modal submit (submitAddPO in app-forms.js).
     *
     * A PO can now carry multiple item rows (supplier -> category -> item
     * cascading select per row in the modal). `items` is a JSON-encoded array
     * of {category, name, qty, unitPrice, amount}; the legacy single-item
     * columns (item, brand, unit_price, qty) are kept in sync from that array
     * so every place that still reads them (tables, filters, dashboard "Spend
     * by Brand") keeps working: `item` becomes a joined item-name summary,
     * `brand` holds the first row's category, `qty` is the summed quantity,
     * and `amount` is the summed total.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'po' => 'required|string|max:50',
            'supplier' => 'required|string|max:150',
            'category' => 'nullable|string|max:100',
            'items' => 'required|string',
            'priority' => 'nullable|string|max:20',
            'expected' => 'nullable|date',
            'createdBy' => 'nullable|string|max:150',
            'remarks' => 'nullable|string',
            'reqRef' => 'nullable|string|max:50',
            'warehouse_id' => 'nullable|integer',
        ]);

        $items = $this->sanitizeItemRows($validated['items']);
        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one item with a category, item, and quantity.',
            ]);
        }

        $totalQty = (int) array_sum(array_column($items, 'qty'));
        $totalAmount = (float) array_sum(array_column($items, 'amount'));
        $primaryCategory = $validated['category'] ?? ($items[0]['category'] ?? '');
        $itemSummary = implode(', ', array_column($items, 'name'));

        // Warehouse is optional (the module matches nexora, which chooses the
        // warehouse on the delivery, not the PO). Only look one up when a
        // warehouse_id was actually provided.
        $warehouse = null;
        if (! empty($validated['warehouse_id'])) {
            $warehouse = Warehouse::query()
                ->whereKey((int) $validated['warehouse_id'])
                ->where('status', 'active')
                ->first();
        }

        $supplier = $this->table('suppliers')->where('name', $validated['supplier'])->first();
        $supplierId = $supplier?->id;

        if (! $supplierId) {
            $supplierId = DB::connection('procurement')->table('suppliers')->insertGetId([
                'client_id' => (int) session('employee_client_id'),
                'name' => $validated['supplier'],
                'contact_person' => 'Auto-imported',
                'email' => 'auto@example.com',
                'phone' => 'N/A',
                'address' => 'Auto-imported',
                'brand' => $primaryCategory ?: null,
                'status' => 'active',
                'product_items' => '[]',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $insert = [
            'client_id' => (int) session('employee_client_id'),
            'po_number' => $validated['po'],
            'supplier_id' => $supplierId,
            'qty' => $totalQty,
            'amount' => $totalAmount,
            'status' => 'pending',
            'priority' => strtolower($validated['priority'] ?? 'normal'),
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => $validated['expected'] ?? null,
            'created_by' => $validated['createdBy'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'item' => $itemSummary,
            'brand' => $primaryCategory ?: null,
            'unit_price' => (float) ($items[0]['unit_price'] ?? 0),
            'requisition_reference' => $validated['reqRef'] ?? null,
            'warehouse_id' => $warehouse?->id,
            'delivery_address' => $warehouse?->address,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $poId = $this->insertPurchaseOrder($insert);
        $savedPoNumber = $this->table('purchase_orders')->where('id', $poId)->value('po_number');

        foreach ($items as $item) {
            // Best-effort link back to the supplier's catalog row (for
            // reporting); the PO item row is still fully self-contained
            // (name/qty/price) if no match is found.
            $supplierProductId = DB::connection('procurement')->table('supplier_products')
                ->where('supplier_id', $supplierId)
                ->where('name', $item['name'])
                ->value('id');

            DB::connection('procurement')->table('purchase_order_items')->insert([
                'client_id' => (int) session('employee_client_id'),
                'purchase_order_id' => $poId,
                'supplier_product_id' => $supplierProductId,
                'name' => $item['name'],
                'qty' => $item['qty'],
                'unit_price' => $item['unit_price'],
                'amount' => $item['amount'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $validated['po'] = $savedPoNumber;
        $validated['item'] = $itemSummary;
        $validated['qty'] = $totalQty;
        $validated['amount'] = $totalAmount;
        $validated['category'] = $primaryCategory;
        $validated['items'] = $items;

        return response()->json(['status' => 'ok', 'data' => $validated, 'id' => $poId, 'po_number' => $savedPoNumber]);
    }

    /**
     * Decode + validate the item-rows JSON from the PO modal: drops any row
     * missing a name or a positive quantity, and recomputes each row's amount
     * server-side (never trusts the client's math).
     */
    private function sanitizeItemRows(string $itemsJson): array
    {
        $decoded = json_decode($itemsJson, true);
        if (! is_array($decoded)) {
            return [];
        }

        $rows = [];
        foreach ($decoded as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $qty = (int) ($row['qty'] ?? 0);
            if ($name === '' || $qty <= 0) {
                continue;
            }

            $unitPrice = (float) ($row['unitPrice'] ?? 0);
            $rows[] = [
                'category' => trim((string) ($row['category'] ?? '')),
                'name' => $name,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'amount' => round($qty * $unitPrice, 2),
            ];
        }

        return $rows;
    }

    public function update(Request $request, $purchaseOrder)
    {
        $validated = $request->validate([
            'status' => 'nullable|string|max:20',
            'amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $status = $validated['status'] ?? null;
        if ($status !== null) {
            $status = strtolower(trim($status));
            $allowed = ['pending', 'approved', 'rejected', 'cancelled', 'processing', 'completed'];
            if (!in_array($status, $allowed, true)) {
                $status = null;
            }
        }

        $purchaseOrderQuery = $this->table('purchase_orders')->where('id', $purchaseOrder);

        if (! $purchaseOrderQuery->exists()) {
            abort(404, 'Purchase order not found for this client.');
        }

        $purchaseOrderQuery->update([
            'status' => $status ?? DB::raw('status'),
            'amount' => $validated['amount'] ?? DB::raw('amount'),
            'remarks' => $validated['remarks'] ?? DB::raw('remarks'),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'ok', 'purchase_order_id' => (int) $purchaseOrder]);
    }

    public function destroy($purchaseOrder)
    {
        $this->table('purchase_orders')->where('id', $purchaseOrder)->delete();

        return response()->json(['status' => 'ok']);
    }
}
