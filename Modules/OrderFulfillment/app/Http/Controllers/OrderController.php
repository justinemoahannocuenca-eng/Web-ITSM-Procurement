<?php

namespace Modules\OrderFulfillment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\OrderFulfillment\Models\Order;
use Modules\OrderFulfillment\Models\PackingMaterial;

class OrderController extends Controller
{
    /**
     * Single order detail page. Moved here from the abstract base
     * Controller, where it didn't belong (every controller inherited
     * an order-specific method) and was broken anyway (Order class
     * was never imported there).
     */
    public function show($id)
    {
        $order = Order::with('items')->findOrFail($id);
        return view('orders.show', compact('order'));
    }

    /**
     * Orders page (order.blade.php)
     */
    public function index()
    {
        $orders       = Order::with('items')->orderByDesc('created_at')->get();
        $ordersReceivedToday  = Order::where('status', 'NEW')->count();
        $inPacking    = Order::where('status', 'PACKING')->count();
        $shippedToday = Order::where('status', 'SHIPPED')->count();
        $delivered    = Order::where('status', 'DELIVERED')->count();
        $total        = Order::count();
        $onTimeRate   = $total > 0 ? round(($delivered / $total) * 100) . '%' : '0%';
        // The legacy Orders template uses these names for its stat cards.
        $inPackingCount = $inPacking;
        $ShippedCount = $shippedToday;
        $shippedTodayCount = $shippedToday;


        // Any status other than NEW represents a change that happened after
        // the order was placed, so surface all of them here (not just a
        // hardcoded subset) — that's what keeps this feed in sync with
        // whatever the order's current status actually is.
        $recentActivity = Order::where('status', '!=', 'NEW')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get()
            ->map(function ($o) {
                $status = strtoupper($o->status);

                $activityMap = [
                    'PACKING'          => ['📦', "Order {$o->id} moved to packing"],
                    'READY_TO_SHIP'    => ['📦', "Order {$o->id} is ready for delivery"],
                    'OUT_FOR_DELIVERY' => ['🚚', "Order {$o->id} is out for delivery"],
                    'SHIPPED'          => ['🚚', "Order {$o->id} has been shipped"],
                    'DELIVERED'        => ['✅', "Order {$o->id} has been delivered"],
                    'CANCELLED'        => ['❌', "Order {$o->id} has been cancelled"],
                ];

                [$icon, $message] = $activityMap[$status] ?? ['🔄', "Order {$o->id} status changed to " . strtolower(str_replace('_', ' ', $status))];

                $o->activity_icon    = $icon;
                $o->activity_message = $message;

                return $o;
            });

        return view('order-fulfillment::order', compact(
            'orders', 'ordersReceivedToday', 'inPacking', 'inPackingCount', 'shippedToday', 'ShippedCount', 'shippedTodayCount', 'onTimeRate', 'recentActivity'
        ));
    }

    /**
     * Packing page (packing.blade.php)
     */
    public function packing()
    {
        $packingOrders    = Order::where('status', 'PACKING')->get();
        $readyToShipCount = Order::where('status', 'READY_TO_SHIP')->count();
        $packingErrorToday = 0; // TODO: hook up to a packing_errors table once one exists

        // packing_materials lives on the separate "inventory" connection,
        // not the default one — must go through the PackingMaterial model
        // (which declares that connection) rather than DB::table(), or this
        // silently queries the wrong database and returns nothing.
        $materials = PackingMaterial::all();

        return view('order-fulfillment::packing', compact(
            'packingOrders', 'readyToShipCount', 'packingErrorToday', 'materials'
        ));
    }

    /**
     * AJAX: mark an order as being prepared -> moves it to PACKING.
     * The order row itself is never deleted, only its status changes,
     * so it keeps showing on the Orders page while also now appearing
     * in the Packing column/queue on the dashboard and packing page.
     */
    public function prepare($id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        if (strtoupper($order->status) !== 'NEW') {
            return response()->json([
                'success' => false,
                'message' => 'Order is already ' . strtoupper($order->status) . '.',
            ], 409);
        }

        $order->update([
            'status'     => 'PACKING',
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'status'  => 'PACKING',
        ]);
    }

    /**
     * AJAX: cancel an order.
     * Order row is never deleted, only its status changes to CANCELLED,
     * so it drops its priority badge / Prepare button on the Orders page
     * and shows up in the Recent activity / dashboard activity feed.
     */
    public function cancel($id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $status = strtoupper($order->status);

        if ($status === 'CANCELLED') {
            return response()->json([
                'success' => false,
                'message' => 'Order is already cancelled.',
            ], 409);
        }

        if (in_array($status, ['SHIPPED', 'DELIVERED'])) {
            return response()->json([
                'success' => false,
                'message' => 'Order has already been ' . strtolower($status) . ' and can no longer be cancelled.',
            ], 409);
        }

        $order->update([
            'status'     => 'CANCELLED',
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'status'  => 'CANCELLED',
        ]);
    }
}