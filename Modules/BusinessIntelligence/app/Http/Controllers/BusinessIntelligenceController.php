<?php

namespace Modules\BusinessIntelligence\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BusinessIntelligenceController
{
    /**
     * BI deliberately reads the owning module databases instead of writing
     * employee or business records into ITSM. Every query is scoped to the
     * current client before any aggregate is calculated.
     */
    public function dashboard(Request $request): View
    {
        $clientId = $this->clientId($request);
        $metrics = $this->metrics($clientId);
        $this->recordSnapshot($clientId, $metrics);

        return view('bi::dashboard', compact('metrics', 'clientId'));
    }

    public function departmentAnalytics(): View
    {
        return view('bi::department-analytics');
    }

    public function liveMonitor(): View
    {
        return view('bi::live-monitor');
    }

    public function aiInsights(): View
    {
        return view('bi::ai-insights');
    }

    public function aiChat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1500'],
        ]);

        $clientId = $this->clientId($request);
        if (! $clientId) {
            return response()->json(['message' => 'Select a client before requesting BI insights.'], 422);
        }

        $key = config('services.digitalocean_inference.key');
        $model = config('services.digitalocean_inference.model');
        if (! $key || ! $model) {
            return response()->json(['message' => 'AI Insights is not configured yet. Contact your system administrator.'], 503);
        }

        $metrics = $this->metrics($clientId);
        $this->recordSnapshot($clientId, $metrics);

        $systemPrompt = <<<PROMPT
You are Nexora BI, a business analyst. Answer only from the client-scoped aggregate metrics supplied below. Do not claim access to raw data, other clients, credentials, personal information, or system internals. If the metrics cannot answer the question, say so plainly and suggest a safe next metric to add. Keep the answer practical and concise.

