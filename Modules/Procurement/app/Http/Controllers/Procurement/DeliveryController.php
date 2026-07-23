<?php

namespace Modules\Procurement\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Modules\Procurement\Models\Delivery;
use Modules\Procurement\Models\PurchaseOrder;
use Modules\Procurement\Models\Requisition;
use Modules\Procurement\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Database\QueryException;

class DeliveryController extends Controller
{
    private function nextAvailableShipmentNumber(string $requestedNumber): string
    {
        if (! preg_match('/^(.*?)(\d+)$/', $requestedNumber, $matches)) {
            return $requestedNumber . '-' . now()->format('YmdHis');
        }

        $prefix = $matches[1];
        $width = strlen($matches[2]);
        $highestSequence = Delivery::query()
            ->where('shipment_number', 'like', $prefix . '%')
            ->pluck('shipment_number')
            ->map(function (string $number) use ($prefix): int {
                return preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $number, $parts)
                    ? (int) $parts[1]
                    : 0;
            })
            ->max() ?? 0;

        return $prefix . str_pad($highestSequence + 1, $width, '0', STR_PAD_LEFT);
    }

    private function createDeliveryWithUniqueShipmentNumber(array $attributes): Delivery
    {
        if (Delivery::query()->where('shipment_number', $attributes['shipment_number'])->exists()) {
            $attributes['shipment_number'] = $this->nextAvailableShipmentNumber($attributes['shipment_number']);
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return Delivery::create($attributes);
            } catch (QueryException $exception) {
                $message = $exception->getMessage();

                if (! str_contains($message, 'deliveries_shipment_number_key')
                    && ! str_contains($message, 'shipment_number')) {
                    throw $exception;
                }

                $attributes['shipment_number'] = $this->nextAvailableShipmentNumber($attributes['shipment_number']);
            }
        }

        throw new \RuntimeException('Unable to allocate a unique shipment number.');
    }

    public function index(): View
    {
        $deliveries = Delivery::with(['supplier', 'purchaseOrder'])->orderBy('delivery_date')->get();

        $counts = [
            'all' => $deliveries->count(),
            'pending' => $deliveries->where('status', 'pending')->count(),
            'shipment' => $deliveries->where('status', 'shipment')->count(),
            'intransit' => $deliveries->whereIn('status', ['intransit', 'shipment'])->count(),
            'delivered' => $deliveries->where('status', 'delivered')->count(),
            'complete' => $deliveries->whereIn('status', ['complete', 'delivered'])->count(),
            'delayed' => $deliveries->where('status', 'delayed')->count(),
        ];

        // Next shipment sequence, derived from the highest existing
        // "SHP-#####" number, so the "+ Log Delivery" form always
        // auto-fills the true next number instead of a hardcoded guess.
        $nextShipmentSeq = ($deliveries->pluck('shipment_number')
            ->map(fn (string $n) => (int) preg_replace('/\D/', '', $n))
            ->max() ?? 0) + 1;

        return view('procurement::pages.deliveries', compact('deliveries', 'counts', 'nextShipmentSeq'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'dr' => ['required', 'string', 'max:255'],
            'po' => ['required', 'string', 'max:255'],
            'supplier' => ['required', 'string', 'max:255'],
            'delDate' => ['required', 'date'],
            'items' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'in:intransit,delayed'],
            'remarks' => ['nullable', 'string'],
        ]);

        $purchaseOrder = PurchaseOrder::where('po_number', $data['po'])->first();
        $supplier = Supplier::where('name', $data['supplier'])->first();

        if (! $supplier) {
            $supplier = Supplier::create([
                'name' => $data['supplier'],
                'contact_person' => 'Pending',
                'email' => null,
                'phone' => null,
                'address' => null,
                'category' => 'General Procurement',
                'status' => 'active',
            ]);
        }

        $delivery = $this->createDeliveryWithUniqueShipmentNumber([
            'shipment_number' => $data['dr'],
            'purchase_order_id' => $purchaseOrder?->id,
            'supplier_id' => $supplier->id,
            'status' => $data['status'] ?? 'intransit',
            'qty' => (int) $data['qty'],
            'qty_expected' => (int) $data['qty'],
            'items' => $data['items'],
            'remarks' => $data['remarks'] ?? null,
            'delivery_date' => $data['delDate'],
            'estimated_arrival' => $purchaseOrder?->expected_delivery_date,
        ]);

        if ($purchaseOrder) {
            $purchaseOrder->update(['delivery_status' => 'intransit', 'status' => 'processing']);

            $requisition = null;
            if (! empty($purchaseOrder->requisition_reference)) {
                $requisition = Requisition::where('req_number', $purchaseOrder->requisition_reference)->first();
            }

            if (! $requisition && $purchaseOrder->requisition_id) {
                $requisition = Requisition::find($purchaseOrder->requisition_id);
            }

            if ($requisition) {
                $requisition->update(['delivery_status' => 'intransit']);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $delivery,
            'shipment_number' => $delivery->shipment_number,
            'delete_url' => route('procurement.deliveries.destroy', $delivery),
        ], 201);
    }

    public function update(Request $request, Delivery $delivery): JsonResponse
    {
        $data = $request->validate([
            'ship' => ['nullable', 'string', 'max:255'],
            'po' => ['nullable', 'string', 'max:255'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:pending,shipment,intransit,delayed,delivered,complete,cancel'],
            'carrier' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $purchaseOrder = ($data['po'] ?? null)
            ? PurchaseOrder::where('po_number', $data['po'])->first()
            : $delivery->purchaseOrder;
        $supplier = ($data['supplier'] ?? null)
            ? Supplier::where('name', $data['supplier'])->first()
            : $delivery->supplier;

        if (($data['supplier'] ?? null) && ! $supplier) {
            $supplier = Supplier::create([
                'name' => $data['supplier'],
                'contact_person' => 'Pending',
                'email' => null,
                'phone' => null,
                'address' => null,
                'category' => 'General Procurement',
                'status' => 'active',
            ]);
        }

        $status = strtolower((string) ($data['status'] ?? $delivery->status));
        $stageMap = [
            'pending' => 0,
            'shipment' => 1,
            'intransit' => 2,
            'delivered' => 3,
            'complete' => 4,
            'delayed' => 2,
            'cancel' => 0,
        ];

        $updateData = [
            'stage' => $stageMap[$status] ?? 0,
            'status' => $status,
        ];

        if ($data['ship'] ?? null) {
            $updateData['shipment_number'] = $data['ship'];
        }
        if ($data['po'] ?? null) {
            $updateData['purchase_order_id'] = $purchaseOrder?->id;
        }
        if ($data['supplier'] ?? null) {
            $updateData['supplier_id'] = $supplier?->id;
        }
        if ($data['date'] ?? null) {
            $updateData['delivery_date'] = $data['date'];
        }
        if ($data['note'] ?? null) {
            $updateData['remarks'] = $data['note'];
        }

        $delivery->update($updateData);

        if ($purchaseOrder && $status === 'complete') {
            $purchaseOrder->update(['status' => 'completed', 'delivery_status' => 'complete']);

            $requisition = null;
            if (! empty($purchaseOrder->requisition_reference)) {
                $requisition = Requisition::where('req_number', $purchaseOrder->requisition_reference)->first();
            }

            if (! $requisition && $purchaseOrder->requisition_id) {
                $requisition = Requisition::find($purchaseOrder->requisition_id);
            }

            if ($requisition) {
                $requisition->update(['status' => 'completed', 'delivery_status' => 'complete']);
            }
        }

        return response()->json(['success' => true, 'data' => $delivery]);
    }

    public function destroy(Delivery $delivery): JsonResponse
    {
        $delivery->delete();

        return response()->json(['success' => true]);
    }
}
