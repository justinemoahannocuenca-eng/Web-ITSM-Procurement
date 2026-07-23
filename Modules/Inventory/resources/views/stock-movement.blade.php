@extends('inventory::layouts.dashboard')

@section('title', 'Stock Movement')

@section('content')
    <!-- Stat Cards Row -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px;">
        <div style="background:#0b1e3d;padding:20px;border-radius:20px;">
            <p style="font-size:15px;color:#94a3b8;">Total Inbound</p>
            <p style="font-size:40px;font-weight:bold;color:#fff;">{{ number_format($totals['inbound']) }}</p>
        </div>
        <div style="background:#0b1e3d;padding:20px;border-radius:20px;">
            <p style="font-size:15px;color:#94a3b8;">Total Outbound</p>
            <p style="font-size:40px;font-weight:bold;color:#fff;">{{ number_format(abs($totals['outbound'])) }}</p>
        </div>
        <div style="background:#0b1e3d;padding:20px;border-radius:20px;">
            <p style="font-size:15px;color:#94a3b8;">Transfer</p>
            <p style="font-size:40px;font-weight:bold;color:#fff;">{{ number_format($totals['transfer']) }}</p>
        </div>
        <div style="background:#0b1e3d;padding:20px;border-radius:20px;">
            <p style="font-size:15px;color:#94a3b8;">Net Change</p>
            <p style="font-size:40px;font-weight:bold;color:{{ $totals['net'] >= 0 ? '#22c55e' : '#ef4444' }};">{{ ($totals['net'] > 0 ? '+' : '') . number_format($totals['net']) }}</p>
        </div>
    </div>

    <!-- Table Card -->
    <div style="background:#ffffff;border-radius:20px;overflow:hidden;min-width:0;">
        <form method="GET" action="{{ route('inventory.stock-movement') }}" id="filters-form">
            <!-- Filters Row -->
            <div style="padding:16px 20px;display:flex;align-items:center;gap:12px;flex-wrap:nowrap;min-width:0;">
                <!-- Search -->
                <div style="display:flex;align-items:center;background:#E2E8F0;border-radius:8px;padding:8px 14px;gap:8px;flex:1;min-width:150px;">
                    <svg width="16" height="16" fill="none" stroke="#64748b" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Name, Reference..." style="border:none;outline:none;background:transparent;font-size:12px;font-family:'Inter',sans-serif;color:#333;width:100%;">
                </div>
                <!-- Filter: Type -->
                <select name="type" onchange="document.getElementById('filters-form').submit();" style="background:#E2E8F0;color:#000;border:none;border-radius:20px;padding:8px 16px;font-size:13px;font-family:'Inter',sans-serif;cursor:pointer;outline:none;flex-shrink:0;">
                    <option value="">Type</option>
                    <option value="inbound" {{ request('type') === 'inbound' ? 'selected' : '' }}>Inbound</option>
                    <option value="outbound" {{ request('type') === 'outbound' ? 'selected' : '' }}>Outbound</option>
                    <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                    <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                </select>
                <!-- Filter: Warehouses -->
                <select name="warehouse" onchange="document.getElementById('filters-form').submit();" style="background:#E2E8F0;color:#000;border:none;border-radius:20px;padding:8px 16px;font-size:13px;font-family:'Inter',sans-serif;cursor:pointer;outline:none;flex-shrink:0;">
                    <option value="">Warehouses</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ data_get($warehouse, 'id') }}" {{ request('warehouse') == data_get($warehouse, 'id') ? 'selected' : '' }}>{{ data_get($warehouse, 'name') }}</option>
                @endforeach
                </select>
                <!-- Filter: Date Range -->
                <select name="date_range" onchange="document.getElementById('filters-form').submit();" style="background:#E2E8F0;color:#000;border:none;border-radius:20px;padding:8px 16px;font-size:13px;font-family:'Inter',sans-serif;cursor:pointer;outline:none;flex-shrink:0;">
                    <option value="">Date Range</option>
                    <option value="today" {{ request('date_range') === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="this_week" {{ request('date_range') === 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="this_month" {{ request('date_range') === 'this_month' ? 'selected' : '' }}>This Month</option>
                </select>
                <!-- Clear Filters -->
                @if(request()->anyFilled(['search', 'type', 'warehouse', 'date_range', 'reference']))
                    <a href="{{ route('inventory.stock-movement') }}" style="background:transparent;color:#64748b;border:1px solid #cbd5e1;border-radius:20px;padding:8px 16px;font-size:13px;font-family:'Inter',sans-serif;text-decoration:none;display:inline-flex;align-items:center;gap:4px;white-space:nowrap;flex-shrink:0;" title="Clear all filters">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        Clear
                    </a>
                @endif
            </div>
        </form>
        <!-- Table -->
        <div class="responsive-table" style="min-width:0;">
            <table class="stock-table" style="width:100%;table-layout:auto;border-collapse:collapse;">
                <thead>
                    <tr style="background:#1b3a6b;">
                        <th data-sort="type" style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">TYPE <span class="sort-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 3.5l-6.5 7h13L12 3.5z"/><path d="M12 20.5l6.5-7h-13l6.5 7z"/></svg></span></th>
                        <th data-sort="item_name" style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">ITEM NAME <span class="sort-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 3.5l-6.5 7h13L12 3.5z"/><path d="M12 20.5l6.5-7h-13l6.5 7z"/></svg></span></th>
                        <th data-sort="sku" style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">SKU <span class="sort-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 3.5l-6.5 7h13L12 3.5z"/><path d="M12 20.5l6.5-7h-13l6.5 7z"/></svg></span></th>
                        <th data-sort="quantity" style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">QUANTITY <span class="sort-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 3.5l-6.5 7h13L12 3.5z"/><path d="M12 20.5l6.5-7h-13l6.5 7z"/></svg></span></th>
                        <th data-sort="warehouse" style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">WAREHOUSE <span class="sort-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 3.5l-6.5 7h13L12 3.5z"/><path d="M12 20.5l6.5-7h-13l6.5 7z"/></svg></span></th>
                        <th data-sort="reference" style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">REFERENCE <span class="sort-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 3.5l-6.5 7h13L12 3.5z"/><path d="M12 20.5l6.5-7h-13l6.5 7z"/></svg></span></th>
                        <th data-sort="performed_by" style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">PERFORMED BY <span class="sort-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 3.5l-6.5 7h13L12 3.5z"/><path d="M12 20.5l6.5-7h-13l6.5 7z"/></svg></span></th>
                        <th data-sort="created_at" style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">DATE AND TIME <span class="sort-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 3.5l-6.5 7h13L12 3.5z"/><path d="M12 20.5l6.5-7h-13l6.5 7z"/></svg></span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        <tr class="trow">
                            <td style="text-align:center;padding:10px 6px;color:#000;font-size:12px;">
                                @if ($movement->type === 'inbound')
                                    <span style="display:inline-block;padding:4px 14px;border-radius:14px;background:#F0FFF5;color:#0CAE57;border:1px solid rgba(12,174,87,0.5);font-size:11px;font-weight:500;">Inbound</span>
                                @elseif ($movement->type === 'outbound')
                                    <span style="display:inline-block;padding:4px 14px;border-radius:14px;background:#FFF5F5;color:#DC2626;border:1px solid rgba(220,38,38,0.5);font-size:11px;font-weight:500;">Outbound</span>
                                @elseif ($movement->type === 'adjustment')
                                    <span style="display:inline-block;padding:4px 14px;border-radius:14px;background:#FEF3C7;color:#D97706;border:1px solid rgba(217,119,6,0.5);font-size:11px;font-weight:500;">Adjustment</span>
                                @else
                                    <span style="display:inline-block;padding:4px 14px;border-radius:14px;background:#EFF6FF;color:#2563EB;border:1px solid rgba(37,99,235,0.5);font-size:11px;font-weight:500;">Transfer</span>
                                @endif
                            </td>
                            <td style="text-align:center;padding:10px 6px;color:#000;font-size:12px;">{{ $movement->item?->name ?? 'N/A' }}</td>
                            <td style="text-align:center;padding:10px 6px;color:#000;font-size:12px;">{{ $movement->item?->sku ?? 'â€”' }}</td>
                            <td style="text-align:center;padding:10px 6px;color:#000;font-size:12px;font-weight:600;">
                                @if ($movement->type === 'inbound')
                                    <span style="display:inline-flex;align-items:center;gap:4px;color:#0CAE57;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg>
                                        {{ number_format($movement->quantity) }}
                                    </span>
                                @elseif ($movement->type === 'outbound')
                                    <span style="display:inline-flex;align-items:center;gap:4px;color:#DC2626;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/></svg>
                                        {{ number_format($movement->quantity) }}
                                    </span>
                                @elseif ($movement->type === 'adjustment')
                                    @if ($movement->quantity >= 0)
                                        <span style="display:inline-flex;align-items:center;gap:4px;color:#0CAE57;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg>
                                            {{ number_format(abs($movement->quantity)) }}
                                        </span>
                                    @else
                                        <span style="display:inline-flex;align-items:center;gap:4px;color:#DC2626;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/></svg>
                                            {{ number_format(abs($movement->quantity)) }}
                                        </span>
                                    @endif
                                @else
                                    <span style="display:inline-flex;align-items:center;gap:4px;color:#2563EB;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8l4 4-4 4"/><path d="M7 16l-4-4 4-4"/></svg>
                                        {{ number_format($movement->quantity) }}
                                    </span>
                                @endif
                            </td>
                            <td style="text-align:center;padding:10px 6px;color:#000;font-size:12px;">
                                @if ($movement->type === 'transfer')
                                    {{ $movement->transfer_warehouses_display ? str_replace(' â‡„ ', ' â†’ ', $movement->transfer_warehouses_display) : 'N/A' }}
                                @else
                                    {{ $movement->warehouse?->name ?? 'N/A' }}
                                @endif
                            </td>
                            <td style="text-align:center;padding:10px 6px;color:#000;font-size:12px;">{{ $movement->reference ?? '-' }}</td>
                            <td style="text-align:center;padding:10px 6px;color:#000;font-size:12px;">{{ $movement->performer?->username ?? $movement->performer?->name ?? 'System' }}</td>
                            <td style="text-align:center;padding:10px 6px;color:#000;font-size:12px;">{{ $movement->created_at?->format('M d, Y h:i A') ?? 'â€”' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center;padding:20px;color:#64748b;font-size:13px;">No stock movements found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $movements->links() }}
    </div>
@endsection

