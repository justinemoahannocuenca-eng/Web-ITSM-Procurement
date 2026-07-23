<?php

namespace Modules\Manufacturing\Http\Controllers;

use Modules\Manufacturing\Models\WorkOrder;
use Modules\Manufacturing\Models\Worker;
use Modules\Manufacturing\Models\QcSession;
use Modules\Manufacturing\Models\ReworkOrder;
use Modules\Manufacturing\Models\Requisition;
use Modules\Manufacturing\Services\ManufacturingDataService;
use Modules\Manufacturing\Services\BenchmarkTargetService;
use Modules\Manufacturing\Services\DueDateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ManufacturingController extends Controller
{
    // ── Page load ────────────────────────────────────────────────────────────
    public function index()
    {
        $data = (new ManufacturingDataService())->loadAll();

        return view('manufacturing::Manufacturing', [
            'workOrders'       => $data['workOrders'],
            'workers'          => $data['workers'],
            'benchmarkTargets' => $data['benchmarkTargets'],
            'qcSessions'       => $data['qcSessions'],
            'reworkOrders'     => $data['reworkOrders'],
            'requisitions'     => $data['requisitions'],
            'statusStyles'     => config('manufacturing.statusStyles'),
            'partStyles'       => config('manufacturing.partStyles'),
            'rangeStyles'      => config('manufacturing.rangeStyles'),
            'tempData'         => array_merge($data, [
                'statusStyles' => config('manufacturing.statusStyles'),
                'partStyles'   => config('manufacturing.partStyles'),
                'rangeStyles'  => config('manufacturing.rangeStyles'),
            ]),
        ]);
    }

    // ── Work orders ──────────────────────────────────────────────────────────
    public function updateOrder(Request $request): JsonResponse
    {
        $orderIndex  = (int)  $request->input('orderIndex');
        $partChanges = (array) $request->input('partChanges', []);
        $sendToQC    = (bool)  $request->input('sendToQC', false);
        $cancelOrder = (bool)  $request->input('cancelOrder', false);

        $order = WorkOrder::with('parts')->orderBy('due_date', 'asc')->get()->values()->get($orderIndex);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        DB::connection('manufacturing')->transaction(function () use ($order, $partChanges, $sendToQC, $cancelOrder) {
            $partsByPosition = $order->parts->values();

            foreach ($partChanges as $position => $newStatus) {
                $part = $partsByPosition->get((int) $position);
                if (!$part) continue;
                if ($part->status === 'Sourcing' && $newStatus === 'Ready') {
                    $part->update(['status' => 'Ready']);
                }
            }

            $order->refresh()->load('parts');

            $allReady = $order->parts->every(fn ($p) => $p->status === 'Ready');
            if ($allReady && $order->status === 'Building') {
                $order->status = 'Finished';
            }

            if ($sendToQC && in_array($order->status, ['Finished', 'Building'])) {
                $order->status = 'QC Check';
            }

            if  ($cancelOrder) {
                $order->status = 'Cancelled';
            }

            $order->save();
        });

        return response()->json(['success' => true]);
    }

    // ── QC benchmark ─────────────────────────────────────────────────────────
    public function updateQC(Request $request): JsonResponse
    {
        $woId    = $request->input('woId');
        $results = $request->input('results', []);

        $order = WorkOrder::find($woId);
        $range = $order->range ?? null;

        $targetService   = new BenchmarkTargetService();
        $allowedVerdicts = ['Pass', 'Warn', 'Fail', ''];

        $cleanResults = array_map(function ($r) use ($targetService, $range, $allowedVerdicts) {
            $checkId = (string) ($r['checkId'] ?? '');
            $value   = isset($r['value']) && $r['value'] !== null ? (float) $r['value'] : null;
            $verdict = $r['verdict'] ?? '';

            if ($verdict === '' && $value !== null) {
                $verdict = $targetService->verdictFor($checkId, $range, $value);
            }

            return [
                'checkId' => $checkId,
                'value'   => $value,
                'verdict' => in_array($verdict, $allowedVerdicts) ? $verdict : '',
                'note'    => (string) ($r['note'] ?? ''),
            ];
        }, $results);

        DB::connection('manufacturing')->transaction(function () use ($woId, $cleanResults, $range, $targetService, $order) {
            $session = QcSession::where('wo_id', $woId)->first();
            if (!$session) {
                $session = QcSession::create(['wo_id' => $woId, 'build_type' => $range ?? 'mid-range', 'tech' => '']);
            }

            $session->results()->delete();
            foreach ($cleanResults as $r) {
                $session->results()->create([
                    'check_id' => $r['checkId'],
                    'value'    => $r['value'],
                    'verdict'  => $r['verdict'],
                    'note'     => $r['note'],
                ]);
            }

            $flagged = array_values(array_filter($cleanResults, fn ($r) => in_array($r['verdict'], ['Warn', 'Fail'])));
            if (count($flagged) > 0 && !ReworkOrder::where('wo_id', $woId)->exists()) {
                $targets = $targetService->targetsFor($range);

                $rwCount  = ReworkOrder::count() + 1;
                $reworkId = 'RW-' . session('employee_client_id') . '-' . str_pad((string) $rwCount, 4, '0', STR_PAD_LEFT);

                $rework = ReworkOrder::create([
                    'id'                       => $reworkId,
                    'wo_id'                    => $woId,
                    'build_name'               => $order->name ?? $woId,
                    'assigned_tech'            => $order->assigned ?? '',
                    'raised_by'                => $order->assigned ?? '',
                    'raised_date'              => now()->format('M d, Y'),
                    'status'                   => 'In Rework',
                    'priority'                 => 'Medium',
                    'notes'                    => 'Auto-created from QC benchmark flags.',
                    'escalated_to_inventory' => false,
                ]);

                foreach ($flagged as $r) {
                    $def = $targets[$r['checkId']] ?? null;
                    $rework->failedChecks()->create([
                        'check_id'   => $r['checkId'],
                        'check_name' => $def['name'] ?? $r['checkId'],
                        'verdict'    => $r['verdict'],
                        'result'     => $r['value'] !== null
                            ? number_format($r['value']) . ' ' . ($def['unit'] ?? '')
                            : '—',
                        'target'     => ($def['operator'] ?? '') . ' ' . number_format($def['target'] ?? 0) . ' ' . ($def['unit'] ?? ''),
                        'reason'     => $r['note'] ?: 'Flagged during QC benchmark',
                    ]);
                }
            }
        });

        return response()->json(['success' => true]);
    }

    // ── Rework ───────────────────────────────────────────────────────────────
    public function updateRework(Request $request): JsonResponse
    {
        $reworkIndex = (int) $request->input('reworkIndex');
        $rw = ReworkOrder::orderBy('id')->get()->values()->get($reworkIndex);

        if (!$rw) {
            return response()->json(['success' => false, 'message' => 'Rework order not found.'], 404);
        }

        if ($request->has('status'))     $rw->status   = $request->input('status');
        if ($request->has('priority'))   $rw->priority = $request->input('priority');
        if ($request->has('notes'))      $rw->notes    = $request->input('notes');
        if ($request->input('escalate')) $rw->escalated_to_inventory = true;
        $rw->save();

        return response()->json(['success' => true]);
    }

    public function addReworkPart(Request $request): JsonResponse
    {
        $reworkIndex = (int) $request->input('reworkIndex');
        $part        = $request->input('part', []);

        $rw = ReworkOrder::orderBy('id')->get()->values()->get($reworkIndex);
        if (!$rw) {
            return response()->json(['success' => false, 'message' => 'Rework order not found.'], 404);
        }

        $rw->requiredParts()->create([
            'name'   => (string) ($part['name']   ?? ''),
            'status' => (string) ($part['status'] ?? 'Sourcing'),
            'eta'    => $part['eta'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    public function updateReworkPart(Request $request): JsonResponse
    {
        $reworkIndex = (int) $request->input('reworkIndex');
        $partIndex   = (int) $request->input('partIndex');
        $part        = $request->input('part', []);

        $rw = ReworkOrder::with('requiredParts')->orderBy('id')->get()->values()->get($reworkIndex);
        if (!$rw) {
            return response()->json(['success' => false, 'message' => 'Rework order not found.'], 404);
        }

        $rp = $rw->requiredParts->values()->get($partIndex);
        if (!$rp) {
            return response()->json(['success' => false, 'message' => 'Part not found.'], 404);
        }

        $rp->update([
            'name'   => (string) ($part['name']   ?? ''),
            'status' => (string) ($part['status'] ?? 'Sourcing'),
            'eta'    => $part['eta'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    // ── Analytics ────────────────────────────────────────────────────────────
    public function addQcNote(Request $request): JsonResponse
    {
        $woId = $request->input('woId');
        $note = $request->input('note', '');

        $session = QcSession::where('wo_id', $woId)->first();
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
        }

        $session->results()->create([
            'check_id' => null,
            'value'    => null,
            'verdict'  => '',
            'note'     => $note,
        ]);

        return response()->json(['success' => true]);
    }

    // ── Workers ──────────────────────────────────────────────────────────────
    public function addWorker(Request $request): JsonResponse
    {
        Worker::create([
            'name'  => $request->input('name'),
            'role'  => $request->input('role'),
            'notes' => $request->input('notes', ''),
        ]);
        return response()->json(['success' => true]);
    }

    public function updateWorker(Request $request): JsonResponse
    {
        $worker = Worker::find($request->input('id'));
        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Worker not found.'], 404);
        }
        $worker->update([
            'name'  => $request->input('name'),
            'role'  => $request->input('role'),
            'notes' => $request->input('notes', ''),
        ]);
        return response()->json(['success' => true]);
    }

    public function deleteWorker(Request $request): JsonResponse
    {
        $worker = Worker::find($request->input('id'));
        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Worker not found.'], 404);
        }
        $worker->delete();
        return response()->json(['success' => true]);
    }

    public function assignWorker(Request $request): JsonResponse
    {
        $order = WorkOrder::find($request->input('orderId'));
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Work order not found.'], 404);
        }
        $order->update(['assigned' => $request->input('workerName')]);
        return response()->json(['success' => true]);
    }

    // ── Requisitions / inventory ─────────────────────────────────────────────
    public function sendToInventory(Request $request): JsonResponse
    {
        $woId    = $request->input('woId');
        $order   = WorkOrder::find($woId);

        $priority = 'Low';
        if ($order && $order->due_date) {
            $daysLeft = now()->startOfDay()->diffInDays($order->due_date->copy()->startOfDay(), false);
            if ($daysLeft <= 0)      $priority = 'Critical';
            elseif ($daysLeft <= 3)  $priority = 'High';
            elseif ($daysLeft <= 7)  $priority = 'Medium';
            else                     $priority = 'Low';
        }

        $reqCount = Requisition::count() + 1;
        $reqId    = 'REQ-' . session('employee_client_id') . '-' . str_pad((string) $reqCount, 4, '0', STR_PAD_LEFT);

        Requisition::create([
            'req_id'         => $reqId,
            'part_name'      => $request->input('partName'),
            'quantity'       => (int) $request->input('quantity', 1),
            'department'     => 'Manufacturing',
            'destination'    => 'Inventory',
            'requested_by'   => $request->input('requestedBy'),
            'priority'       => $priority,
            'wo_id'          => $woId,
            'notes'          => $request->input('notes'),
            'date_requested' => now()->toDateString(),
            'status'         => 'Pending',
        ]);

        return response()->json(['success' => true, 'reqId' => $reqId, 'priority' => $priority]);
    }

    // ── Work orders (cont.) ──────────────────────────────────────────────────
    public function cancelOrder(Request $request): JsonResponse
    {
        $orderIndex = (int) $request->input('orderIndex');
        $order = WorkOrder::orderBy('due_date', 'asc')->get()->values()->get($orderIndex);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        if ($order->status === 'Cancelled') {
            return response()->json(['success' => false, 'message' => 'Order is already cancelled.'], 422);
        }

        $order->update(['status' => 'Cancelled']);

        return response()->json(['success' => true]);
    }

    // ── E-commerce intake ────────────────────────────────────────────────────
    public function receiveOrderFromEcommerce(Request $request): JsonResponse
    {
        $orderDate = $request->has('orderDate')
            ? \Carbon\Carbon::parse($request->input('orderDate'))
            : now();

        $dueDate = (new DueDateService())->calculate($orderDate);

        $order = WorkOrder::create([
            'id'       => $request->input('id'),
            'name'     => $request->input('name'),
            'specs'    => $request->input('specs'),
            'status'   => $request->input('status', 'Pending'),
            'due_date' => $dueDate->toDateString(),
            'source'   => $request->input('source'),
            'assigned' => $request->input('assigned'),
            'range'    => $request->input('range'),
        ]);

        foreach ($request->input('parts', []) as $part) {
            $order->parts()->create([
                'product_id' => $part['productId'] ?? null,
                'name'       => $part['name'] ?? '',
                'category'   => $part['category'] ?? '',
                'status'     => $part['status'] ?? 'Sourcing',
            ]);
        }

        return response()->json([
            'success' => true,
            'id'      => $order->id,
            'dueDate' => $dueDate->toDateString(),
        ]);
    }
}
