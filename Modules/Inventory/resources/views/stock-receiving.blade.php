@extends('inventory::layouts.dashboard')

@section('title', 'Stock Receiving')

@push('styles')
<style>
    .status-badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .status-pending { background: #fef9c3; color: #854d0e; }
    .status-in-transit, .status-intransit { background: #dbeafe; color: #1e40af; }
    .status-approved { background: #dcfce7; color: #166534; }
    .status-rejected { background: #fee2e2; color: #991b1b; }

    #tbodyPending tr[data-destination-warehouse] td:nth-child(4) { font-size: 0 !important; }
    #tbodyPending tr[data-destination-warehouse] td:nth-child(4)::after {
        content: attr(data-destination-warehouse);
        font-size: 13px;
    }
    #tbodyHistory tr[data-history-supplier] td:nth-child(2) { font-size: 0 !important; }
    #tbodyHistory tr[data-history-supplier] td:nth-child(2)::after {
        content: attr(data-history-supplier);
        font-size: 13px;
    }

    #approveModal, #rejectModal { opacity: 0; pointer-events: none; transition: opacity 0.2s ease; }
    #approveModal.open, #rejectModal.open { opacity: 1; pointer-events: auto; }
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
            <p style="font-size:15px;color:#94a3b8;">Pending Deliveries</p>
            <p style="font-size:40px;font-weight:bold;color:#fff;">{{ $pendingCount }}</p>
        </div>
        <div style="background:#0b1e3d;padding:20px;border-radius:20px;">
            <p style="font-size:15px;color:#94a3b8;">Received Today</p>
            <p style="font-size:40px;font-weight:bold;color:#fff;">{{ $receivedTodayCount }}</p>
        </div>
        <div style="background:#0b1e3d;padding:20px;border-radius:20px;">
            <p style="font-size:15px;color:#94a3b8;">Rejected</p>
            <p style="font-size:40px;font-weight:bold;color:#fff;">{{ $rejectedCount }}</p>
        </div>
    </div>

    <!-- Table Card -->
    <div style="background:#ffffff;border-radius:20px;overflow:hidden;min-width:0;">
        <!-- Tabs + Filters Row -->
        <div style="padding:16px 20px;display:flex;align-items:center;gap:12px;flex-wrap:nowrap;min-width:0;border-bottom:1px solid #e2e8f0;">
            <div style="display:flex;gap:4px;background:#e2e8f0;border-radius:8px;padding:3px;flex-shrink:0;">
                <button id="tabPending" onclick="switchTab('pending')" style="padding:6px 16px;border:none;border-radius:6px;font-size:12px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;background:#0b1e3d;color:#fff;">Pending</button>
                <button id="tabHistory" onclick="switchTab('history')" style="padding:6px 16px;border:none;border-radius:6px;font-size:12px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;background:transparent;color:#64748b;">History</button>
            </div>
            <!-- Filters (only shown on Pending tab) -->
            <div id="pendingFilters" style="display:flex;align-items:center;gap:12px;flex:1;">
                <form method="GET" action="{{ route('inventory.stock-receiving') }}" style="display:flex;align-items:center;gap:12px;flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;background:#E2E8F0;border-radius:8px;padding:8px 14px;gap:8px;flex:1;min-width:150px;">
                        <svg width="16" height="16" fill="none" stroke="#64748b" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by Shipment Number, Item..." style="border:none;outline:none;background:transparent;font-size:12px;font-family:'Inter',sans-serif;color:#333;width:100%;">
                    </div>
                    <select name="status" onchange="this.form.submit()" style="background:#E2E8F0;color:#000;border:none;border-radius:20px;padding:8px 16px;font-size:13px;font-family:'Inter',sans-serif;cursor:pointer;outline:none;flex-shrink:0;">
                        <option value="">All Status</option>
                        <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="intransit" {{ ($filters['status'] ?? '') === 'intransit' ? 'selected' : '' }}>In Transit</option>
                    </select>
                    @if(array_filter($filters ?? []))
                        <a href="{{ route('inventory.stock-receiving') }}" style="background:transparent;color:#64748b;border:1px solid #cbd5e1;border-radius:20px;padding:8px 16px;font-size:13px;font-family:'Inter',sans-serif;text-decoration:none;display:inline-flex;align-items:center;gap:4px;white-space:nowrap;flex-shrink:0;" title="Clear all filters">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Clear
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="responsive-table" style="min-width:0;">
            <table class="stock-table" style="width:100%;table-layout:auto;border-collapse:collapse;">
                <thead>
                    <tr style="background:#1b3a6b;">
                        <th style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">SHIPMENT #</th>
                        <th style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">SUPPLIER</th>
                        <th style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">QTY</th>
                        <th style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">WAREHOUSE</th>
                        <th style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">STATUS</th>
                        <th style="text-align:center;padding:10px 6px;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">DETAILS</th>
                    </tr>
                </thead>

                <!-- Pending deliveries -->
                <tbody id="tbodyPending">
                    @forelse ($deliveries as $delivery)
                        @php
                            $isProcessed = $deliveryProcessed[$delivery->id] ?? false;
                        @endphp
                        <tr data-destination-warehouse="{{ $delivery->destination_warehouse_name }}" style="border-bottom:1px solid #e2e8f0;{{ $isProcessed ? 'opacity:0.5;' : '' }}">
                            <td style="text-align:center;padding:12px 8px;font-size:13px;color:#132B52;">{{ $delivery->shipment_number }}</td>
                            <td style="text-align:center;padding:12px 8px;font-size:13px;color:#132B52;">{{ $delivery->supplier_name }}</td>
                            <td style="text-align:center;padding:12px 8px;font-size:13px;color:#132B52;font-weight:600;">{{ $delivery->qty }}</td>
                            <td style="text-align:center;padding:12px 8px;font-size:13px;color:#5B7A9D;">â€”</td>
                            <td style="text-align:center;padding:12px 8px;">
                                <span class="status-badge status-{{ str_replace(' ', '-', strtolower($delivery->status)) }}">{{ ucfirst($delivery->status) }}</span>
                            </td>
                            <td style="text-align:center;padding:12px 8px;">
                                @error("del_action_{$delivery->id}")
                                    <p style="color:#ef4444;font-size:11px;margin:0 0 6px 0;">{{ $message }}</p>
                                @enderror
                                @if(!$isProcessed)
                                    <form method="POST" action="{{ route('inventory.stock-receiving.approve', $delivery->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" style="background:#166534;color:#fff;border:none;border-radius:6px;padding:5px 12px;font-size:11px;font-weight:600;cursor:pointer;margin-right:4px;">Approve &amp; Receive</button>
                                    </form>
                                    <button onclick="openRejectModal({{ $delivery->id }})" style="background:#991b1b;color:#fff;border:none;border-radius:6px;padding:5px 12px;font-size:11px;font-weight:600;cursor:pointer;">Reject</button>
                                @else
                                    <span style="color:#94a3b8;font-size:12px;">â€”</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr id="pendingEmptyRow">
                            <td colspan="6" style="text-align:center;padding:40px;color:#64748b;font-size:13px;">
                                <svg width="48" height="48" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" stroke-width="1.5" style="margin:0 auto 12px;display:block;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                No pending deliveries from Procurement.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <!-- History entries -->
                <tbody id="tbodyHistory" style="display:none;">
                    @forelse ($history as $entry)
                        <tr data-history-supplier="{{ $historySuppliers[$entry->shipment_number] ?? 'Unknown supplier' }}" style="border-bottom:1px solid #e2e8f0;">
                            <td style="text-align:center;padding:12px 8px;font-size:13px;color:#132B52;">{{ $entry->shipment_number }}</td>
                            <td style="text-align:center;padding:12px 8px;font-size:13px;color:#132B52;">{{ $entry->item?->name ?? 'â€”' }}</td>
                            <td style="text-align:center;padding:12px 8px;font-size:13px;color:#132B52;font-weight:600;">{{ $entry->quantity }}</td>
                            <td style="text-align:center;padding:12px 8px;font-size:13px;color:#132B52;">{{ $entry->warehouse?->name ?? 'â€”' }}</td>
                            <td style="text-align:center;padding:12px 8px;">
                                <span class="status-badge status-{{ $entry->status }}" style="{{ $entry->status === 'approved' ? 'background:#dcfce7;color:#166534;' : 'background:#fee2e2;color:#991b1b;' }}">{{ ucfirst($entry->status) }}</span>
                            </td>
                            <td style="text-align:center;padding:12px 8px;font-size:12px;color:#5B7A9D;line-height:1.5;">
                                <div>by {{ $entry->processor?->name ?? 'â€”' }}</div>
                                <div style="font-size:11px;color:#94a3b8;">{{ $entry->processed_at?->format('M d, Y h:i A') ?? 'â€”' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:40px;color:#64748b;font-size:13px;">No processed records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination (pending only) -->
        <div id="pendingPagination">
            @if($deliveries->hasPages())
                <div style="padding:16px;border-top:1px solid #e2e8f0;">
                    {{ $deliveries->links() }}
                </div>
            @endif
        </div>
    </div>

    <script>
        function switchTab(tab) {
            const tabPending = document.getElementById('tabPending');
            const tabHistory = document.getElementById('tabHistory');
            const tbodyPending = document.getElementById('tbodyPending');
            const tbodyHistory = document.getElementById('tbodyHistory');
            const pendingFilters = document.getElementById('pendingFilters');
            const pendingPagination = document.getElementById('pendingPagination');

            if (tab === 'pending') {
                tabPending.style.background = '#0b1e3d';
                tabPending.style.color = '#fff';
                tabHistory.style.background = 'transparent';
                tabHistory.style.color = '#64748b';
                tbodyPending.style.display = '';
                tbodyHistory.style.display = 'none';
                pendingFilters.style.display = '';
                pendingPagination.style.display = '';
            } else {
                tabHistory.style.background = '#0b1e3d';
                tabHistory.style.color = '#fff';
                tabPending.style.background = 'transparent';
                tabPending.style.color = '#64748b';
                tbodyHistory.style.display = '';
                tbodyPending.style.display = 'none';
                pendingFilters.style.display = 'none';
                pendingPagination.style.display = 'none';
            }
        }
    </script>

    <!-- Reject Modal -->
    <div id="rejectModal" class="nexora-modal-overlay" style="display:flex;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:20;align-items:center;justify-content:center;">
        <div class="nexora-modal" style="max-width:500px;">
            <div class="nexora-modal-logo"></div>
            <div class="nexora-modal-header">
                <h2 class="nexora-modal-title">Reject Delivery</h2>
                <button type="button" onclick="closeRejectModal()" class="nexora-modal-close">&times;</button>
            </div>

            <form id="rejectForm" method="POST" action="">
                @csrf

                <div class="nexora-modal-form" style="grid-template-columns:1fr;">
                    <div>
                        <label class="nexora-modal-label">Reason for Rejection <span style="color:#ef4444;">*</span></label>
                        <textarea name="reject_reason" required rows="4" class="nexora-modal-input" style="resize:vertical;"></textarea>
                        @error('reject_reason')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="nexora-modal-actions">
                    <button type="button" onclick="closeRejectModal()" class="nexora-modal-btn-secondary">Cancel</button>
                    <button type="submit" class="nexora-modal-btn-primary" style="background:#dc2626;color:#fff;border-color:#dc2626;">Reject</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(deliveryId) {
            document.getElementById('rejectModal').classList.add('open');
            document.getElementById('rejectForm').action = @json(url('inventory/stock-receiving')) + '/' + deliveryId + '/reject';
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('open');
            document.getElementById('rejectForm').reset();
        }

        // Close modal when clicking outside
        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) closeRejectModal();
        });
    </script>
@endsection
