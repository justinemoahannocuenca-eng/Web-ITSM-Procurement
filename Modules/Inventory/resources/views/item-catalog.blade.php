@extends('inventory::layouts.dashboard')

@section('title', 'Item Catalog')

@push('styles')
    <style>
        .expand-row { display: none; }
        .expand-row.open { display: table-row; }
        .expand-toggle { cursor: pointer; transition: transform 0.2s ease; display: inline-block; }
        .expand-toggle.open { transform: rotate(90deg); }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 600; text-transform: uppercase; }
        .status-In.Stock { background: #dcfce7; color: #166534; }
        .status-Low.Stock { background: #fef9c3; color: #854d0e; }
        .status-Out.of.Stock { background: #fee2e2; color: #991b1b; }

        /* Expandable sub-table animation */
        .expand-row {
            display: none;
            opacity: 0;
            transform: translateY(-6px);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .expand-row.open {
            display: table-row;
            opacity: 1;
            transform: translateY(0);
        }
    </style>
@endpush

@section('content')
    @if(session('success'))
        <div style="margin-bottom:16px;padding:12px 16px;background:rgba(34,197,94,0.15);color:#22c55e;border-radius:10px;font-weight:600;">
            {{ session('success') }}
        </div>
    @endif

    <!-- Stat Cards Row -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px;">
        <div style="background:#0b1e3d;padding:20px;border-radius:20px;">
            <p style="font-size:15px;color:#94a3b8;">In Stock</p>
            <p style="font-size:40px;font-weight:bold;color:#fff;">{{ $inStockCount }}</p>
        </div>
        <div style="background:#0b1e3d;padding:20px;border-radius:20px;">
            <p style="font-size:15px;color:#94a3b8;">Low Stock</p>
            <p style="font-size:40px;font-weight:bold;color:#fff;">{{ $lowStockCount }}</p>
        </div>
        <div style="background:#0b1e3d;padding:20px;border-radius:20px;">
            <p style="font-size:15px;color:#94a3b8;">Out of Stock</p>
            <p style="font-size:40px;font-weight:bold;color:#fff;">{{ $outOfStockCount }}</p>
        </div>
    </div>

<!-- Inventory Items Card -->
<div style="background:#ffffff;border-radius:20px;overflow:hidden;min-width:0;">
    <form method="GET" action="{{ route('inventory.item-catalog') }}" style="padding:20px 24px 16px 24px;display:flex;align-items:center;gap:12px;flex-wrap:nowrap;min-width:0;">
        <div style="display:flex;gap:4px;background:#e2e8f0;border-radius:8px;padding:3px;flex-shrink:0;">
            <button type="button" id="catTabItems" onclick="switchCatTab('items')" style="padding:6px 16px;border:none;border-radius:6px;font-size:12px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;background:#0b1e3d;color:#fff;">Items</button>
            <button type="button" id="catTabPacking" onclick="switchCatTab('packing')" style="padding:6px 16px;border:none;border-radius:6px;font-size:12px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;background:transparent;color:#64748b;">Packing</button>
        </div>
        <div id="itemsFilters" style="display:flex;align-items:center;gap:12px;flex:1;min-width:0;">
            <div style="display:flex;align-items:center;background:#E2E8F0;border-radius:8px;padding:8px 14px;gap:8px;flex:1;min-width:150px;">
                <svg width="16" height="16" fill="none" stroke="#64748b" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Name, Category..." style="border:none;outline:none;background:transparent;font-size:13px;font-family:'Inter',sans-serif;color:#333;width:100%;">
            </div>
            <select name="category" onchange="this.form.submit()" style="background:#E2E8F0;color:#000;border:none;border-radius:20px;padding:8px 16px;font-size:13px;font-family:'Inter',sans-serif;cursor:pointer;outline:none;flex-shrink:0;">
                <option value="">All Categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="warehouse" onchange="this.form.submit()" style="background:#E2E8F0;color:#000;border:none;border-radius:20px;padding:8px 16px;font-size:13px;font-family:'Inter',sans-serif;cursor:pointer;outline:none;flex-shrink:0;">
                <option value="">All Warehouse</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" {{ request('warehouse') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()" style="background:#E2E8F0;color:#000;border:none;border-radius:20px;padding:8px 16px;font-size:13px;font-family:'Inter',sans-serif;cursor:pointer;outline:none;flex-shrink:0;">
                <option value="">All Status</option>
                <option value="In Stock" {{ request('status') === 'In Stock' ? 'selected' : '' }}>In Stock</option>
                <option value="Low Stock" {{ request('status') === 'Low Stock' ? 'selected' : '' }}>Low Stock</option>
                <option value="Out of Stock" {{ request('status') === 'Out of Stock' ? 'selected' : '' }}>Out of Stock</option>
            </select>
            @if(request()->anyFilled(['search', 'category', 'warehouse', 'status']))
                <a href="{{ route('inventory.item-catalog') }}" style="background:transparent;color:#64748b;border:1px solid #cbd5e1;border-radius:20px;padding:8px 16px;font-size:13px;font-family:'Inter',sans-serif;text-decoration:none;display:inline-flex;align-items:center;gap:4px;white-space:nowrap;flex-shrink:0;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg> Clear
                </a>
            @endif
        </div>
    </form>

    <!-- Items Table -->
    <div id="itemsTableSection">
        <div class="responsive-table" style="min-width:0;">
            <table class="stock-table" style="width:100%;table-layout:fixed;border-collapse:collapse;min-width:820px;">
                <thead>
                    <tr style="background:#1b3a6b;">
                        <th style="width:30px;padding:12px 4px;"></th>
                        <th style="text-align:center;padding:12px 4px;color:#fff;font-size:12px;font-weight:600;">SKU</th>
                        <th style="text-align:center;padding:12px 4px;color:#fff;font-size:12px;font-weight:600;">ITEM NAME</th>
                        <th style="text-align:center;padding:12px 4px;color:#fff;font-size:12px;font-weight:600;">CATEGORY</th>
                        <th style="text-align:center;padding:12px 4px;color:#fff;font-size:12px;font-weight:600;">AVAILABLE</th>
                        <th style="text-align:center;padding:12px 4px;color:#fff;font-size:12px;font-weight:600;">UNIT COST</th>
                        <th style="text-align:center;padding:12px 4px;color:#fff;font-size:12px;font-weight:600;">STATUS</th>
                        <th style="text-align:center;padding:12px 4px;color:#fff;font-size:12px;font-weight:600;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                    <tr style="background:{{ $loop->even ? '#F4F6FA' : '#ffffff' }}; border-top:1px solid #E2E8F0; cursor:pointer;" onclick="toggleExpand({{ $loop->index }})">
                        <td style="text-align:center;padding:12px 4px;">
                            <span class="expand-toggle" id="toggle-{{ $loop->index }}">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="#64748b"><path d="M8 5v14l11-7z"/></svg>
                            </span>
                        </td>
                        <td style="text-align:center;padding:12px 4px;font-size:13px;color:#132B52;">{{ $item['sku'] ?? 'â€”' }}</td>
                        <td style="text-align:center;padding:12px 4px;font-size:13px;color:#132B52;">{{ $item['name'] }}</td>
                        <td style="text-align:center;padding:12px 4px;font-size:13px;color:#5B7A9D;">{{ $item['category'] }}</td>
                        <td style="text-align:center;padding:12px 4px;font-size:13px;color:#132B52;font-weight:600;">{{ $item['total_stock'] }}</td>
                        <td style="text-align:center;padding:12px 4px;font-size:13px;color:#132B52;">&#8369;{{ number_format($item['unit_cost'] ?? 0, 2) }}</td>
                        <td style="text-align:center;padding:12px 8px;">
                            @php
                                $badgeColors = ['In Stock' => ['bg'=>'#DCFCE7','text'=>'#16A34A'], 'Low Stock' => ['bg'=>'#FEF3C7','text'=>'#D97706'], 'Out of Stock' => ['bg'=>'#FEE2E2','text'=>'#DC2626']];
                                $colors = $badgeColors[$item['status']] ?? ['bg'=>'#e2e8f0','text'=>'#64748b'];
                            @endphp
                            <span style="background:{{ $colors['bg'] }};color:{{ $colors['text'] }};font-size:11px;font-weight:600;padding:4px 12px;border-radius:20px;">{{ $item['status'] }}</span>
                        </td>
                        <td style="text-align:center;padding:12px 4px;">
                            <form method="POST" action="{{ route('inventory.item-catalog.destroy', $item['id']) }}" onsubmit="return confirm('Delete this item permanently?')" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none;border:none;cursor:pointer;color:#dc2626;font-size:13px;padding:4px 8px;">ðŸ—‘</button>
                            </form>
                        </td>
                    </tr>
                    <tr class="expand-row" id="expand-{{ $loop->index }}">
                        <td colspan="8" style="padding:0 16px 16px 40px;background:#f8fafc;">
                            <table style="width:100%;border-collapse:collapse;margin-top:4px;">
                                <thead>
                                    <tr style="border-bottom:2px solid #e2e8f0;">
                                        <th style="text-align:left;padding:8px 10px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;">Warehouse</th>
                                        <th style="text-align:center;padding:8px 10px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;">Available</th>
                                        <th style="text-align:center;padding:8px 10px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;">Reserved</th>
                                        <th style="text-align:center;padding:8px 10px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;">Reorder Threshold</th>
                                        <th style="text-align:center;padding:8px 10px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($item['stock_breakdown'] ?? [] as $row)
                                    <tr style="border-bottom:1px solid #e2e8f0;background:#ffffff;">
                                        <td style="padding:8px 10px;font-size:12px;color:#0f172a;">{{ $row['warehouse'] }}</td>
                                        <td style="text-align:center;padding:8px 10px;font-size:12px;color:#0f172a;font-weight:600;">{{ $row['on_hand'] }}</td>
                                        <td style="text-align:center;padding:8px 10px;font-size:12px;color:#dc2626;">{{ $row['reserved'] }}</td>
                                        <td style="padding:6px 10px;" onclick="event.stopPropagation()">
                                            <form method="POST" action="{{ route('inventory.stock-levels.update', $row['stock_level_id']) }}" style="display:inline-flex;align-items:center;gap:4px;">
                                                @csrf @method('PATCH')
                                                <input type="number" name="reorder_threshold" value="{{ $row['reorder_threshold'] }}" min="0" style="min-width:48px;width:calc({{ max(1, strlen((string) $row['reorder_threshold'])) }}ch + 22px);padding:5px 8px;background:#fff;color:#0f172a;border:1px solid #94a3b8;border-radius:6px;font-size:12px;text-align:center;outline:none;">
                                                <button type="submit" style="background:#1b6fc8;color:#fff;border:none;border-radius:4px;padding:3px 6px;font-size:10px;cursor:pointer;font-weight:600;">Save</button>
                                            </form>
                                        </td>
                                        <td style="text-align:center;padding:8px 10px;">
                                            @php $slColors = ['In Stock'=>['bg'=>'#dcfce7','text'=>'#166534'],'Low Stock'=>['bg'=>'#fef9c3','text'=>'#854d0e'],'Out of Stock'=>['bg'=>'#fee2e2','text'=>'#991b1b']]; $slc = $slColors[$row['status']] ?? ['bg'=>'#e2e8f0','text'=>'#64748b']; @endphp
                                            <span style="background:{{ $slc['bg'] }};color:{{ $slc['text'] }};font-size:10px;font-weight:600;padding:3px 8px;border-radius:20px;">{{ $row['status'] }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" style="text-align:center;padding:12px;color:#94a3b8;font-size:12px;">No stock records.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" style="text-align:center;padding:32px;color:#94a3b8;font-size:13px;">No items found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>

    <!-- Packing Materials Table -->
    <div id="packingTableSection" style="display:none;">
        <div class="responsive-table" style="min-width:0;">
            <table class="stock-table" style="width:100%;table-layout:auto;border-collapse:collapse;">
                <thead>
                    <tr style="background:#1b3a6b;">
                        <th style="text-align:center;padding:12px 6px;color:#fff;font-size:12px;font-weight:600;">NAME</th>
                        <th style="text-align:center;padding:12px 6px;color:#fff;font-size:12px;font-weight:600;">STOCK QTY</th>
                        <th style="text-align:center;padding:12px 6px;color:#fff;font-size:12px;font-weight:600;">LOW STOCK AT</th>
                        <th style="text-align:center;padding:12px 6px;color:#fff;font-size:12px;font-weight:600;">TYPE</th>
                        <th style="text-align:center;padding:12px 6px;color:#fff;font-size:12px;font-weight:600;">BOX SIZE</th>
                        <th style="text-align:center;padding:12px 6px;color:#fff;font-size:12px;font-weight:600;">STATUS</th>
                        <th style="text-align:center;padding:12px 6px;color:#fff;font-size:12px;font-weight:600;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($packingMaterials as $pm)
                    <tr style="border-bottom:1px solid #e2e8f0;background:{{ $loop->even ? '#F4F6FA' : '#fff' }};">
                        <td style="text-align:center;padding:12px 6px;font-size:13px;color:#132B52;font-weight:500;">{{ $pm->name }}</td>
                        <td style="text-align:center;padding:12px 6px;font-size:13px;color:#132B52;font-weight:600;">{{ $pm->stock_qty }}</td>
                        <td style="text-align:center;padding:12px 6px;font-size:13px;color:#5B7A9D;">{{ $pm->low_stock_threshold }}</td>
                        <td style="text-align:center;padding:12px 6px;font-size:13px;">
                            <span style="background:{{ $pm->is_box ? '#dbeafe' : '#e2e8f0' }};color:{{ $pm->is_box ? '#1e40af' : '#64748b' }};padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;">{{ $pm->is_box ? 'Box' : 'Material' }}</span>
                        </td>
                        <td style="text-align:center;padding:12px 6px;font-size:13px;color:#5B7A9D;">{{ $pm->box_size ?? 'â€”' }}</td>
                        <td style="text-align:center;padding:12px 6px;">
                            @php $pmStatus = $pm->stock_qty <= 0 ? 'Out of Stock' : ($pm->stock_qty <= $pm->low_stock_threshold ? 'Low Stock' : 'In Stock'); $pmColors = ['In Stock'=>['bg'=>'#dcfce7','text'=>'#166534'],'Low Stock'=>['bg'=>'#fef9c3','text'=>'#854d0e'],'Out of Stock'=>['bg'=>'#fee2e2','text'=>'#991b1b']]; $pc = $pmColors[$pmStatus]; @endphp
                            <span style="background:{{ $pc['bg'] }};color:{{ $pc['text'] }};font-size:11px;font-weight:600;padding:4px 12px;border-radius:20px;">{{ $pmStatus }}</span>
                        </td>
                        <td style="text-align:center;padding:12px 6px;">
                            <form method="POST" action="{{ route('inventory.item-catalog.packing.destroy', $pm->id) }}" onsubmit="return confirm('Delete this packing material?')" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none;border:none;cursor:pointer;color:#dc2626;font-size:13px;padding:4px 8px;">ðŸ—‘</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="text-align:center;padding:32px;color:#94a3b8;font-size:13px;">No packing materials yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleExpand(index) {
        document.getElementById('expand-' + index).classList.toggle('open');
        document.getElementById('toggle-' + index).classList.toggle('open');
    }

    function switchCatTab(tab) {
        const tabItems = document.getElementById('catTabItems');
        const tabPacking = document.getElementById('catTabPacking');
        const itemsSection = document.getElementById('itemsTableSection');
        const packingSection = document.getElementById('packingTableSection');
        const itemsFilters = document.getElementById('itemsFilters');

        if (tab === 'items') {
            tabItems.style.background = '#0b1e3d'; tabItems.style.color = '#fff';
            tabPacking.style.background = 'transparent'; tabPacking.style.color = '#64748b';
            itemsSection.style.display = 'block'; packingSection.style.display = 'none';
            itemsFilters.style.display = 'flex';
        } else {
            tabPacking.style.background = '#0b1e3d'; tabPacking.style.color = '#fff';
            tabItems.style.background = 'transparent'; tabItems.style.color = '#64748b';
            packingSection.style.display = 'block'; itemsSection.style.display = 'none';
            itemsFilters.style.display = 'none';
        }
    }
</script>
@endpush

