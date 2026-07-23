<?php

namespace Modules\Procurement\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Models\Warehouse;

class PurchaseOrderController extends Controller
{
    private function table(string $name)
    {
        $query = DB::connection('procurement')->table($name);

        if (! (config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin')) {
            $query->where($name.'.client_id', (int) session('employee_client_id'));
        }

        return $query;
    }

    /**
     * Detect a unique-constraint violation (e.g. a duplicate po_number),
     * regardless of which database driver raised it.
     */
    private function isDuplicateKeyException(\Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'duplicate key')
            || str_contains($message, 'Unique violation')
            || str_contains($message, 'SQLSTATE[23505]')
            || str_contains($message, 'UNIQUE constraint failed');
    }

    /**
     * Insert the purchase order, automatically regenerating the po_number
     * if it collides with one that already exists. This is what was making
     * "Submit for Approval" silently fail: the browser pre-fills the PO
     * number from an in-memory counter that resets on every page load, so
     * once real PO numbers passed that counter, every new submission hit
     * a duplicate po_number and the insert was rejected by the database.
     */
    private function insertPurchaseOrder(array $insert): int
    {
        $attempts = 0;
        $currentInsert = $insert;

        while ($attempts < 3) {
            try {
                return DB::connection('procurement')->table('purchase_orders')->insertGetId($currentInsert);
            } catch (\Throwable $e) {
                if ($this->isDuplicateKeyException($e)) {
                    $suffix = now()->format('YmdHis') . '-' . random_int(1000, 9999);
                    $currentInsert['po_number'] = $insert['po_number'] . '-' . $suffix;
                    $attempts++;
                    continue;
                }

                throw $e;
            }
        }

        throw new \RuntimeException('Unable to save purchase order after retrying.');
    }

    /**
     * Purchase Orders list page (filters, sortable table, add PO modal).
     */
    public function index(Request $request)
    {
        $purchaseOrders = $this->table('purchase_orders')
            ->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->select('purchase_orders.*', 'suppliers.name as supplier_name')
            ->orderBy('purchase_orders.created_at', 'desc')
            ->limit(8)
            ->get();

        $suppliers = $this->table('suppliers')
            ->orderBy('created_at', 'desc')
            ->get();

        $warehouses = Warehouse::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'address']);

        $statusCounts = $this->table('purchase_orders')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->mapWithKeys(function ($total, $status) {
                return [strtolower(str_replace([' ', '_'], '-', $status ?? 'pending')) => $total];
            });

        return view('procurement::pages.purchase-orders', compact('purchaseOrders', 'suppliers', 'warehouses', 'statusCounts'));
    }

    public function approved(Request $request)
    {
        $approvedPurchaseOrders = $this->table('purchase_orders')
            ->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->select('purchase_orders.*', 'suppliers.name as supplier_name')
            ->where('purchase_orders.status', 'approved')
            ->orderBy('purchase_orders.order_date', 'desc')
            ->get();

        return response()->json($approvedPurchaseOrders);
    }

    /**
     * Handle the "+ New PO" modal submit (submitAddPO in app-forms.js).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'po' => 'required|string|max:50',
            'supplier' => 'required|string|max:150',
            'brand' => 'nullable|string|max:100',
            'item' => 'nullable|string|max:150',
            'qty' => 'nullable|integer|min:1',
            'unitPrice' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'priority' => 'nullable|string|max:20',
            'expected' => 'nullable|date',
            'createdBy' => 'nullable|string|max:150',
            'remarks' => 'nullable|string',
            'reqRef' => 'nullable|string|max:50',
            'warehouse_id' => 'required|integer',
        ]);

        $warehouse = Warehouse::query()
            ->whereKey((int) $validated['warehouse_id'])
            ->where('status', 'active')
            ->first();

        if (! $warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Select an active warehouse belonging to your client.',
            ]);
        }

        $supplier = $this->table('suppliers')->where('name', $validated['supplier'])->first();
        $supplierId = $supplier?->id;

        if (! $supplierId) {
            $supplierId = DB::connection('procurement')->table('suppliers')->insertGetId([
                'client_id' => (int) session('employee_client_id'),
                'name' => $validated['supplier'],
                'contact_person' => 'Auto-imported',
                'email' => 'auto@example.com',
                'phone' => 'N/A',
                'address' => 'Auto-imported',
                'brand' => $validated['brand'] ?? null,
                'status' => 'active',
                'product_items' => '[]',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $insert = [
            'client_id' => (int) session('employee_client_id'),
            'po_number' => $validated['po'],
            'supplier_id' => $supplierId,
            'qty' => (int) ($validated['qty'] ?? 1),
            'amount' => (float) ($validated['amount'] ?? 0),
            'status' => 'pending',
            'priority' => strtolower($validated['priority'] ?? 'normal'),
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => $validated['expected'] ?? null,
            'created_by' => $validated['createdBy'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'item' => $validated['item'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'unit_price' => (float) ($validated['unitPrice'] ?? 0),
            'requisition_reference' => $validated['reqRef'] ?? null,
            'warehouse_id' => $warehouse->id,
            'delivery_address' => $warehouse->address,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $poId = $this->insertPurchaseOrder($insert);
        $savedPoNumber = $this->table('purchase_orders')->where('id', $poId)->value('po_number');

        DB::connection('procurement')->table('purchase_order_items')->insert([
            'client_id' => (int) session('employee_client_id'),
            'purchase_order_id' => $poId,
            'supplier_product_id' => null,
            'name' => $validated['item'] ?? 'Item',
            'qty' => (int) ($validated['qty'] ?? 1),
            'unit_price' => (float) ($validated['unitPrice'] ?? 0),
            'amount' => (float) ($validated['amount'] ?? 0),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $validated['po'] = $savedPoNumber;

        return response()->json(['status' => 'ok', 'data' => $validated, 'id' => $poId, 'po_number' => $savedPoNumber]);
    }

    public function update(Request $request, $purchaseOrder)
    {
        $validated = $request->validate([
            'status' => 'nullable|string|max:20',
            'amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $status = $validated['status'] ?? null;
        if ($status !== null) {
            $status = strtolower(trim($status));
            $allowed = ['pending', 'approved', 'rejected', 'cancelled', 'processing', 'completed'];
            if (!in_array($status, $allowed, true)) {
                $status = null;
            }
        }

        $purchaseOrderQuery = $this->table('purchase_orders')->where('id', $purchaseOrder);

        if (! $purchaseOrderQuery->exists()) {
            abort(404, 'Purchase order not found for this client.');
        }

        $purchaseOrderQuery->update([
            'status' => $status ?? DB::raw('status'),
            'amount' => $validated['amount'] ?? DB::raw('amount'),
            'remarks' => $validated['remarks'] ?? DB::raw('remarks'),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'ok', 'purchase_order_id' => (int) $purchaseOrder]);
    }

    public function destroy($purchaseOrder)
    {
        $this->table('purchase_orders')->where('id', $purchaseOrder)->delete();

        return response()->json(['status' => 'ok']);
    }
}
