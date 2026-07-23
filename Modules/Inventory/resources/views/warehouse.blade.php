@extends('inventory::layouts.dashboard')

@section('title', 'Warehouse')

@push('styles')
<style>
    .capacity-bar { height: 10px; border-radius: 9999px; background: linear-gradient(90deg, #ef4444 0%, #f0a93e 40%, #22c55e 70%, #22c55e 100%); }
    .capacity-track { height: 10px; border-radius: 9999px; background: #e2e8f0; overflow: hidden; }

    .view-btn { background: transparent; color: #94a3b8; border: 1px solid #1b3a6b; border-radius: 8px; padding: 8px 12px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
    .view-btn.active { background: #1b3a6b; color: #fff; }

    /* Modal transition */
    #addWarehouseModal { opacity: 0; pointer-events: none; transition: opacity 0.2s ease; }
    #addWarehouseModal.open { opacity: 1; pointer-events: auto; }
    #editWarehouseModal { opacity: 0; pointer-events: none; transition: opacity 0.2s ease; }
    #editWarehouseModal.open { opacity: 1; pointer-events: auto; }
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

    <!-- Toolbar -->
    <div class="responsive-flex" style="margin-bottom:16px;">
        <!-- Grid / List Toggle -->
        <div style="display:flex;align-items:center;gap:6px;">
            <button id="gridViewBtn" class="view-btn active" title="Grid view" aria-label="Grid view">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
            </button>
            <button id="listViewBtn" class="view-btn" title="List view" aria-label="List view">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <button onclick="openModal()" style="background:#1b6fc8;color:#fff;border:none;border-radius:10px;padding:10px 20px;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;display:flex;align-items:center;gap:8px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add new
        </button>
    </div>

    <!-- Warehouse Grid/List Container -->
    <div id="warehouseContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 items-stretch">
        @forelse($warehouses as $warehouse)
            <div class="warehouse-card" style="display:flex;flex-direction:column;height:100%;" data-id="{{ data_get($warehouse, 'id') }}">
                <!-- Card Header -->
                <div style="background:#0b1e3d;border-radius:16px 16px 0 0;padding:14px 16px;flex-shrink:0;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                        <div style="min-width:0;display:flex;flex-direction:column;justify-content:center;">
                            <p style="font-size:15px;font-weight:700;line-height:1.25;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ data_get($warehouse, 'name') }}</p>
                            @if(data_get($warehouse, 'address'))
                                <p style="font-size:11px;color:#94a3b8;margin-top:4px;">{{ data_get($warehouse, 'address') }}</p>
                            @endif
                        </div>
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:12px;background:rgba(34,197,94,0.12);color:#22c55e;font-size:10px;font-weight:700;text-transform:uppercase;white-space:nowrap;flex-shrink:0;">
                            <span style="width:6px;height:6px;border-radius:50%;background:#22c55e;display:inline-block;"></span>
                            {{ data_get($warehouse, 'status') }}
                        </span>
                    </div>
                </div>

                <!-- Card Body -->
                <div style="background:#ffffff;border-radius:0 0 16px 16px;padding:16px;flex:1 1 auto;display:flex;flex-direction:column;">
                    @php
                        $daysInactive = data_get($warehouse, 'days_since_activity');
                        $showInactivityWarning = $warehouse->status === 'active' && ($daysInactive !== null && $daysInactive >= 90);
                    @endphp
                    @if($showInactivityWarning)
                        <div style="display:flex;align-items:center;gap:6px;padding:8px 10px;margin-bottom:12px;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:8px;font-size:11px;color:#b45309;">
                            <svg width="14" height="14" fill="none" stroke="#b45309" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
                            <span style="font-weight:600;">
                                Inactive for {{ $daysInactive }} days â€” consider deactivating
                            </span>
                        </div>
                    @endif
                    <p style="font-size:10px;font-weight:700;color:#64748b;letter-spacing:0.6px;margin-bottom:6px;">CAPACITY STORAGE</p>
                    <div style="display:flex;align-items:baseline;gap:8px;margin-bottom:10px;">
                        <span style="font-size:28px;font-weight:800;color:#0f172a;">{{ data_get($warehouse, 'capacity_percentage') }}%</span>
                        <span style="font-size:12px;color:#94a3b8;">{{ data_get($warehouse, 'used_units') }}/{{ data_get($warehouse, 'capacity_units') }} Units</span>
                    </div>
                    <div class="capacity-track">
                        <div class="capacity-bar" style="width:{{ data_get($warehouse, 'capacity_percentage') }}%;"></div>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;">
                        <div style="display:flex;gap:6px;">
                            <button onclick="openEditModal({{ $warehouse->id }}, '{{ addslashes($warehouse->name) }}', {{ $warehouse->capacity_units }}, '{{ addslashes($warehouse->address ?? '') }}', '{{ $warehouse->status }}')" style="background:transparent;border:1px solid #e2e8f0;border-radius:6px;padding:4px 8px;cursor:pointer;display:flex;align-items:center;" title="Edit">
                                <svg width="14" height="14" fill="none" stroke="#64748b" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <form method="POST" action="{{ route('inventory.warehouse.destroy', $warehouse) }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to deactivate this warehouse?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background:transparent;border:1px solid #fee2e2;border-radius:6px;padding:4px 8px;cursor:pointer;display:flex;align-items:center;" title="Deactivate">
                                    <svg width="14" height="14" fill="none" stroke="#ef4444" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div id="noWarehouses" style="grid-column:1 / -1;text-align:center;padding:40px 20px;color:#94a3b8;">
                No warehouses found. Click "Add new" to create one.
            </div>
        @endforelse
    </div>

    <!-- Add Warehouse Modal -->
    <div id="addWarehouseModal" class="nexora-modal-overlay" style="display:flex;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:20;align-items:center;justify-content:center;">
        <div class="nexora-modal">
            <div class="nexora-modal-logo"></div>
            <div class="nexora-modal-header">
                <h2 class="nexora-modal-title">Add New Warehouse</h2>
                <button type="button" onclick="closeModal()" class="nexora-modal-close">&times;</button>
            </div>

            <form id="addWarehouseForm" method="POST" action="{{ route('inventory.warehouse.store') }}" novalidate>
                @csrf

                <div class="nexora-modal-form">
                    <div>
                        <label class="nexora-modal-label">Warehouse Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="nexora-modal-input" placeholder="e.g. Dasma Main Warehouse" required>
                        @error('name')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="nexora-modal-label">Capacity (Units)</label>
                        <input type="number" name="capacity_units" value="{{ old('capacity_units') }}" min="1" class="nexora-modal-input" placeholder="e.g. 1000" required>
                        @error('capacity_units')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="nexora-modal-label">Address</label>
                        <input type="text" name="address" value="{{ old('address') }}" class="nexora-modal-input" placeholder="e.g. Blk 5 Lot 12, Salawag, DasmariÃ±as, Cavite">
                        @error('address')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="nexora-modal-label">Status</label>
                        <select name="status" class="nexora-modal-select" required>
                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="nexora-modal-actions">
                    <button type="button" onclick="closeModal()" class="nexora-modal-btn-secondary">Cancel</button>
                    <button type="submit" id="submitWarehouseBtn" class="nexora-modal-btn-primary">
                        <span>Add Warehouse</span>
                        <svg id="submitSpinner" style="display:none;" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Edit Warehouse Modal -->
    <div id="editWarehouseModal" class="nexora-modal-overlay" style="display:flex;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:20;align-items:center;justify-content:center;">
        <div class="nexora-modal">
            <div class="nexora-modal-logo"></div>
            <div class="nexora-modal-header">
                <h2 class="nexora-modal-title">Edit Warehouse</h2>
                <button type="button" onclick="closeEditModal()" class="nexora-modal-close">&times;</button>
            </div>

            <form id="editWarehouseForm" method="POST" novalidate>
                @csrf
                @method('PATCH')

                <div class="nexora-modal-form">
                    <div>
                        <label class="nexora-modal-label">Warehouse Name</label>
                        <input type="text" name="name" id="edit_name" value="{{ old('name') }}" class="nexora-modal-input" required>
                        @error('name')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="nexora-modal-label">Capacity (Units)</label>
                        <input type="number" name="capacity_units" id="edit_capacity_units" value="{{ old('capacity_units') }}" min="1" class="nexora-modal-input" required>
                        @error('capacity_units')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="nexora-modal-label">Address</label>
                        <input type="text" name="address" id="edit_address" value="{{ old('address') }}" class="nexora-modal-input" placeholder="e.g. Blk 5 Lot 12, Salawag, DasmariÃ±as, Cavite">
                        @error('address')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="nexora-modal-label">Status</label>
                        <select name="status" id="edit_status" class="nexora-modal-select" required>
                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="nexora-modal-actions">
                    <button type="button" onclick="closeEditModal()" class="nexora-modal-btn-secondary">Cancel</button>
                    <button type="submit" class="nexora-modal-btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Modal
    const modal = document.getElementById('addWarehouseModal');
    function openModal() {
        modal.classList.add('open');
    }
    function closeModal() {
        modal.classList.remove('open');
    }
    modal.addEventListener('click', function (e) {
        if (e.target === this) closeModal();
    });

    // View Toggle
    const container = document.getElementById('warehouseContainer');
    const gridBtn = document.getElementById('gridViewBtn');
    const listBtn = document.getElementById('listViewBtn');

    function setView(view) {
        localStorage.setItem('warehouseView', view);
        if (view === 'list') {
            container.classList.remove('grid', 'grid-cols-1', 'sm:grid-cols-2', 'lg:grid-cols-3', 'xl:grid-cols-4', 'gap-4');
            container.classList.add('flex', 'flex-col', 'gap-4');
            listBtn.classList.add('active');
            gridBtn.classList.remove('active');
        } else {
            container.classList.remove('flex', 'flex-col', 'gap-4');
            container.classList.add('grid', 'grid-cols-1', 'sm:grid-cols-2', 'lg:grid-cols-3', 'xl:grid-cols-4', 'gap-4');
            gridBtn.classList.add('active');
            listBtn.classList.remove('active');
        }
    }

    gridBtn.addEventListener('click', () => setView('grid'));
    listBtn.addEventListener('click', () => setView('list'));

    const savedView = localStorage.getItem('warehouseView') || 'grid';
    setView(savedView);

    function updateGridStretch() {
        const view = localStorage.getItem('warehouseView') || 'grid';
        if (view === 'grid') {
            container.classList.add('items-stretch');
        } else {
            container.classList.remove('items-stretch');
        }
    }
    const originalSetView = setView;
    setView = function(view) {
        originalSetView(view);
        updateGridStretch();
    }
    updateGridStretch();

    // Edit Modal
    const editModal = document.getElementById('editWarehouseModal');
    const editForm = document.getElementById('editWarehouseForm');
    function openEditModal(id, name, capacity, address, status) {
        editForm.action = '/warehouse/' + id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_capacity_units').value = capacity;
        document.getElementById('edit_address').value = address;
        document.getElementById('edit_status').value = status;
        editModal.style.opacity = '1';
        editModal.style.pointerEvents = 'auto';
    }
    function closeEditModal() {
        editModal.style.opacity = '0';
        editModal.style.pointerEvents = 'none';
    }
    editModal.addEventListener('click', function(e) { if (e.target === this) closeEditModal(); });

    // Form loading state
    const form = document.getElementById('addWarehouseForm');
    const submitBtn = document.getElementById('submitWarehouseBtn');
    const spinner = document.getElementById('submitSpinner');
    form.addEventListener('submit', function () {
        submitBtn.disabled = true;
        spinner.style.display = 'inline-block';
    });

    // Open add modal if validation errors exist
    @if($errors->any() && !request()->has('edit'))
        openModal();
    @endif

    // Auto-open edit modal and set form action when ?edit=ID is present
    (function() {
        var params = new URLSearchParams(window.location.search);
        var editId = params.get('edit');
        if (editId) {
            editForm.action = '/warehouse/' + editId;
            editModal.style.opacity = '1';
            editModal.style.pointerEvents = 'auto';
        }
    })();
</script>
@endpush

