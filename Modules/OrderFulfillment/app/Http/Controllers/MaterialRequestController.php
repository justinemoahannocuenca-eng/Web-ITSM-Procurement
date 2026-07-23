<?php

namespace Modules\OrderFulfillment\Http\Controllers;

use Illuminate\Http\Request;
use Modules\OrderFulfillment\Models\MaterialRequest;

class MaterialRequestController extends Controller
{
    
public function store(Request $request)
{
    $validated = $request->validate([
        'req_number' => 'required|string|unique:requisitions,req_number',
        'date_requested' => 'required|date',
        'item' => 'required|string',
        'qty' => 'required|integer|min:1',
        'department' => 'nullable|string',
        'requested_by' => 'nullable|string',
        'notes' => 'nullable|string',
        'priority' => 'nullable|string',
    ]);

    MaterialRequest::create($validated);

    return response()->json(['success' => true]);
}
}

