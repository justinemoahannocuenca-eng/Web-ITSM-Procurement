<?php

namespace Modules\OrderFulfillment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\OrderFulfillment\Models\Order;
use Modules\OrderFulfillment\Models\Shipment;
use Modules\OrderFulfillment\Models\DeliveryMan;
use Modules\OrderFulfillment\Models\ReturnItem;
use Modules\OrderFulfillment\App\Helpers\OrderStatus;
use Modules\OrderFulfillment\Models\OrderItem;
use Illuminate\Support\Str;

class ShippingController extends Controller
{
    public function index()
    {
        // Promote anything that's been sitting at SHIPPED for 24+ hours to
        // READY_TO_SHIP. Runs here instead of a scheduled command, so it
        // only recalculates when someone actually loads this page.
        Shipment::where('status', 'SHIPPED')
            ->whereNotNull('shipped_at')
            ->where('shipped_at', '<=', now()->subDay())
            ->update(['status' => 'READY_TO_SHIP']);

        $shipments = Shipment::select(
            'shipment_id',
            'order_id',
            'customer_name',
            'status',
            'due_date',
            'address',
            'tracking_number',
            'courier',
            'amount',
            'delivery_man_id',
            'updated_at'
        )
        ->whereIn('status', [
            'SHIPPED',
            'READY_TO_SHIP',
            'OUT_FOR_DELIVERY',
            'DELAYED',
            'DELIVERED',
        ])
        ->get();

        // Pull every line item for the orders behind these shipments in one
        // query, then attach a plain-array 'items' + 'items_count' to each
        // shipment so the Items column and the item-breakdown modals (order
        // detail + assign-driver) can both be driven from the same data
        // that's already being @json()'d out to the page.
        $orderIds = $shipments->pluck('order_id')->filter()->unique()->values();

        $itemsByOrder = OrderItem::whereIn('order_id', $orderIds)
            ->get(['order_id', 'product_name', 'qty', 'product_amount'])
            ->groupBy('order_id');

        $shipments->each(function (Shipment $shipment) use ($itemsByOrder) {
            $orderItems = $itemsByOrder->get($shipment->order_id, collect());

            $shipment->items = $orderItems->map(function (OrderItem $item) {
                return [
                    'product_name'   => $item->product_name,
                    'qty'            => (int) $item->qty,
                    'product_amount' => (float) $item->product_amount,
                    'line_total'     => (float) $item->line_total,
                ];
            })->values()->toArray();

            $shipment->items_count = $orderItems->count();
        });

        $shippedToday = Order::whereDate('updated_at', today())
            ->where('status', 'SHIPPED')
            ->count();

        $inTransit = Order::whereDate('updated_at', today())
            ->where('status', 'OUT_FOR_DELIVERY')
            ->count();

        $delayed = Order::whereDate('updated_at', today())
            ->where('status', 'DELAYED')
            ->count();

        $delivered = Order::whereDate('updated_at', today())
            ->where('status', 'DELIVERED')
            ->count();

        $onTimeRate = $delivered
            ? round(($delivered / ($delivered + $delayed)) * 100)
            : 0;

        // Delivery alerts panel: recently-changed shipments, newest first.
        // Same idea as the dashboard's $alerts — just built from $shipments
        // instead of a separate query.
        $deliveryAlerts = $shipments->sortByDesc('updated_at')->take(10)->map(function ($s) {
            // OrderStatus::label() returns the shouty all-caps form used for
            // the status pills (e.g. "OUT FOR DELIVERY"). That reads fine on
            // a small badge but not sitting in a sentence, so title-case it
            // here for the alert feed only — pills elsewhere are untouched.
            $label = ucwords(strtolower(OrderStatus::label($s->status)));

            return (object) [
                'id'      => $s->shipment_id,
                'icon'    => '🔔',
                'message' => $s->shipment_id . ' is now ' . $label,
            ];
        })->values();

        return view('order-fulfillment::shipping', compact(
            'shipments',
            'shippedToday',
            'inTransit',
            'delayed',
            'delivered',
            'onTimeRate',
            'deliveryAlerts'
        ));
    }

