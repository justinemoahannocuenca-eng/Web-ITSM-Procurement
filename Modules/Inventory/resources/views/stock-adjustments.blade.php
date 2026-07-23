@extends('inventory::layouts.dashboard')

@section('title', 'Stock Adjustments')

@push('styles')
<style>
    .status-badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .status-pending { background: #fef9c3; color: #854d0e; }
    .status-approved { background: #dcfce7; color: #166534; }
    .status-rejected { background: #fee2e2; color: #991b1b; }
    .status-cancelled { background: #e2e8f0; color: #475569; }

    #adjustmentModal { opacity: 0; pointer-events: none; transition: opacity 0.2s ease; }
    #adjustmentModal.open { opacity: 1; pointer-events: auto; }

</style>
@endpush

@section('content')
    @if(session('success'))
        <div style="margin-bottom:16px;padding:12px 16px;background:rgba(34,197,94,0.15);color:#22c55e;border-radius:10px;font-weight:600;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="margin-bottom:16px;padding:12px 16px;background:rgba(239,68,68,0.15);color:#ef4444;border-radius:10px;font-weight:600;">
            {{ session('error') }}
        </div>
    @endif

    <!-- Stat Cards Row -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px;">
        <div style="background:#0b1e3d;padding:20px;border-radius:20px;">
            <p style="font-size:15px;color:#94a3b8;">Total Adjustments</p>
            <p style="font-size:40px;font-weight:bold;color:#fff;">{{ $totalCount }}</p>
        </div>
        <div style="background:#0b1e3d;padding:20px;border-radius:20px;">
            <p style="font-size:15px;color:#94a3b8;">Net Adjusted Units</p>
            <p style="font-size:40px;font-weight:bold;color:{{ $netAdjustedUnits >= 0 ? '#22c55e' : '#ef4444' }};">
                {{ ($netAdjustedUnits >= 0 ? '+' : '') . number_format($netAdjustedUnits) }}
            </p>
        </div>
        <div style="background:#0b1e3d;padding:20px;border-radius:20px;">
            <p style="font-size:15px;color:#94a3b8;">Pending Approval</p>
            <p style="font-size:40px;font-weight:bold;color:#fff;">{{ $pendingCount }}</p>
        </div>
    </div>

    <!-- Table Card -->
    <div style="background:#ffffff;border-radius:20px;overflow:hidden;min-width:0;">
        <!-- Filters Row -->
        <form method="GET" action="{{ route('inventory.stock-adjustments') }}" style="padding:16px 20px;display:flex;align-items:center;gap:12px;flex-wrap:nowrap;min-width:0;">
            <!-- Search -->
            <div style="display:flex;align-items:center;background:#E2E8F0;border-radius:8px;padding:8px 14px;gap:8px;flex:1;min-width:150px;">
                <svg width="16" height="16" fill="none" stroke="#64748b" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by Name..." style="border:none;outline:none;background:transparent;font-size:12px;font-family:'Inter',sans-serif;color:#333;width:100%;">
            </div>
            <!-- Filter: Type -->
            <select name="type" onchange="this.form.submit()" style="background:#E2E8F0;color:#000;border:none;border-radius:20px;padding:8px 16px;font-size:13px;font-family:'Inter',sans-serif;cursor:pointer;outline:none;flex-shrink:0;">
                <option value="">Type</option>
                <option value="increase" {{ ($filters['type'] ?? '') === 'increase' ? 'selected' : '' }}>Increase</option>
                <option value="decrease" {{ ($filters['type'] ?? '') === 'decrease' ? 'selected' : '' }}>Decrease</option>
            </select>
            <!-- Filter: Reason -->
            <select name="reason" onchange="this.form.submit()" style="background:#E2E8F0;color:#000;border:none;border-radius:20px;padding:8px 16px;font-size:13px;font-family:'Inter',sans-serif;cursor:pointer;outline:none;flex-shrink:0;">
                <option value="">Reason</option>
                <option value="damage" {{ ($filters['reason'] ?? '') === 'damage' ? 'selected' : '' }}>Damage</option>
                <option value="expired" {{ ($filters['reason'] ?? '') === 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="recount" {{ ($filters['reason'] ?? '') === 'recount' ? 'selected' : '' }}>Recount</option>
                <option value="theft" {{ ($filters['reason'] ?? '') === 'theft' ? 'selected' : '' }}>Theft</option>
                <option value="correction" {{ ($filters['reason'] ?? '') === 'correction' ? 'selected' : '' }}>Correction</option>
            </select>
            <!-- Filter: Warehouse -->
            <select name="warehouse" onchange="this.form.submit()" style="background:#E2E8F0;color:#000;border:none;border-radius:20px;padding:8px 16px;font-size:13px;font-family:'Inter',sans-serif;cursor:pointer;outline:none;flex-shrink:0;">
                <option value="">Warehouse</option>
                @foreach ($warehouses as $wh)
                    <option value="{{ $wh->id }}" {{ ($filters['warehouse'] ?? '') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                @endforeach
            </select>
            <!-- Filter: Status -->
            <select name="status" onchange="this.form.submit()" style="background:#E2E8F0;color:#000;border:none;border-radius:20px;padding:8px 16px;font-size:13px;font-family:'Inter',sans-serif;cursor:pointer;outline:none;flex-shrink:0;">
                <option value="">Status</option>
                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <!-- Clear Filters -->
            @if(array_filter($filters ?? []))
                <a href="{{ route('inventory.stock-adjustments') }}" style="background:transparent;color:#64748b;border:1px solid #cbd5e1;border-radius:20px;padding:8px 16px;font-size:13px;font-family:'Inter',sans-serif;text-decoration:none;display:inline-flex;align-items:center;gap:4px;white-space:nowrap;flex-shrink:0;" title="Clear all filters">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear
                </a>
            @endif
            <!-- + New Adjustment Button -->
            <button type="button" onclick="openAdjustmentModal()" style="background:#1b6fc8;color:#fff;border:none;border-radius:20px;padding:8px 20px;font-size:13px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap;flex-shrink:0;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                New Adjustment
            </button>
        </form>

        <!-- Table -->
        <div class="responsive-table" style="min-width:0;">
            <table class="stock-table" style="width:100%;table-layout:auto;border-collapse:collapse;">
                <thead>
                    <tr style="background:#1b3a6b;">
                        <th style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">ADJ.ID</th>
                        <th style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">ITEM NAME</th>
                        <th style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">SKU</th>
                        <th style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">WAREHOUSE</th>
                        <th style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">QUANTITY</th>
                        <th style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">TYPE</th>
                        <th style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">REASON</th>
                        <th style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">STATUS</th>
                        <th style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">APPROVED BY</th>
                        <th style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">DATE</th>
                        <th style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($adjustments as $adjustment)
                        <tr style="border-bottom:1px solid #e2e8f0;">
                            <td style="text-align:center;padding:12px 8px;font-size:13px;color:#132B52;">{{ $adjustment->id }}</td>
                            <td style="text-align:center;padding:12px 8px;font-size:13px;color:#132B52;">{{ $adjustment->item?->name ?? 'Deleted' }}</td>
                            <td style="text-align:center;padding:12px 8px;font-size:13px;color:#5B7A9D;">{{ $adjustment->item?->sku ?? 'â€”' }}</td>
                            <td style="text-align:center;padding:12px 8px;font-size:13px;color:#5B7A9D;">{{ $adjustment->warehouse?->name ?? 'Deleted' }}</td>
                            <td style="text-align:center;padding:12px 8px;font-size:13px;color:#132B52;font-weight:600;">{{ $adjustment->quantity }}</td>
                            <td style="text-align:center;padding:12px 8px;font-size:13px;color:#132B52;">{{ ucfirst($adjustment->type) }}</td>
                            <td style="text-align:center;padding:12px 8px;font-size:13px;color:#132B52;">{{ ucfirst($adjustment->reason) }}</td>
                            <td style="text-align:center;padding:12px 8px;">
                                <span class="status-badge status-{{ $adjustment->status }}">{{ ucfirst($adjustment->status) }}</span>
                            </td>
                            <td style="text-align:center;padding:12px 8px;font-size:13px;color:#5B7A9D;">{{ $adjustment->approver?->name ?? 'â€”' }}</td>
                            <td style="text-align:center;padding:12px 8px;font-size:13px;color:#5B7A9D;">{{ $adjustment->created_at?->format('M d, Y') ?? 'â€”' }}</td>
                            <td style="text-align:center;padding:12px 8px;">
                                @error("adj_action_{$adjustment->id}")
                                    <p style="color:#ef4444;font-size:11px;margin:0 0 6px 0;">{{ $message }}</p>
                                @enderror
                                @if($adjustment->status === 'pending')
                                    @if($adjustment->requested_by !== Auth::id())
                                        <form method="POST" action="{{ route('inventory.stock-adjustments.approve', $adjustment) }}" style="display:inline;" onsubmit="return confirm('Approve this adjustment?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" style="background:#166534;color:#fff;border:none;border-radius:6px;padding:5px 12px;font-size:11px;font-weight:600;cursor:pointer;">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('inventory.stock-adjustments.reject', $adjustment) }}" style="display:inline;" onsubmit="return confirm('Reject this adjustment?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" style="background:#991b1b;color:#fff;border:none;border-radius:6px;padding:5px 12px;font-size:11px;font-weight:600;cursor:pointer;">Reject</button>
                                        </form>
                                    @else
                                        <span style="color:#94a3b8;font-size:12px;">Awaiting review</span>
                                        <form method="POST" action="{{ route('inventory.stock-adjustments.cancel', $adjustment) }}" style="display:inline;" onsubmit="return confirm('Cancel this adjustment request?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" style="background:#475569;color:#fff;border:none;border-radius:6px;padding:5px 12px;font-size:11px;font-weight:600;cursor:pointer;">Cancel</button>
                                        </form>
                                    @endif
                                @else
                                    <span style="color:#94a3b8;font-size:12px;">â€”</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" style="text-align:center;padding:20px;color:#64748b;font-size:13px;">No stock adjustments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $adjustments->links() }}
    </div>
    <div id="adjustmentModal" class="nexora-modal-overlay" style="display:flex;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:20;align-items:center;justify-content:center;">
        <div class="nexora-modal">
            <div class="nexora-modal-logo"></div>
            <div class="nexora-modal-header">
                <h2 class="nexora-modal-title">New Stock Adjustment</h2>
                <button type="button" onclick="closeAdjustmentModal()" class="nexora-modal-close">&times;</button>
            </div>

            <form method="POST" action="{{ route('inventory.stock-adjustments.store') }}" novalidate>
                @csrf

                <div class="nexora-modal-form">
                    <div>
                        <label class="nexora-modal-label">Warehouse</label>
                        <select name="warehouse_id" id="warehouse_id" class="nexora-modal-select" required>
                            <option value="">Select Warehouse</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                            @endforeach
                        </select>
                        @error('warehouse_id')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="nexora-modal-label">Item</label>
                        <select name="item_id" id="item_id" class="nexora-modal-select" required>
                            <option value="">Select Warehouse First</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}" {{ old('item_id') == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('item_id')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="nexora-modal-label">Type</label>
                        <select name="type" class="nexora-modal-select" required>
                            <option value="">Select Type</option>
                            <option value="increase" {{ old('type') === 'increase' ? 'selected' : '' }}>Increase</option>
                            <option value="decrease" {{ old('type') === 'decrease' ? 'selected' : '' }}>Decrease</option>
                        </select>
                        @error('type')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="nexora-modal-label">Reason</label>
                        <select name="reason" class="nexora-modal-select" required>
                            <option value="">Select Reason</option>
                            <option value="damage" {{ old('reason') === 'damage' ? 'selected' : '' }}>Damage</option>
                            <option value="expired" {{ old('reason') === 'expired' ? 'selected' : '' }}>Expired</option>
                            <option value="recount" {{ old('reason') === 'recount' ? 'selected' : '' }}>Recount</option>
                            <option value="theft" {{ old('reason') === 'theft' ? 'selected' : '' }}>Theft</option>
                            <option value="correction" {{ old('reason') === 'correction' ? 'selected' : '' }}>Correction</option>
                        </select>
                        @error('reason')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="nexora-modal-label">Quantity</label>
                        <input type="number" name="quantity" id="adjustment_quantity" value="{{ old('quantity') }}" min="1" class="nexora-modal-input" placeholder="e.g. 50" required>
                        <span id="stock_indicator" style="font-size:11px;color:#64748b;display:none;margin-top:4px;"></span>
                        @error('quantity')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="nexora-modal-label">Notes (optional)</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" class="nexora-modal-input" placeholder="Additional details...">
                    </div>
                </div>

                <div class="nexora-modal-actions">
                    <button type="button" onclick="closeAdjustmentModal()" class="nexora-modal-btn-secondary">Cancel</button>
                    <button type="submit" class="nexora-modal-btn-primary">Submit Adjustment</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const adjModal = document.getElementById('adjustmentModal');
    window.openAdjustmentModal = function() { adjModal.classList.add('open'); };
    window.closeAdjustmentModal = function() { adjModal.classList.remove('open'); };
    if (adjModal) adjModal.addEventListener('click', function(e) { if (e.target === this) window.closeAdjustmentModal(); });

    @if($errors->any())
        window.openAdjustmentModal();
    @endif

    const stockMap = @json($stockMap);
    const itemsByWarehouse = @json($itemsByWarehouse);
    const allItems = @json($items);
    const warehouseSelect = document.getElementById('warehouse_id');
    const itemSelect = document.getElementById('item_id');
    const typeSelect = document.querySelector('#adjustmentModal select[name="type"]');
    const quantityInput = document.getElementById('adjustment_quantity');
    const stockIndicator = document.getElementById('stock_indicator');

    function getCurrentStock() {
        const wh = warehouseSelect.value;
        const item = itemSelect.value;
        if (wh && item) {
            return stockMap[wh + '-' + item] ?? null;
        }
        return null;
    }

    function clamp() {
        const stock = getCurrentStock();
        const type = typeSelect.value;
        if (stock !== null && type === 'decrease') {
            const val = parseInt(quantityInput.value);
            if (!isNaN(val) && val > stock) {
                quantityInput.value = stock;
            }
        }
    }

    function updateIndicator() {
        const stock = getCurrentStock();
        stockIndicator.textContent = stock !== null ? 'Stock available: ' + stock : '';
        stockIndicator.style.display = stock !== null ? 'block' : 'none';
        clamp();
    }

    function filterItems() {
        const warehouseId = warehouseSelect.value;
        const currentItemId = itemSelect.value;

        itemSelect.innerHTML = '<option value="">Select Item</option>';

        let availableItems = [];
        if (warehouseId && itemsByWarehouse[warehouseId]) {
            availableItems = itemsByWarehouse[warehouseId];
        } else if (!warehouseId) {
            availableItems = allItems;
        }

        availableItems.forEach(function(item) {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            if (String(item.id) === currentItemId) {
                option.selected = true;
            }
            itemSelect.appendChild(option);
        });

        if (warehouseId && availableItems.length === 0) {
            itemSelect.innerHTML = '<option value="">No items in this warehouse</option>';
        }

        updateIndicator();
    }

    warehouseSelect.addEventListener('change', filterItems);
    itemSelect.addEventListener('change', updateIndicator);
    typeSelect.addEventListener('change', updateIndicator);
    quantityInput.addEventListener('input', clamp);
    quantityInput.addEventListener('change', clamp);

    // Run once on load in case old() has a value selected
    filterItems();
</script>
@endpush

