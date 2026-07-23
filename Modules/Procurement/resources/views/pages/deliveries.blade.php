@extends('procurement::layouts.app')

@section('title', 'Nexora ERP — Deliveries')

@section('content')
<section id="page-deliveries">
      @php
        $statusCounts = $statusCounts ?? collect($deliveries)->map(function ($d) {
            return strtolower(str_replace([' ', '_'], '-', $d->status ?? 'intransit'));
        })->countBy();
      @endphp

      <div class="page-head">
        <h1>Deliveries</h1>
        <p>Track incoming shipments from suppliers in real time.</p>
      </div>

      <div class="status-chart" id="delivery-status-chart">
        <div class="status-chart-item pending" data-status="pending" style="background:linear-gradient(135deg,#fff3e0,#ffe0b2);border-color:#ff9800;" onclick="filterByStatus('deliveries-table', 'pending', this)">
          <div class="status-label">Pending</div>
          <div class="status-count">{{ $statusCounts->get('pending', 0) }}</div>
        </div>
        <div class="status-chart-item scheduled" data-status="scheduled" style="background:linear-gradient(135deg,#f0eaff,#dcd0fb);border-color:#7a5af0;" onclick="filterByStatus('deliveries-table', 'scheduled', this)">
          <div class="status-label">Scheduled</div>
          <div class="status-count">{{ $statusCounts->get('scheduled', 0) }}</div>
        </div>
        <div class="status-chart-item intransit" data-status="intransit" style="background:linear-gradient(135deg,#e3f2fd,#bbdefb);border-color:#2196f3;" onclick="filterByStatus('deliveries-table', 'intransit', this)">
          <div class="status-label">intransit</div>
          <div class="status-count">{{ $statusCounts->get('intransit', 0) }}</div>
            <div class="stat-sub info">● Live tracking</div>
        </div>
        <div class="status-chart-item delayed" data-status="delayed" style="background:linear-gradient(135deg,#ffebee,#ffcdd2);border-color:#f44336;" onclick="filterByStatus('deliveries-table', 'delayed', this)">
          <div class="status-label">Delayed</div>
          <div class="status-count">{{ $statusCounts->get('delayed', 0) }}</div>

          <div class="stat-sub" style="color:var(--red);">● Needs attention</div>
        </div>
        <div class="status-chart-item delivered" data-status="delivered" style="background:linear-gradient(135deg,#e8f5e9,#c8e6c9);border-color:#4caf50;" onclick="filterByStatus('deliveries-table', 'delivered', this)">
          <div class="status-label">Delivered</div>
          <div class="status-count">{{ $statusCounts->get('delivered', 0) }}</div>
        </div>
        <div class="status-chart-item completed" data-status="completed" style="background:linear-gradient(135deg,#e0f2f1,#b2dfdb);border-color:#009688;" onclick="filterByStatus('deliveries-table', 'completed', this)">
          <div class="status-label">Completed</div>
          <div class="status-count">{{ $statusCounts->get('completed', 0) }}</div>
        </div>
        <div class="status-chart-item cancelled" data-status="cancelled" style="background:linear-gradient(135deg,#f1f3f6,#e2e6ee);border-color:#7c88a3;" onclick="filterByStatus('deliveries-table', 'cancelled', this)">
          <div class="status-label">Cancelled</div>
          <div class="status-count">{{ $statusCounts->get('cancelled', 0) }}</div>
        </div>
      </div>
      
        <div class="panel" style="grid-column: span 2;">
          <div class="table-toolbar">
            <h2>Active Shipments</h2>
            <div class="search-box">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              <input placeholder="Search shipment or PO..." oninput="filterTable('deliveries-table', this.value)">
            </div>
             <button class="toolbar-btn" onclick="toggleFilterPanel('del-filter-panel', this)">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M3 5h18l-7 8v6l-4 2v-8L3 5z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
            Filter
          </button>
            <button class="toolbar-btn primary" onclick="openAddModal('delivery')">+ Log Delivery</button>
          </div>
        </div>
        <div class="filter-panel hidden" id="del-filter-panel">
          <div class="filter-group">
            <label>Status</label>
            <select id="delivery-filter-status" onchange="applyDelFilter()">
              <option value="">All Status</option>
              <option value="pending">Pending</option>
              <option value="scheduled">Scheduled</option>
              <option value="intransit">intransit</option>
              <option value="delayed">Delayed</option>
              <option value="delivered">Delivered</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div class="filter-group">
            <label>Date Range</label>
            <input type="date" id="delivery-filter-date-from" placeholder="From"> 
            <input type="date" id="delivery-filter-date-to" placeholder="To">
          </div>
          <div class="filter-group">
            <label>Supplier</label>
            <select id="delivery-filter-supplier" onchange="applyDelFilter()">
              <option value="">All Suppliers</option>
              <option value="TechWholesale PH">TechWholesale PH</option>
              <option value="Quantum Motherboards">Quantum Motherboards</option>
              <option value="Silverline PSU Ltd">Silverline PSU Ltd</option>
              <option value="Primo Electronics">Primo Electronics</option>
              <option value="GigaCore Components">GigaCore Components</option>
            </select>
          </div>
          <div class="filter-actions">
            <button class="btn-text" onclick="clearDelFilter()">Clear</button>
            <button class="btn-primary" onclick="applyDelFilter()">Apply</button>
          </div>
        </div>

        <table class="data-table sortable-table" id="deliveries-table">
            <thead>
              <tr>
                <th class="sortable" data-key="ship">SHIP<span class="sort-arrows"><svg viewBox="0 0 8 5"><path d="M4 0L8 5H0z" fill="currentColor"/></svg><svg viewBox="0 0 8 5"><path d="M4 5L0 0h8z" fill="currentColor"/></svg></span></th>
                <th class="sortable" data-key="po">PO<span class="sort-arrows"><svg viewBox="0 0 8 5"><path d="M4 0L8 5H0z" fill="currentColor"/></svg><svg viewBox="0 0 8 5"><path d="M4 5L0 0h8z" fill="currentColor"/></svg></span></th>
                <th class="sortable" data-key="supplier">SUPPLIER<span class="sort-arrows"><svg viewBox="0 0 8 5"><path d="M4 0L8 5H0z" fill="currentColor"/></svg><svg viewBox="0 0 8 5"><path d="M4 5L0 0h8z" fill="currentColor"/></svg></span></th>
                <th>ITEM</th>
                <th>EXPECTED DELIVERY</th>
                <th class="sortable" data-key="status">STATUS<span class="sort-arrows"><svg viewBox="0 0 8 5"><path d="M4 0L8 5H0z" fill="currentColor"/></svg><svg viewBox="0 0 8 5"><path d="M4 5L0 0h8z" fill="currentColor"/></svg></span></th>
                <th class="sortable sort-asc" data-key="DATE">DATE<span class="sort-arrows"><svg viewBox="0 0 8 5"><path d="M4 0L8 5H0z" fill="currentColor"/></svg><svg viewBox="0 0 8 5"><path d="M4 5L0 0h8z" fill="currentColor"/></svg></span></th>
                <th>ACTION</th>
              </tr>
            </thead>
            <tbody>
              @if(isset($deliveries) && count($deliveries))
                @foreach($deliveries as $d)
                  @php
                    $sname = $d->supplier_name ?? '';
                    $parts = preg_split('/\s+/', trim($sname));
                    $initials = '';
                    if($parts){
                      $initials = strtoupper(substr($parts[0],0,1));
                      if(count($parts) > 1) $initials .= strtoupper(substr($parts[count($parts)-1],0,1));
                    }
                    $colorMap = ['GigaCore Components'=>'#22c55e','Global Tech Supply'=>'#0ea5e9','MegaStar Trading'=>'#f2994a','Primo Electronics'=>'#22c55e','Quantum Motherboards'=>'#7a5af0','Silverline PSU Ltd'=>'#eb5757','Silverline PSU Ltd.'=>'#eb5757','TechWholesale PH'=>'#2f6fed','Trident RAM Supply'=>'#0ea5e9'];
                    $colors = ['#2f6fed','#22c55e','#f2994a','#7a5af0','#eb5757','#0ea5e9','#1fa971','#e0338c'];
                    $badgeColor = $colorMap[$sname] ?? null;
                    if(!$badgeColor){
                      $h = 0; foreach(str_split($sname ?? '') as $ch) $h = ($h*31 + ord($ch)) & 0xffff;
                      $badgeColor = $colors[$h % count($colors)];
                    }
                  @endphp
                  <tr data-id="{{ $d->id }}" data-po="{{ $d->po_number ?? '' }}" data-sup="{{ $d->supplier_name ?? '' }}" data-stage="{{ $d->stage ?? '' }}" data-status="{{ strtolower(str_replace([' ', '_'], '-', $d->status ?? 'intransit')) }}" data-date="{{ $d->delivery_date ?? '' }}">
                    <td><a class="po-link">{{ $d->shipment_number }}</a></td>
                    <td><a class="po-link">{{ $d->po_number ?? '—' }}</a></td>
                    <td><div class="supplier-pill-cell"><span class="supplier-pill"><span class="supplier-badge" style="background: {{ $badgeColor }}">{{ $initials }}</span>{{ $d->supplier_name ?? '—' }}</span></div></td>
                    <td>{{ $d->items ?? '—' }}</td>
                    <td>{{ $d->estimated_arrival ?? $d->delivery_date ?? '' }}</td>
                    <td><span class="status-pill {{ strtolower(str_replace([' ', '_'], '-', $d->status ?? 'intransit')) }}">{{ ucwords(str_replace('-', ' ', $d->status ?? 'intransit')) }}</span></td>
                    <td>{{ $d->delivery_date ?? '' }}</td>
                    <td><span class="row-actions"><button title="Track">🔎</button></span></td>
                  </tr>
                @endforeach
              @else
                <tr>
                  <td colspan="8" style="text-align:center; padding:32px 16px; color:var(--text-muted);">No deliveries yet.</td>
                </tr>
              @endif
            </tbody>
          </table>
          

      
      </div>
    </section>
@endsection
