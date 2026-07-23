<?php

namespace Modules\Procurement\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $db = DB::connection('procurement');
        $clientId = (int) session('employee_client_id');
        $rootTesting = config('nexora.root_admin_module_testing') && $request->user()?->role === 'root_admin';

        $table = function (string $name) use ($db, $clientId, $rootTesting) {
            $query = $db->table($name);

            if (! $rootTesting) {
                $query->where($name.'.client_id', $clientId);
            }

            return $query;
        };

        $poCount = $table('purchase_orders')->count();
        $poStatusBreakdown = $table('purchase_orders')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $supplierCount = $table('suppliers')->where('status', 'active')->count();
        $requisitionCount = $table('requisitions')->count();
        $deliveryCount = $table('deliveries')->count();
        $pendingDeliveries = $table('deliveries')
            ->whereIn('status', ['pending', 'scheduled', 'intransit'])
            ->count();

        $recentPOs = $table('purchase_orders')
            ->select('id', 'po_number', 'supplier_id', 'qty', 'amount', 'status', 'priority', 'order_date', 'expected_delivery_date', 'item', 'brand')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $supplierIds = $recentPOs->pluck('supplier_id')->filter()->unique()->all();
        $suppliersMap = $supplierIds
            ? $table('suppliers')->whereIn('id', $supplierIds)->pluck('name', 'id')->all()
            : [];

        $recentDeliveries = $table('deliveries')
            ->select('id', 'shipment_number', 'purchase_order_id', 'supplier_id', 'status', 'delivery_date', 'estimated_arrival', 'actual_arrival', 'carrier')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $deliverySupplierIds = $recentDeliveries->pluck('supplier_id')->filter()->unique()->all();
        $deliverySuppliersMap = $deliverySupplierIds
            ? $table('suppliers')->whereIn('id', $deliverySupplierIds)->pluck('name', 'id')->all()
            : [];

        $spendByBrand = $table('purchase_orders')
            ->select('brand', DB::raw('SUM(amount) as total'))
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->groupBy('brand')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $totalSpend = $table('purchase_orders')
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->sum('amount');

        $topSuppliers = $table('purchase_orders')
            ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->select('suppliers.id', 'suppliers.name', DB::raw('SUM(purchase_orders.amount) as total_spend'))
            ->whereNotIn('purchase_orders.status', ['cancelled', 'rejected'])
            ->when(! $rootTesting, fn ($query) => $query->where('suppliers.client_id', $clientId))
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total_spend')
            ->limit(5)
            ->get();

        return view('procurement::pages.dashboard', [
            'poCount' => $poCount,
            'poStatusBreakdown' => $poStatusBreakdown,
            'supplierCount' => $supplierCount,
            'requisitionCount' => $requisitionCount,
            'deliveryCount' => $deliveryCount,
            'pendingDeliveries' => $pendingDeliveries,
            'recentPOs' => $recentPOs,
            'suppliersMap' => $suppliersMap,
            'recentDeliveries' => $recentDeliveries,
            'deliverySuppliersMap' => $deliverySuppliersMap,
            'spendByBrand' => $spendByBrand,
            'totalSpend' => $totalSpend,
            'totalSpendFormatted' => 'PHP '.number_format($totalSpend, 2),
            'topSuppliers' => $topSuppliers,
            'lowStockAlerts' => collect(),
        ]);
    }
}
