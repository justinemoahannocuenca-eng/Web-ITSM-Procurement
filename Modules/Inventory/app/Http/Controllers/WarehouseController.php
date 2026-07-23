<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;

use Modules\Inventory\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::withCount(['stockLevels as item_types_count' => function ($query) {
            $query->where('stock', '>', 0)->select(\Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT item_id)'));
        }])->get();

        return view('inventory::warehouse', [
            'warehouses' => $warehouses,
            'activePage' => 'warehouse',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'capacity_units' => 'required|integer|min:1',
            'status' => 'required|in:active,inactive',
        ]);

        Warehouse::create($validated);

        return redirect()->route('inventory.warehouse')->with('success', 'Warehouse created successfully.');
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'capacity_units' => 'required|integer|min:1',
            'status' => 'required|in:active,inactive',
        ]);

        $warehouse->update($validated);

        return redirect()->route('inventory.warehouse')->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->used_units > 0) {
            return back()->with('error', 'Cannot deactivate warehouse with stock. Relocate items first.');
        }

        $warehouse->delete();

        return redirect()->route('inventory.warehouse')->with('success', 'Warehouse deactivated.');
    }
}

