<?php

namespace Modules\OrderFulfillment\Http\Controllers;

use Modules\OrderFulfillment\Models\ReturnItem;

class ReturnController extends Controller
{
public function index()
{
    // Orders that were cancelled after they'd already left for delivery
    // ("Cancelled while shipping") start out as In Transit to Warehouse /
    // Pending. Once they've had a day to make it back, auto-close them out
    // the same way ShippingController::index() promotes SHIPPED shipments.
    ReturnItem::where('status', 'In Transit to Warehouse')
        ->where('updated_at', '<=', now()->subDay())
        ->update([
            'status'     => 'Completed',
            'resolution' => 'Returned to Inventory',
        ]);

    $returns = ReturnItem::all();

    $pendingReturns = ReturnItem::where('status', 'NEW')->count();

    $refundedToday = ReturnItem::whereDate(
        'updated_at',
        today()
    )->where('status', 'Refunded')
     ->count();

    return view('order-fulfillment::return', compact(
    'returns',
    'pendingReturns',
    'refundedToday'
));
}
}
