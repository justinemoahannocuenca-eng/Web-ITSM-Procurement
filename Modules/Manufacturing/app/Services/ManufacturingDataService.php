<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Models\WorkOrder;
use Modules\Manufacturing\Models\Worker;
use Modules\Manufacturing\Models\QcSession;
use Modules\Manufacturing\Models\ReworkOrder;
use Modules\Manufacturing\Models\Requisition;

class ManufacturingDataService
{
    // ── Load everything ──────────────────────────────────────────────────────
    public function loadAll(): array
    {
        return [
            'workOrders'       => $this->workOrders(),
            'workers'          => $this->workers(),
            'benchmarkTargets' => config('manufacturing.benchmarkTargets'),
            'qcSessions'       => $this->qcSessions(),
            'reworkOrders'     => $this->reworkOrders(),
            'requisitions'     => $this->requisitions(),
        ];
    }

    // ── Work orders ──────────────────────────────────────────────────────────
    public function workOrders(): array
    {
        return WorkOrder::with('parts')->orderBy('due_date', 'asc')->get()->map(fn ($wo) => [
            'id'       => $wo->id,
            'name'     => $wo->name,
            'specs'    => $wo->specs,
            'status'   => $wo->status,
            'due'      => $wo->due_date
                ? 'Due ' . $wo->due_date->format('M j')
                : $wo->due,
            'dueDate'  => optional($wo->due_date)->toDateString(),
            'source'   => $wo->source,
            'assigned' => $wo->assigned,
            'range'    => $wo->range,
            'createdAt'=> optional($wo->created_at)->toDateTimeString(),
            'parts'    => $wo->parts->map(fn ($p) => [
                'productId' => $p->product_id,
                'name'      => $p->name,
                'category'  => $p->category,
                'status'    => $p->status,
            ])->values()->all(),
        ])->values()->all();
    }

    // ── Workers ──────────────────────────────────────────────────────────────
    public function workers(): array
    {
        return Worker::orderBy('id')->get()->map(fn ($w) => [
            'id'    => $w->id,
            'name'  => $w->name,
            'role'  => $w->role,
            'notes' => $w->notes,
        ])->values()->all();
    }

    // ── QC sessions ──────────────────────────────────────────────────────────
    public function qcSessions(): array
    {
        return QcSession::with('results')->get()->map(fn ($s) => [
            'woId'     => $s->wo_id,
            'template' => $s->build_type,
            'tech'     => $s->tech,
            'results'  => $s->results->map(fn ($r) => [
                'checkId' => $r->check_id,
                'value'   => $r->value !== null ? $r->value + 0 : null,
                'verdict' => $r->verdict,
                'note'    => $r->note,
            ])->values()->all(),
        ])->values()->all();
    }

    // ── Rework orders ────────────────────────────────────────────────────────
    public function reworkOrders(): array
    {
        return ReworkOrder::with(['failedChecks', 'requiredParts'])->orderBy('id')->get()->map(fn ($rw) => [
            'id'                     => $rw->id,
            'woId'                   => $rw->wo_id,
            'buildName'              => $rw->build_name,
            'assignedTech'           => $rw->assigned_tech,
            'raisedBy'               => $rw->raised_by,
            'raisedDate'             => $rw->raised_date,
            'status'                 => $rw->status,
            'priority'               => $rw->priority,
            'notes'                  => $rw->notes,
            'escalatedToInventory' => (bool) $rw->escalated_to_inventory,
            'failedChecks'           => $rw->failedChecks->map(fn ($fc) => [
                'checkId'   => $fc->check_id,
                'checkName' => $fc->check_name,
                'verdict'   => $fc->verdict,
                'result'    => $fc->result,
                'target'    => $fc->target,
                'reason'    => $fc->reason,
            ])->values()->all(),
            'requiredParts'          => $rw->requiredParts->map(fn ($rp) => [
                'name'   => $rp->name,
                'status' => $rp->status,
                'eta'    => $rp->eta,
            ])->values()->all(),
        ])->values()->all();
    }

    // ── Requisitions ─────────────────────────────────────────────────────────
    public function requisitions(): array
    {
        return Requisition::orderBy('created_at', 'desc')->get()->map(fn ($r) => [
            'reqId'         => $r->req_id,
            'partName'      => $r->part_name,
            'quantity'      => $r->quantity,
            'department'    => $r->department,
            'destination'   => $r->destination,
            'requestedBy'   => $r->requested_by,
            'priority'      => $r->priority,
            'woId'          => $r->wo_id,
            'notes'         => $r->notes,
            'dateRequested' => $r->date_requested?->format('M d, Y'),
            'status'        => $r->status,
        ])->values()->all();
    }
}
