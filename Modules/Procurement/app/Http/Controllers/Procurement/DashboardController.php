<?php

namespace Modules\Procurement\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Compact peso format for the dashboard summaries: ₱9.6k, ₱1.2m, ₱1b.
     * Amounts under 1,000 are shown in full (₱950).
     */
    private function compactPeso($value): string
    {
        $value = (float) $value;
        $abs = abs($value);

        [$divisor, $suffix] = match (true) {
            $abs >= 1_000_000_000 => [1_000_000_000, 'b'],
            $abs >= 1_000_000 => [1_000_000, 'm'],
            $abs >= 1_000 => [1_000, 'k'],
            default => [1, ''],
        };

        $scaled = $value / $divisor;
        $formatted = $suffix === ''
            ? number_format($scaled, 0)
            : rtrim(rtrim(number_format($scaled, 1), '0'), '.'); // 9.6k, 1k (not 1.0k)

        return '₱'.$formatted.$suffix;
    }

    /**
     * Lightweight JSON counts polled by the dashboard + sidebar so cards and
     * nav badges update live (no page refresh). Every query is wrapped so a
     * missing table/column/connection can never turn this into a 500.
     */
    public function liveStats(Request $request)
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

        $safe = function (callable $cb): int {
            try {
                return (int) $cb();
            } catch (\Throwable $e) {
                return 0;
            }
        };

        // Requisitions live on the Manufacturing / OrderFulfillment connection
        // (same source the sidebar badge uses).
        $requisitionPending = 0;
        foreach (['orderfullfillment', 'manufacturing'] as $conn) {
            try {
                $external = DB::connection($conn);
                if ($external->getSchemaBuilder()->hasTable('requisitions')) {
                    $requisitionPending = $external->getSchemaBuilder()->hasColumn('requisitions', 'status')
                        ? (int) $external->table('requisitions')->whereIn('status', ['Pending', 'pending'])->count()
                        : (int) $external->table('requisitions')->count();
                    break;
                }
            } catch (\Throwable $e) {
                // try the next connection
            }
        }

        return response()->json([
            'cards' => [
                'activePos' => $safe(fn () => $table('purchase_orders')->count()),
                'suppliers' => $safe(fn () => $table('suppliers')->where('status', 'active')->count()),
                'requisitions' => $safe(fn () => $table('requisitions')->count()),
                'deliveries' => $safe(fn () => $table('deliveries')->count()),
            ],
            'badges' => [
                'purchaseOrders' => $safe(fn () => $table('purchase_orders')->where('status', 'pending')->count()),
                'requisitions' => $requisitionPending,
                'deliveries' => $safe(fn () => $table('deliveries')->whereIn('status', ['pending', 'scheduled', 'intransit'])->count()),
            ],
        ]);
    }

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
            ->limit(5)
            ->get();

        $supplierIds = $recentPOs->pluck('supplier_id')->filter()->unique()->all();
        $suppliersMap = $supplierIds
            ? $table('suppliers')->whereIn('id', $supplierIds)->pluck('name', 'id')->all()
            : [];

        $recentDeliveries = $table('deliveries')
            ->select('id', 'shipment_number', 'purchase_order_id', 'supplier_id', 'status', 'delivery_date', 'estimated_arrival', 'actual_arrival', 'carrier')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $deliverySupplierIds = $recentDeliveries->pluck('supplier_id')->filter()->unique()->all();
        $deliverySuppliersMap = $deliverySupplierIds
            ? $table('suppliers')->whereIn('id', $deliverySupplierIds)->pluck('name', 'id')->all()
            : [];

        // purchase_orders.brand now holds each PO's Category (see the PO
        // modal's Category field) — aliased to `category` here so the whole
        // "Spend by Category" feature reads naturally end-to-end.
        $spendByCategoryAll = $table('purchase_orders')
            ->select('brand as category', DB::raw('SUM(amount) as total'))
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->groupBy('brand')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                $row->formatted_total = $this->compactPeso($row->total);

                return $row;
            })
            ->values();

        // Top 5 for the compact dashboard panel; the full list feeds the
        // "View all" modal.
        $spendByCategory = $spendByCategoryAll->take(5)->values();

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
            ->get()
            ->map(function ($row) {
                $row->formatted_total_spend = $this->compactPeso($row->total_spend);

                return $row;
            });

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
            'spendByCategory' => $spendByCategory,
            'spendByCategoryAll' => $spendByCategoryAll,
            'totalSpend' => $totalSpend,
            'totalSpendFormatted' => $this->compactPeso($totalSpend),
            'topSuppliers' => $topSuppliers,
            'lowStockAlerts' => collect(),
        ]);
    }
}
