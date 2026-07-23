<?php

namespace Modules\Finance\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Finance\Models\Invoice;
use App\Services\ErpIntegrationService;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::query()->latest('issue_date')->get();
        $currentMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        $monthTotal = fn ($month, ?string $status = null) => Invoice::query()
            ->when($status, fn ($query) => $query->where('status', $status), fn ($query) => $query->where('status', '!=', 'Rejected'))
            ->whereYear('issue_date', $month->year)
            ->whereMonth('issue_date', $month->month)
            ->sum('invoice_amount');

        $change = static function (float $current, float $previous): float {
            return $previous > 0 ? (($current - $previous) / $previous) * 100 : ($current > 0 ? 100 : 0);
        };

        $currentTotal = (float) $monthTotal($currentMonth);
        $lastTotal = (float) $monthTotal($lastMonth);
        $currentPaid = (float) Invoice::query()->where('status', 'Paid')->whereYear('issue_date', $currentMonth->year)->whereMonth('issue_date', $currentMonth->month)->sum('paid_amount');
        $lastPaid = (float) Invoice::query()->where('status', 'Paid')->whereYear('issue_date', $lastMonth->year)->whereMonth('issue_date', $lastMonth->month)->sum('paid_amount');
        $currentPending = (float) $monthTotal($currentMonth, 'Pending');
        $lastPending = (float) $monthTotal($lastMonth, 'Pending');
        $overdue = Invoice::query()->where('due_date', '<', now())->where('status', 'Pending')->get()
            ->sum(fn (Invoice $invoice) => (float) $invoice->invoice_amount - (float) $invoice->discount + (float) $invoice->shipping_fee + (((float) $invoice->invoice_amount - (float) $invoice->discount + (float) $invoice->shipping_fee) * .12) - (float) $invoice->paid_amount);

        $percent = $change($currentTotal, $lastTotal);
        $paidPercent = $change($currentPaid, $lastPaid);
        $pendingPercent = $change($currentPending, $lastPending);

        return view('finance::invoicedash', compact('invoices', 'currentTotal', 'lastTotal', 'currentPaid', 'lastPaid', 'currentPending', 'lastPending', 'overdue', 'percent', 'paidPercent', 'pendingPercent') + [
            'trend' => $percent >= 0 ? '↑' : '↓', 'color' => $percent >= 0 ? 'text-emerald-400' : 'text-red-400',
            'paidTrend' => $paidPercent >= 0 ? '↑' : '↓', 'paidColor' => $paidPercent >= 0 ? 'text-emerald-400' : 'text-red-400',
            'pendingTrend' => $pendingPercent >= 0 ? '↑' : '↓', 'pendingColor' => $pendingPercent >= 0 ? 'text-emerald-400' : 'text-red-400',
            'currentOverdue' => $overdue, 'lastOverdue' => 0, 'overduePercent' => 0, 'overdueTrend' => '↑', 'overdueColor' => 'text-emerald-400',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['issue_date' => 'required|date', 'due_date' => 'required|date', 'invoice_amount' => 'required|numeric', 'status' => 'required|string|max:20']);
        Invoice::create($data + ['discount' => 0, 'shipping_fee' => 0, 'paid_amount' => 0, 'payment_status' => 'Unpaid']);
        return back()->with('success', 'Invoice created successfully.');
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $request->validate(['invoice_amount' => 'required|numeric', 'status' => 'required|string|max:20']);
        if (strtolower($data['status']) === 'paid') {
            $data += [
                'payment_status' => 'Paid',
                'paid_amount' => $data['invoice_amount'],
                'payment_date' => now()->toDateString(),
            ];
        }
        $invoice->update($data);
        app(ErpIntegrationService::class)->financeInvoiceChanged((int) session('employee_client_id'), $invoice->fresh());
        return response()->json(['success' => true]);
    }

    public function reject(Invoice $invoice)
    {
        $invoice->update(['status' => 'Rejected']);
        app(ErpIntegrationService::class)->financeInvoiceChanged((int) session('employee_client_id'), $invoice->fresh(), true);
        return response()->json(['success' => true]);
    }
}