Client-scoped metrics: %s
PROMPT;

        try {
            $response = Http::withToken($key)
                ->acceptJson()
                ->timeout(30)
                ->post(rtrim((string) config('services.digitalocean_inference.base_url'), '/').'/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => sprintf($systemPrompt, json_encode($metrics, JSON_THROW_ON_ERROR))],
                        ['role' => 'user', 'content' => $validated['message']],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 500,
                ]);

            if ($response->failed()) {
                Log::warning('DigitalOcean BI inference request failed.', [
                    'client_id' => $clientId,
                    'status' => $response->status(),
                ]);

                return response()->json(['message' => 'AI Insights is temporarily unavailable. Please try again shortly.'], 502);
            }

            $message = data_get($response->json(), 'choices.0.message.content');
            if (is_array($message)) {
                $message = collect($message)->pluck('text')->filter()->implode("\n");
            }
            $message = trim((string) $message);
            if ($message === '') {
                return response()->json(['message' => 'AI Insights returned no answer. Please try again.'], 502);
            }

            $this->recordConversation($clientId, 'user', $validated['message'], true);
            $this->recordConversation($clientId, 'assistant', $message, true);

            return response()->json(['message' => $message]);
        } catch (\Throwable $exception) {
            Log::warning('DigitalOcean BI inference request threw an exception.', [
                'client_id' => $clientId,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'AI Insights is temporarily unavailable. Please try again shortly.'], 502);
        }
    }

    public function salesForecast(Request $request): JsonResponse
    {
        $days = match ($request->string('range')->toString()) {
            '1m' => 30,
            '1y' => 365,
            default => 7,
        };
        $clientId = $this->clientId($request);
        $rows = $this->query('finance', 'invoice', $clientId, 'nexora_client_id')
            ?->whereDate('issue_date', '>=', now()->subDays($days - 1))
            ->selectRaw('DATE(issue_date) as day, COALESCE(SUM(invoice_amount), 0) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day') ?? collect();

        $labels = [];
        $sales = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $labels[] = $days === 365 ? now()->subDays($i)->format('M') : now()->subDays($i)->format('M d');
            $sales[] = (float) ($rows->get($day)->total ?? 0);
        }

        return response()->json(compact('labels', 'sales'));
    }

    public function departmentData(Request $request, string $department): JsonResponse
    {
        $clientId = $this->clientId($request);

        return response()->json($this->departmentSummary($department, $clientId));
    }

    public function liveFeed(Request $request): JsonResponse
    {
        $clientId = $this->clientId($request);
        $metrics = $this->metrics($clientId);
        $alerts = [];

        if ($metrics['inventory_low_stock'] > 0) {
            $alerts[] = $this->alert('critical', 'Inventory', 'Low stock requires attention', $metrics['inventory_low_stock'].' stock records are at or below their reorder threshold.', $metrics['inventory_low_stock']);
        }
        if ($metrics['finance_overdue'] > 0) {
            $alerts[] = $this->alert('warning', 'Finance', 'Overdue invoices', $metrics['finance_overdue'].' invoices are overdue.', $metrics['finance_overdue']);
        }
        if ($metrics['procurement_open'] > 0) {
            $alerts[] = $this->alert('info', 'Procurement', 'Open purchase orders', $metrics['procurement_open'].' purchase orders remain active.', $metrics['procurement_open']);
        }
        if ($metrics['manufacturing_active'] > 0) {
            $alerts[] = $this->alert('info', 'Manufacturing', 'Active work orders', $metrics['manufacturing_active'].' work orders are in progress.', $metrics['manufacturing_active']);
        }
        if ($metrics['fulfillment_delayed'] > 0) {
            $alerts[] = $this->alert('warning', 'Fulfillment', 'Delayed shipments', $metrics['fulfillment_delayed'].' shipments are past their due date.', $metrics['fulfillment_delayed']);
        }

        return response()->json([
            'alerts' => $alerts,
            'summary' => [
                'critical' => collect($alerts)->where('severity', 'critical')->count(),
                'warning' => collect($alerts)->where('severity', 'warning')->count(),
                'info' => collect($alerts)->where('severity', 'info')->count(),
            ],
        ]);
    }

    private function metrics(?int $clientId): array
    {
        $revenue = $this->sum('finance', 'invoice', 'paid_amount', $clientId, 'nexora_client_id');
        $invoiced = $this->sum('finance', 'invoice', 'invoice_amount', $clientId, 'nexora_client_id');
        $expenses = $this->sum('finance', 'expenses', 'total_expenses', $clientId, 'nexora_client_id');

        return [
            'revenue' => $revenue,
            'invoiced' => $invoiced,
            'expenses' => $expenses,
            'profit' => $revenue - $expenses,
            'finance_overdue' => $this->countWhere('finance', 'invoice', $clientId, 'nexora_client_id', 'status', 'Overdue'),
            'inventory_items' => $this->count('inventory', 'items', $clientId),
            'inventory_low_stock' => $this->lowStockCount($clientId),
            'procurement_open' => $this->openPurchaseOrders($clientId),
            'manufacturing_active' => $this->activeWorkOrders($clientId),
            'fulfillment_orders' => $this->count('order_fulfillment', 'orders', $clientId),
            'fulfillment_delayed' => $this->delayedShipments($clientId),
            'ecommerce_products' => $this->firstCount('ecommerce', ['products', 'prebuilt_configs', 'configurator_configs'], $clientId),
        ];
    }

    private function departmentSummary(string $department, ?int $clientId): array
    {
        $metrics = $this->metrics($clientId);

        return match ($department) {
            'finance' => [
                'title' => 'Finance & Accounting',
                'stats' => [['label' => 'Revenue', 'value' => $metrics['revenue']], ['label' => 'Expenses', 'value' => $metrics['expenses']], ['label' => 'Overdue', 'value' => $metrics['finance_overdue']]],
                'chart1' => ['type' => 'line', 'label' => 'Invoice revenue', 'data' => $this->financeTrend($clientId)],
                'chart2' => ['type' => 'bar', 'label' => 'Invoice status', 'data' => $this->statusBreakdown('finance', 'invoice', $clientId, 'nexora_client_id')],
            ],
            'inventory' => [
                'title' => 'Inventory & Warehouse',
                'stats' => [['label' => 'Items', 'value' => $metrics['inventory_items']], ['label' => 'Low stock', 'value' => $metrics['inventory_low_stock']]],
                'chart1' => ['type' => 'bar', 'label' => 'Items by category', 'data' => $this->groupCount('inventory', 'items', 'category_id', $clientId)],
                'chart2' => ['type' => 'bar', 'label' => 'Stock alerts', 'data' => [['label' => 'Low stock', 'value' => $metrics['inventory_low_stock']]]],
            ],
            'procurement' => [
                'title' => 'Procurement',
                'stats' => [['label' => 'Open purchase orders', 'value' => $metrics['procurement_open']]],
                'chart1' => ['type' => 'doughnut', 'label' => 'Purchase order status', 'data' => $this->statusBreakdown('procurement', 'purchase_orders', $clientId)],
                'chart2' => ['type' => 'bar', 'label' => 'Open orders', 'data' => [['label' => 'Open', 'value' => $metrics['procurement_open']]]],
            ],
            'manufacturing' => [
                'title' => 'Manufacturing',
                'stats' => [['label' => 'Active work orders', 'value' => $metrics['manufacturing_active']]],
                'chart1' => ['type' => 'doughnut', 'label' => 'Work order status', 'data' => $this->statusBreakdown('manufacturing', 'work_orders', $clientId)],
                'chart2' => ['type' => 'bar', 'label' => 'Work orders in progress', 'data' => [['label' => 'Active', 'value' => $metrics['manufacturing_active']]]],
            ],
            'fulfillment' => [
                'title' => 'Order Fulfillment',
                'stats' => [['label' => 'Orders', 'value' => $metrics['fulfillment_orders']], ['label' => 'Delayed shipments', 'value' => $metrics['fulfillment_delayed']]],
                'chart1' => ['type' => 'doughnut', 'label' => 'Order status', 'data' => $this->statusBreakdown('order_fulfillment', 'orders', $clientId)],
                'chart2' => ['type' => 'bar', 'label' => 'Shipment risk', 'data' => [['label' => 'Delayed', 'value' => $metrics['fulfillment_delayed']]]],
            ],
            default => [
                'title' => 'E-commerce & CRM',
                'stats' => [['label' => 'Catalog records', 'value' => $metrics['ecommerce_products']]],
                'chart1' => ['type' => 'bar', 'label' => 'Catalog records', 'data' => [['label' => 'Products', 'value' => $metrics['ecommerce_products']]]],
                'chart2' => ['type' => 'bar', 'label' => 'Status', 'data' => []],
            ],
        };
    }

    private function clientId(Request $request): ?int
    {
        if (session('employee_client_id')) {
            return (int) session('employee_client_id');
        }

        if (config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin') {
            return $request->integer('client_id') ?: null;
        }

        return null;
    }

    private function query(string $connection, string $table, ?int $clientId, string $tenantColumn = 'client_id'): ?Builder
    {
        try {
            $schema = Schema::connection($connection);
            if (! $clientId || ! $schema->hasTable($table) || ! $schema->hasColumn($table, $tenantColumn)) {
                return null;
            }

            return DB::connection($connection)->table($table)->where($tenantColumn, $clientId);
        } catch (\Throwable) {
            return null;
        }
    }

    private function count(string $connection, string $table, ?int $clientId, string $tenantColumn = 'client_id'): int
    {
        try {
            return (int) ($this->query($connection, $table, $clientId, $tenantColumn)?->count() ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function firstCount(string $connection, array $tables, ?int $clientId): int
    {
        foreach ($tables as $table) {
            $count = $this->count($connection, $table, $clientId);
            if ($count > 0 || $this->query($connection, $table, $clientId)) {
                return $count;
            }
        }

        return 0;
    }

    private function sum(string $connection, string $table, string $column, ?int $clientId, string $tenantColumn = 'client_id'): float
    {
        try {
            if (! Schema::connection($connection)->hasColumn($table, $column)) {
                return 0.0;
            }
            return (float) ($this->query($connection, $table, $clientId, $tenantColumn)?->sum($column) ?? 0);
        } catch (\Throwable) {
            return 0.0;
        }
    }

    private function countWhere(string $connection, string $table, ?int $clientId, string $tenantColumn, string $column, mixed $value): int
    {
        try {
            if (! Schema::connection($connection)->hasColumn($table, $column)) {
                return 0;
            }
            return (int) ($this->query($connection, $table, $clientId, $tenantColumn)?->where($column, $value)->count() ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function lowStockCount(?int $clientId): int
    {
        try {
            $query = $this->query('inventory', 'stock_levels', $clientId);
            return (int) ($query?->where(function (Builder $query): void {
                $query->whereColumn('stock', '<=', 'reserved_quantity')
                    ->orWhere(function (Builder $query): void {
                        $query->where('reorder_threshold', '>', 0)
                            ->whereRaw('(stock - reserved_quantity) <= reorder_threshold');
                    });
            })->count() ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function openPurchaseOrders(?int $clientId): int
    {
        try {
            return (int) ($this->query('procurement', 'purchase_orders', $clientId)
                ?->whereNotIn('status', ['received', 'cancelled', 'closed'])->count() ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function activeWorkOrders(?int $clientId): int
    {
        try {
            return (int) ($this->query('manufacturing', 'work_orders', $clientId)
                ?->whereNotIn('status', ['Finished', 'Cancelled', 'completed', 'cancelled'])->count() ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function delayedShipments(?int $clientId): int
    {
        try {
            return (int) ($this->query('order_fulfillment', 'shipments', $clientId)
                ?->whereDate('due_date', '<', today())
                ->whereNotIn('status', ['Delivered', 'Completed', 'delivered', 'completed'])->count() ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function groupCount(string $connection, string $table, string $column, ?int $clientId): array
    {
        try {
            if (! Schema::connection($connection)->hasColumn($table, $column)) {
                return [];
            }
            return $this->query($connection, $table, $clientId)?->selectRaw("{$column} as label, COUNT(*) as value")
                ->groupBy($column)->orderByDesc('value')->limit(8)->get()->map(fn ($row) => ['label' => (string) ($row->label ?? 'Unassigned'), 'value' => (int) $row->value])->all() ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function statusBreakdown(string $connection, string $table, ?int $clientId, string $tenantColumn = 'client_id'): array
    {
        try {
            if (! Schema::connection($connection)->hasColumn($table, 'status')) {
                return [];
            }
            return $this->query($connection, $table, $clientId, $tenantColumn)?->selectRaw('status as label, COUNT(*) as value')
                ->groupBy('status')->orderByDesc('value')->get()->map(fn ($row) => ['label' => (string) ($row->label ?? 'Unspecified'), 'value' => (int) $row->value])->all() ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function financeTrend(?int $clientId): array
    {
        try {
            return $this->query('finance', 'invoice', $clientId, 'nexora_client_id')?->whereDate('issue_date', '>=', now()->subDays(6))
                ->selectRaw('DATE(issue_date) as label, COALESCE(SUM(invoice_amount), 0) as value')
                ->groupBy('label')->orderBy('label')->get()->map(fn ($row) => ['label' => $row->label, 'value' => (float) $row->value])->all() ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function alert(string $severity, string $department, string $title, string $description, int $value): array
    {
        return compact('severity', 'department', 'title', 'description', 'value') + ['timestamp' => now()->toIso8601String()];
    }

    private function recordSnapshot(?int $clientId, array $metrics): void
    {
        if (! $clientId || ! config('database.connections.business_intelligence.url')) {
            return;
        }

        try {
            if (! Schema::connection('business_intelligence')->hasTable('bi_snapshots')) {
                return;
            }

            DB::connection('business_intelligence')->table('bi_snapshots')->updateOrInsert(
                ['client_id' => $clientId, 'source' => 'live-dashboard'],
                ['payload' => json_encode($metrics, JSON_THROW_ON_ERROR), 'captured_at' => now(), 'updated_at' => now()]
            );
        } catch (\Throwable) {
            // BI must remain read-only and available when its optional
            // snapshot store is temporarily unavailable.
        }
    }

    private function recordConversation(int $clientId, string $role, string $message, bool $usedAi): void
    {
        if (! config('database.connections.business_intelligence.url')) {
            return;
        }

        try {
            if (! Schema::connection('business_intelligence')->hasTable('bi_ai_conversations')) {
                return;
            }

            DB::connection('business_intelligence')->table('bi_ai_conversations')->insert([
                'client_id' => $clientId,
                'employee_id' => session('employee_id'),
                'role' => $role,
                'message' => $message,
                'used_ai' => $usedAi,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // Conversation auditing must not prevent the client from using BI.
        }
    }
}
