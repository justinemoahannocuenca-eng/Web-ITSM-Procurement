<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;

use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\Warehouse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::with(['item', 'warehouse', 'performer'])->orderByDesc('created_at');

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($warehouse = $request->input('warehouse')) {
            $query->where('warehouse_id', $warehouse);
        }

        if ($reference = $request->input('reference')) {
            $query->where('reference', $reference);
        }

        if ($dateRange = $request->input('date_range')) {
            match ($dateRange) {
                'today' => $query->whereDate('created_at', today()),
                'this_week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                'this_month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
                default => null,
            };
        }

        if ($search = $request->input('search')) {
            $search = strtolower($search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(reference) LIKE ?', ["%{$search}%"])
                  ->orWhereHas('item', function ($iq) use ($search) {
                      $iq->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                   });
            });
        }

        $movements = $query->paginate(10)->appends($request->query());

        // Merge transfer movements (which are stored as two rows: from & to) into a single display row.
        // Group by item_id + reference since that's how transfer rows are created.
        $mergedMovements = collect();
        $transferGroups = $movements->getCollection()->groupBy(fn ($m) => $m->type === 'transfer'
            ? ($m->item_id . '|' . ($m->reference ?? ''))
            : ('__single__|' . $m->id)
        );

        foreach ($transferGroups as $groupKey => $group) {
            // Skip empty keys
            if ($groupKey === null || $groupKey === '') {
                continue;
            }

            // Handle non-transfer movements (each has unique key)
            if (str_starts_with($groupKey, '__single__|')) {
                $mergedMovements->push($group->first());
                continue;
            }

            // Take the newest row as the base (for date / performer / item fields)
            $base = $group->sortByDesc('created_at')->first();

            $transferFrom = $group->firstWhere('warehouse_id', $group->min('warehouse_id')) ?? $group->first();
            $transferTo = $group->firstWhere('warehouse_id', $group->max('warehouse_id')) ?? $group->last();

            $fromName = $transferFrom?->warehouse?->name ?? 'Deleted';
            $toName = $transferTo?->warehouse?->name ?? 'Deleted';

            // Attach display-only fields consumed by the blade.
            $base->transfer_warehouses_display = $fromName . ' â‡„ ' . $toName;
            $base->transfer_quantity_display = $base->quantity;

            $mergedMovements->push($base);
        }

        // Sort merged results newest-first
        $mergedMovements = $mergedMovements->sortByDesc('created_at')->values();

        // Replace pagination collection so links stay correct.
        $movements->setCollection($mergedMovements);

        $totals = [
            'inbound' => StockMovement::where('type', 'inbound')->sum('quantity'),
            'outbound' => StockMovement::where('type', 'outbound')->sum('quantity'),
            'transfer' => StockMovement::where('type', 'transfer')->sum('quantity') / 2,
            'adjustment' => StockMovement::where('type', 'adjustment')->sum('quantity'),
        ];
        $totals['net'] = $totals['inbound'] + $totals['outbound'] + $totals['adjustment'];

        return view('inventory::stock-movement', [
            'movements' => $movements,
            'warehouses' => Warehouse::where('status', 'active')->whereNull('deleted_at')->get(),
            'totals' => $totals,
            'activePage' => 'stock-movement',
        ]);
    }
}