    /**
     * Available drivers for this shipment's courier — feeds the
     * "Assign Driver" modal.
     *
     * GET /shipping/{shipmentId}/drivers
     */
    public function drivers(string $shipmentId)
    {
        $shipment = Shipment::where('shipment_id', $shipmentId)->firstOrFail();

        $drivers = DeliveryMan::available()
            ->forCourier($shipment->courier)
            ->orderBy('name')
            ->get(['id', 'name', 'vehicle_type', 'plate_number']);

        return response()->json($drivers);
    }

    /**
     * Assign a driver to a shipment: the shipment moves to OUT_FOR_DELIVERY
     * and the driver flips to UNAVAILABLE until the shipment is delivered.
     *
     * POST /shipping/{shipmentId}/assign-driver
     */
    public function assignDriver(Request $request, string $shipmentId)
    {
        $validated = $request->validate([
            'driver_id' => 'required|string|exists:delivery_men,id',
        ]);

        $shipment = Shipment::where('shipment_id', $shipmentId)->firstOrFail();

        $driver = DeliveryMan::where('id', $validated['driver_id'])
            ->where('status', DeliveryMan::STATUS_AVAILABLE)
            ->first();

        if (! $driver) {
            return response()->json([
                'message' => 'That driver is no longer available. Please pick another.',
            ], 422);
        }

        DB::transaction(function () use ($shipment, $driver) {
            $shipment->update([
                'delivery_man_id' => $driver->id,
                'status' => 'OUT_FOR_DELIVERY',
            ]);

            $driver->update(['status' => DeliveryMan::STATUS_UNAVAILABLE]);
        });

        return response()->json([
            'message' => "{$driver->name} assigned to {$shipment->shipment_id}",
            'status' => $shipment->status,
        ]);
    }

    /**
     * Cancel a shipment: it disappears from the Shipping page and a matching
     * record is created on the Returns page instead.
     *
     * POST /shipping/{shipmentId}/cancel
     */
    public function cancel(string $shipmentId)
    {
        $shipment = Shipment::where('shipment_id', $shipmentId)->firstOrFail();

        if (strtoupper($shipment->status) === 'DELIVERED') {
            return response()->json([
                'message' => 'This order has already been delivered and can no longer be cancelled.',
            ], 422);
        }

        DB::transaction(function () use ($shipment) {
            $orderItems = OrderItem::where('order_id', $shipment->order_id)
                ->get(['product_name', 'qty', 'product_amount']);

            // line_total isn't a real order_items column — it's derived
            // (qty * product_amount) — so sum it in PHP the same way
            // index() and the order-detail modal already do, rather than
            // selecting it directly in the query.
            $refundAmount = $orderItems->sum(function (OrderItem $item) {
                return $item->qty * $item->product_amount;
            });

            ReturnItem::create([
                'id'            => (string) Str::uuid(),
                'order_id'      => $shipment->order_id,
                'customer_name' => $shipment->customer_name,
                'product_name'  => $orderItems->pluck('product_name')->implode(', ') ?: 'N/A',
                'reason'        => 'Cancelled while shipping',
                // Admin cancelled this after it had already left for delivery,
                // so there's nothing to "review" — it just needs to make its
                // way back to the warehouse. ReturnController::index() auto-
                // promotes this to Completed / Returned to Inventory 24h later,
                // the same way Shipment::SHIPPED auto-promotes on the shipping page.
                'status'        => 'In Transit to Warehouse',
                'resolution'    => 'Pending',
                'due_date'      => $shipment->due_date,
                'address'       => $shipment->address,
                'refund_amount' => $refundAmount,
            ]);

            // Free up the driver, if one was already assigned, same as a
            // normal delivery completion would.
            if ($shipment->delivery_man_id) {
                DeliveryMan::where('id', $shipment->delivery_man_id)
                    ->update(['status' => DeliveryMan::STATUS_AVAILABLE]);
            }

            // Setting status to CANCELLED both removes it from the shipping
            // index (which only pulls SHIPPED/READY_TO_SHIP/OUT_FOR_DELIVERY/
            // DELAYED/DELIVERED) and, via Shipment::booted()'s `updated`
            // hook, mirrors the same status onto the parent Order.
            $shipment->update(['status' => 'CANCELLED']);
        });

        return response()->json([
            'message' => 'Order cancelled and moved to Returns.',
        ]);
    }
}
