<?php

namespace Modules\OrderFulfillment\Http\Controllers;

use Modules\OrderFulfillment\Helpers\OrderPriority;
use Modules\OrderFulfillment\Models\Order;
use Modules\OrderFulfillment\Models\PackingError;
use Modules\OrderFulfillment\Models\PackingMaterial;
use Modules\OrderFulfillment\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PackingController extends Controller
{

    private const INVENTORY_CONN = 'inventory';

    public function index()
    {
        $packingOrders = Order::where('status', 'PACKING')
            ->when(Schema::hasTable('order_items'), fn ($q) => $q->with('items'))
            ->get();

        $inPackingCount = $packingOrders->count();
        $ShippedCount   = Order::where('status', 'SHIPPED')->count();


        $packingError = Schema::hasTable('packing_errors')
            ? PackingError::count()
            : 0;

        $materials = Schema::connection(self::INVENTORY_CONN)->hasTable('packing_materials')
            ? PackingMaterial::all()
            : collect();

        $lowStockMaterialCount = $materials->filter(function ($m) {
            return isset($m->stock_qty, $m->low_stock_threshold) && $m->stock_qty <= $m->low_stock_threshold;
        })->count();

        $boxMaterials = $materials->filter(fn ($m) => !empty($m->is_box));

        $packingOrdersJson = $packingOrders->mapWithKeys(function ($order) {
            $priority = OrderPriority::packing($order->created_at ?? null);
            $items    = $this->buildOrderItems($order);

            // Total the order from its actual line items rather than the
            // single product_amount/qty fields, so multi-item orders
            // (order_items) report the correct total instead of null/0.
            $totalAmount = $items->sum('amount_raw');

            return [
                (string) $order->id => [
                    'customer'      => $order->customer_name,
                    'item'          => $order->product_name,
                    'qty'           => $order->qty,
                    'amount'        => number_format($totalAmount, 2),
                    'priority'      => $priority['label'],
                    'priorityKey'   => $priority['key'] ?? '',
                    'priorityClass' => $priority['class'],
                    'address'       => $order->address ?? '',
                    'items'         => $items,
                    'itemCount'     => $items->count(),
                ],
            ];
        });

        return view('order-fulfillment::packing', compact(
            'packingOrders',
            'inPackingCount',
            'ShippedCount',
            'packingError',
            'materials',
            'lowStockMaterialCount',
            'boxMaterials',
            'packingOrdersJson'
        ));
    }

    public function processOrder(Request $request, $id)
    {
        // 1. Validate input before doing anything else.
        $validated = $request->validate([
            'box'     => ['required', 'string'],
            'courier' => ['required', 'string'],
        ]);

        // 2. Look up the order FIRST. Fail fast if it doesn't exist,
        //    before any stock is touched.
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'error'   => 'order_not_found',
            ], 404);
        }

        // Everything below is wrapped in a top-level try/catch. If ANYTHING
        // unexpected throws here (type errors, DB errors, etc.), we still
        // want to return JSON — never let an exception fall through to
        // Laravel's HTML error page, since the frontend expects JSON.
        try {
            // Figure out which materials this shipment requires.
            $shipmentCount = Shipment::count();
            $isBonusShipment = (($shipmentCount + 1) % 10 == 0);

            $requiredMaterials = [
                $validated['box'],
                'Foam Inserts',
                'Silica Gel Packs',
            ];

            if ($isBonusShipment) {
                $requiredMaterials = array_merge($requiredMaterials, [
                    'Packing Tape',
                    'Bubble Wrap',
                    'Fragile Tape',
                ]);
            }

            // 3. Check stock BEFORE opening any transaction. This is
            //    intentionally outside DB::transaction() — logging a packing
            //    error must never be rolled back by the same transaction
            //    that failed. packing_materials lives on the separate
            //    "inventory" Neon database, so this read goes through that
            //    connection.
            foreach ($requiredMaterials as $materialName) {
                $row = PackingMaterial::where('name', $materialName)->first();

                if (!$row || $row->stock_qty <= 0) {
                    $this->logPackingError((string) $order->id, $materialName, $row ? 'out_of_stock' : 'material_not_found');

                    return response()->json([
                        'success'  => false,
                        'error'    => 'insufficient_stock',
                        'material' => $materialName,
                    ], 422);
                }
            }



            // 4. Decrement stock inside its own transaction, with row locks
            //    to guard against a race between the pre-check above and now.
            DB::connection(self::INVENTORY_CONN)->transaction(function () use ($requiredMaterials) {
                foreach ($requiredMaterials as $materialName) {
                    $row = PackingMaterial::where('name', $materialName)
                        ->lockForUpdate()
                        ->first();

                    if (!$row || $row->stock_qty <= 0) {
                        // Throw so this transaction rolls back cleanly; we log
                        // the error AFTER the transaction exits (see catch below).
                        throw new \RuntimeException('INSUFFICIENT_STOCK::' . $materialName);
                    }
                }

                // All materials confirmed in stock — safe to decrement.
                foreach ($requiredMaterials as $materialName) {
                    PackingMaterial::where('name', $materialName)->decrement('stock_qty', 1);
                }
            });

            // 5. Stock is now decremented and committed on the inventory DB.
            //    Create the shipment + update the order on the default DB.
            //    If anything here fails, we must give the materials back.
            try {
                $result = DB::transaction(function () use ($validated, $order, $id) {

                    $trackingNumber = strtoupper($validated['courier']) . '-' . time();
                    $shipmentId = $this->generateUniqueShipmentId();

                    Shipment::create([
                        'shipment_id'     => $shipmentId,
                        'order_id'        => $order->id,
                        'customer_name'   => $order->customer_name,
                        'product_name'    => $order->product_name,
                        'qty'             => $order->qty,
                        'amount'          => $order->product_amount * $order->qty,
                        'courier'         => $validated['courier'],
                        'box_used'        => $validated['box'],
                        'tracking_number' => $trackingNumber,
                        'status'          => 'SHIPPED',
                        'address'         => $order->address,
                        'due_date'        => $order->due_date,
                    ]);

                    $order->update(['status' => 'SHIPPED']);

                    return [
                        'success'         => true,
                        'shipment_id'     => $shipmentId,
                        'tracking_number' => $trackingNumber,
                    ];
                });
            } catch (\Throwable $e) {
                // Compensating action: give the materials back since the
                // inventory-side decrement already committed on its own
                // connection and won't be rolled back by this failure.
                $this->restoreMaterialStock($requiredMaterials);
                throw $e;
            }
        } catch (\RuntimeException $e) {
            // Stock ran out between the pre-check and the locked re-check
            // (race condition). The inventory transaction has already rolled
            // back cleanly at this point, so it's safe to log now.
            $materialName = str_replace('INSUFFICIENT_STOCK::', '', $e->getMessage());
            $this->logPackingError((string) $order->id, $materialName, 'out_of_stock');

            return response()->json([
                'success'  => false,
                'error'    => 'insufficient_stock',
                'material' => $materialName,
            ], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'error'   => 'processing_failed',
            ], 500);
        }

        return response()->json($result, 200);
    }

    /**
     * Compensating rollback for the cross-database transaction gap: puts
     * back the stock that was decremented on the inventory connection when
     * the shipment/order write on the default connection fails afterward.
     */
    private function restoreMaterialStock(array $requiredMaterials): void
    {
        try {
            DB::connection(self::INVENTORY_CONN)->transaction(function () use ($requiredMaterials) {
                foreach ($requiredMaterials as $materialName) {
                    PackingMaterial::where('name', $materialName)->increment('stock_qty', 1);
                }
            });
        } catch (\Throwable $e) {
            // If even the compensating restore fails, this needs a human —
            // log loudly rather than losing the discrepancy silently.
            report($e);
        }
    }

    /**
     * Log a packing error. Deliberately defensive: if the packing_errors
     * table doesn't exist yet (e.g. migration not run), this must NOT
     * throw and take down the whole request — it falls back to the
     * application log instead so the real business response still reaches
     * the user.
     */
    private function logPackingError(string $orderId, string $material, string $reason): void
    {
        if (!Schema::hasTable('packing_errors')) {
            \Illuminate\Support\Facades\Log::warning('packing_errors table missing — could not log packing error', [
                'order_id' => $orderId,
                'material' => $material,
                'reason'   => $reason,
            ]);
            return;
        }

        try {
            PackingError::create([
                'order_id' => $orderId,
                'material' => $material,
                'reason'   => $reason,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Build the list of line items shown in the packing modal.
     *
     * Prefers the order's related order_items rows (multi-item orders).
     * Falls back to the single product_name/qty/product_amount fields on
     * the order itself, so orders created before order_items existed (or
     * environments where the table hasn't been migrated yet) still show
     * a one-line item list instead of an empty modal.
     */
    private function buildOrderItems(Order $order)
    {
        if (Schema::hasTable('order_items') && $order->relationLoaded('items') && $order->items->isNotEmpty()) {
            return $order->items->map(function ($item) {
                $rawAmount = $item->qty * $item->product_amount;

                return [
                    'name'       => $item->product_name,
                    'qty'        => $item->qty,
                    'amount'     => number_format($rawAmount, 2),
                    'amount_raw' => $rawAmount,
                ];
            })->values();
        }

        $rawAmount = $order->product_amount * $order->qty;

        return collect([[
            'name'       => $order->product_name,
            'qty'        => $order->qty,
            'amount'     => number_format($rawAmount, 2),
            'amount_raw' => $rawAmount,
        ]]);
    }

    /**
     * Generate a shipment ID and guarantee it doesn't already exist.
     */
    private function generateUniqueShipmentId(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = 'SHIP-' . strtoupper(Str::random(8));

            $exists = Shipment::where('shipment_id', $candidate)->exists();

            if (!$exists) {
                return $candidate;
            }
        }

        // Extremely unlikely fallback: timestamp guarantees uniqueness.
        return 'SHIP-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
    }
}
