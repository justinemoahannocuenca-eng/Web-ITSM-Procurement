<?php

namespace Modules\Procurement\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Models\Warehouse;

class ProcurementServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'procurement');
        $this->commands([
            \Modules\Procurement\Console\Commands\EnsureProcurementClientColumns::class,
            \Modules\Procurement\Console\Commands\InstallProcurementSchema::class,
        ]);

        Route::middleware('web')
            ->prefix('procurement')
            ->name('procurement.')
            ->group(__DIR__.'/../../routes/web.php');

        // The PO modal is included by every Procurement page, not just the
        // purchase-order page. Supply its client-scoped warehouse list at the
        // shared partial level so a user cannot submit a PO with a typed or
        // foreign-client delivery address.
        View::composer('procurement::partials.modals', function ($view): void {
            $view->with('warehouses', Warehouse::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'address']));
        });

          View::composer('partials.sidebar', function ($view): void {
            $alerts = DB::connection('inventory')
                ->table('stock_levels as sl')
                ->join('items as i', 'sl.item_id', '=', 'i.id')
                ->where('sl.stock', '<', 5)
                ->orderBy('sl.stock', 'asc')
                ->select('sl.stock', 'sl.reorder_threshold', 'i.name as item_name', 'i.sku')
                ->limit(5)
                ->get();

            $requisitionCount = 0;
            try {
                $requisitionConnection = $this->resolveRequisitionConnection();
                if ($requisitionConnection && $requisitionConnection->getSchemaBuilder()->hasTable('requisitions')) {
                    if ($requisitionConnection->getSchemaBuilder()->hasColumn('requisitions', 'status')) {
                        $requisitionCount = $requisitionConnection->table('requisitions')
                            ->where(function ($query) {
                                $query->where('status', 'Pending')
                                    ->orWhere('status', 'pending');
                            })
                            ->count();
                    } else {
                        $requisitionCount = $requisitionConnection->table('requisitions')->count();
                    }
                }
            } catch (\Exception $e) {
                $requisitionCount = 0;
            }

            $view->with([
                'lowStockAlerts' => $alerts,
                'lowStockAlertCount' => $alerts->count(),
                'requisitionCount' => $requisitionCount,
            ]);
        });
    }

    private function resolveRequisitionConnection()
    {
        foreach (['orderfullfillment', 'manufacturing'] as $connection) {
            try {
                if (DB::connection($connection)->getSchemaBuilder()->hasTable('requisitions')) {
                    return DB::connection($connection);
                }
            } catch (\Exception $e) {
                // ignore broken or unavailable external DB connections
            }
        }

        return DB::connection('manufacturing');
    }
}
