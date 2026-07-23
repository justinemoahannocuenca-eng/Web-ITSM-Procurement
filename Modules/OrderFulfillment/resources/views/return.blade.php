<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Nexora Returns</title>
<style>
   :root {
    --bg-header: #0B1E3D;
    --bg-dark: #1B3A6B;
    --bg-card: #0B1E3D;
    --text-light: #FFFFFF;
    --text-muted: #9FB3D1;
    --border-soft: rgba(255,255,255,0.08);
    --accent: #3B82F6;
    --pill: #16305c;
    --pill-border: #2c4373;
  }

  * { box-sizing: border-box; }

  body {
    margin: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: var(--bg-dark);
    color: var(--text-light);
  }

  /* ===== Navbar ===== */
  .navbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 40px;
    background: var(--bg-header);
    border-bottom: 1px solid var(--border-soft);
  }

.brand{
    display:flex;
    align-items:center;
    gap:14px;
}

.brand-logo{
    display:flex;
    align-items:center;
    gap:14px;
    text-decoration:none;
    color:inherit;
    cursor:pointer;
    transition:
        transform .25s ease,
        filter .25s ease;
}

.brand-logo:hover{
    transform:scale(1.06);
    filter:drop-shadow(0 8px 18px rgba(59,130,246,.45));
}

.brand-logo:active{
    transform:scale(.96);
}

.brand-logo:visited,
.brand-logo:link,
.brand-logo:hover,
.brand-logo:active{
    color:inherit;
}

.brand-logo .title{
    color:#FFFFFF;
}

.brand-logo .subtitle{
    color:#3B82F6;
}

  .logo {
    width: 46px;
    height: 50px;
    object-fit: contain;
  }

  .brand-text .title { font-size: 20px; font-weight: 700; letter-spacing: 1px; }
  .brand-text .subtitle { font-size: 11px; color: #3B82F6; letter-spacing: 1px; }

  .nav-links { display: flex; gap: 36px; }
  .nav-links a { color: var(--text-muted); text-decoration: none; font-size: 15px; font-weight: 500; }
  .nav-links a.active { color: var(--text-light); font-weight: 700; }

  .nav-links a:hover {
    color: var(--text-light);
    text-shadow: 0 0 0.4px currentColor, 0 0 0.4px currentColor;
  }
  
  /* ---------- Stats ---------- */
  .stats-row {
    display: flex;
    gap: 24px;
    padding: 32px 40px 10px;
    flex-wrap: wrap;
  }

  .stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-soft);
    border-radius: 12px;
    padding: 22px 28px;
    flex: 1;
    min-width: 200px;
  }

  .stat-card .label { color: var(--text-muted); font-size: 14px; font-weight: 600; margin-bottom: 10px; }
  .stat-card .value { font-size: 32px; font-weight: 700; }

  /* ---------- Main content ---------- */
  .content {
    display: flex;
    gap: 24px;
    padding: 28px 40px 60px 40px;
  }

  .panel {
    background: var(--bg-card);
    border-radius: 12px;
    overflow: hidden;
  }

  .returns-queue { flex: 2.5; }
  .side { flex: 1; display: flex; flex-direction: column; gap: 24px; }

  .panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    position: relative;
    gap: 16px;
  }

  .panel-header .title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    font-size: 16px;
    white-space: nowrap;
  }

  .panel-header .actions {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-muted);
    font-size: 14px;
  }

  /* ===== Search + Filter (working controls) ===== */
  .search-wrap {
    position: relative;
  }

  .search-wrap input {
    width: 170px;
    background: var(--pill);
    border: 1px solid var(--pill-border);
    border-radius: 20px;
    padding: 8px 14px 8px 32px;
    color: var(--text-light);
    font-size: 13px;
    outline: none;
    transition: width 0.15s ease, border-color 0.15s ease;
  }

  .search-wrap input:focus {
    width: 210px;
    border-color: var(--accent);
  }

  .search-wrap input::placeholder {
    color: var(--text-muted);
  }

  .search-icon {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    pointer-events: none;
    font-size: 12px;
  }

  .filter-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    background: var(--pill);
    border: 1px solid var(--pill-border);
    border-radius: 20px;
    padding: 8px 14px;
    color: var(--text-light);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    position: relative;
  }

  .filter-btn:hover,
  .filter-btn.active {
    border-color: var(--accent);
  }

  .filter-btn .caret {
    font-size: 10px;
    color: var(--text-muted);
    transition: transform 0.15s ease;
  }

  .filter-btn.open .caret {
    transform: rotate(180deg);
  }

  .filter-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #ff2f92;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 10px;
    line-height: 1.4;
    display: none;
  }

  .filter-panel {
    position: absolute;
    right: 24px;
    top: 56px;
    background: #16305c;
    border: 1px solid var(--pill-border);
    border-radius: 12px;
    padding: 14px 16px;
    width: 200px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.5);
    display: none;
    z-index: 30;
  }

  .filter-panel.show {
    display: block;
  }

  .filter-panel .filter-title {
    color: var(--text-muted);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 10px;
  }

  .filter-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 0;
    cursor: pointer;
    color: var(--text-light);
    font-size: 14px;
    font-weight: 600;
    user-select: none;
  }

  .filter-option input {
    width: 16px;
    height: 16px;
    accent-color: var(--accent);
    cursor: pointer;
  }

  .filter-overlay {
    position: fixed;
    inset: 0;
    z-index: 20;
    display: none;
  }

  .filter-overlay.show {
    display: block;
  }

  .no-results-row td {
    text-align: center;
    padding: 30px;
    color: var(--text-muted);
    font-size: 14px;
  }
  /* ===== end search + filter ===== */

  table { width: 100%; border-collapse: collapse; }

  thead th {
    text-align: left;
    padding: 14px 24px;
    font-size: 13px;
    color: #fff;
    border-bottom: 1px solid rgba(255,255,255,0.08);
  }

  tbody td { padding: 14px 24px; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.05); }
  tbody tr.return-row { cursor: pointer; }
  tbody tr.return-row:hover { background: rgba(255,255,255,0.04); }

  .order-id, .product { color: var(--text-muted); }
  .customer { font-weight: 600; }

  .status-badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 4px;
  }

  .status-high { background: #7F1D2E; color: #FCA5B1; }
  .status-med  { background: #6B4A1E; color: #FBD38D; }
  .status-refunded { background: #16532E; color: #86EFAC; }
  .status-inspecting { background: #2b3a5c; color: #cdd6f5; }

  .resolution-not-resellable { color: #f28b82; font-weight: 600; }

  .empty-row td { height: 38px; }

 
  table {
    width: 100%;
    border-collapse: collapse;
  }
 
  thead th {
    text-align: left;
    padding: 14px 24px;
    font-size: 14px;
    color: #fff;
    border-bottom: 1px solid rgba(255,255,255,0.08);
  }
 
  tbody td {
    padding: 14px 24px;
    font-size: 14px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
  }
 
  tbody tr:nth-child(even) {
    background: rgba(255,255,255,0.02);
  }
 
  .order-id, .product {
    color: var(--text-muted);
  }
 
  .customer {
    font-weight: 600;
  }
 
  .priority-low {
    background: #5A3A4A;
    color: #E8B8C8;
    padding: 3px 12px;
    border-radius: 5px;
    font-size: 11px;
    display: inline-block;
  }
 
  .priority-med {
    background: #6B4A1E;
    color: #FBD38D;
    padding: 3px 12px;
    border-radius: 5px;
    font-size: 11px;
    display: inline-block;
  }
 
  .priority-high {
    background: #7F1D2E;
    color: #FCA5B1;
    padding: 3px 12px;
    border-radius: 5px;
    font-size: 11px;
    display: inline-block;
  }
 
  .btn-prepare {
    display: inline-block;
    background: var(--bg-dark);
    color: var(--text-light);
    font-weight: 700;
    font-size: 13px;
    padding: 6px 14px;
    border-radius: 20px;
    text-align: center;
    border: none;
    cursor: pointer;
  }
 
  .btn-prepare:hover {
    background: #244a80;
  }
 
  .empty-row td {
    height: 38px;
  }
 
  .activity-list {
    padding: 8px 0;
  }
 
  .activity-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 24px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    font-size: 14px;
  }
 
  .activity-item:last-child {
    border-bottom: none;
  }
 
  .activity-icon {
    width: 18px;
    text-align: center;
    flex-shrink: 0;
    margin-top: 2px;
  }

  /* Return reasons / refund activity lists */
  .reason-list, .refund-list { padding: 8px 0; }

  .reason-item, .refund-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 24px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    font-size: 14px;
  }

  .reason-item:last-child, .refund-item:last-child { border-bottom: none; }
  .reason-icon, .refund-icon { width: 18px; text-align: center; flex-shrink: 0; }
  .refund-icon { color: #4ade80; }

  /* ============================================
     Blur + modal mechanism
     ============================================ */
  #pageContent {
    transition: filter 0.25s ease;
  }

  #pageContent.blurred {
    filter: blur(4px);
  }

  .overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(5, 12, 28, 0.45);
    align-items: center;
    justify-content: center;
    z-index: 100;
  }

  .overlay.active { display: flex; }

  .modal {
    width: 620px;
    max-width: 90vw;
    background: #16305c;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.4);
  }

  .modal-header { background: #0f2549; padding: 20px 28px; }
  .modal-header h2 { margin: 0; color: #fff; font-size: 18px; }
  .modal-header h2 span { color: #8ea3cc; font-weight: 400; }
  .modal-header p { margin: 4px 0 0; color: #8ea3cc; font-size: 13px; }

  .modal-tags {
    display: flex;
    gap: 10px;
    padding: 18px 28px 0;
  }

  .tag {
    display: inline-block;
    font-size: 12px;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 6px;
  }

  .tag.priority { background: #7F1D2E; color: #FCA5B1; }
  .tag.review { background: #16532E; color: #86EFAC; }

  .modal-body { padding: 20px 28px 0; }
  .modal-body .field-label { margin: 0 0 6px; font-size: 12px; color: #8ea3cc; }
  .modal-body .reason-title { margin: 0 0 10px; font-size: 16px; font-weight: 700; color: #fff; }
  .modal-body .reason-desc { margin: 0 0 20px; font-size: 14px; color: #b9c6e3; line-height: 1.5; }

  .proof-row { display: flex; gap: 12px; margin-bottom: 20px; }

  .proof-thumb {
    width: 80px;
    height: 70px;
    background: #1c3766;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: #6f89c2;
  }

  .meta-row {
    display: flex;
    gap: 40px;
    padding: 18px 0;
    border-top: 1px solid rgba(255,255,255,0.08);
    margin-top: 4px;
  }

  .meta-row .field-value { margin: 0; font-size: 15px; color: #fff; font-weight: 700; }

  .modal-footer {
    display: flex;
    gap: 12px;
    padding: 20px 28px;
    border-top: 1px solid rgba(255,255,255,0.08);
  }

  .btn {
    flex: 1;
    padding: 14px;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
  }

  .btn-close { background: var(--pill); color: var(--text-light); border: 1px solid var(--pill-border); }
  .btn-close:hover { background: #1c3766; }

  .btn-accept { background: #16a34a; color: #eafff0; }
  .btn-accept:hover { background: #1bbf58; }
</style>
</head>
<body>
<div class="top-strip"></div>

  <!-- ============================================
       Everything the user should see BLURRED while
       the modal is open goes inside #pageContent.
       ============================================ -->
  <div id="pageContent">

    <!-- Navbar -->
    <div class="navbar">
      <form method="POST" action="{{ route('order-fulfillment.logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="brand brand-logo" style="background:none;border:none;padding:0;font:inherit;">
          <img class="logo" src="{{ asset('orderfulfillment/logo/Nexora_Logo_Transparent.png') }}" alt="Nexora Logo">
          <div class="brand-text">
              <div class="title">NEXORA</div>
              <div class="subtitle">ENTERPRISE RESOURCE PLANNING</div>
          </div>
        </button>
      </form>
      <div class="nav-links">
        <a href="{{ route('order-fulfillment.dashboard') }}">Dashboard</a>
        <a href="{{ route('order-fulfillment.orders') }}">Orders</a>
        <a href="{{ route('order-fulfillment.packing') }}">Packing</a>
        <a href="{{ route('order-fulfillment.shipping') }}">Shipping</a>
        <a href="{{ route('order-fulfillment.return') }}" class="active">Returns</a>
      </div>
    </div>

<div class="stats-row">

  <div class="stat-card">
    <div class="label">Return requests pending</div>
    <div class="value">{{ $pendingReturns }}</div>
  </div>

  <div class="stat-card">
    <div class="label">In transit back</div>
    <div class="value">0</div>
  </div>

  <div class="stat-card">
    <div class="label">Refunds processed today</div>
    <div class="value">{{ $refundedToday }}</div>
  </div>

  <div class="stat-card">
    <div class="label">Return rate (30 days)</div>
    <div class="value">0%</div>
  </div>

</div>

    <div class="content">

      <div class="panel returns-queue">
        <div class="panel-header">
          <div class="title">📋 Return requests</div>
          <div class="actions">
            <div class="search-wrap">
              <span class="search-icon">🔍</span>
              <input type="text" id="returnSearch" placeholder="Search..." autocomplete="off">
            </div>

            <button id="filterBtn" class="filter-btn">
              Filter <span class="caret">▾</span>
              <span id="filterBadge" class="filter-badge">1</span>
            </button>

            <div id="filterPanel" class="filter-panel">
              <div class="filter-title">Status</div>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="" class="status-check" checked>
                All
              </label>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="High" class="status-check">
                High
              </label>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="Med" class="status-check">
                Med
              </label>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="Refunded" class="status-check">
                Refunded
              </label>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="Inspecting" class="status-check">
                Inspecting
              </label>
            </div>
          </div>
        </div>
        <table>
          <thead>
            <tr>
              <th>Order Id</th>
              <th>Customer</th>
              <th>Product</th>
              <th>Reason</th>
              <th>Status</th>
              <th>Resolution</th>
            </tr>
          </thead>
<tbody id="returnsTableBody">

@foreach($returns as $return)
<tr class="return-row"
    onclick="openReturnModal(this)"
    data-return-id="{{ $return->id }}"
    data-order-id="{{ $return->order_id }}"
    data-customer="{{ $return->customer_name }}"
    data-product="{{ $return->product_name }}"
    data-reason="{{ $return->reason }}"
    data-status="{{ $return->status }}"
    data-resolution="{{ $return->resolution }}"
>
    <td class="order-id">{{ $return->order_id }}</td>
    <td class="customer">{{ $return->customer_name }}</td>
    <td class="product">{{ $return->product_name }}</td>
    <td>{{ $return->reason }}</td>

    <td>
        <span class="status-badge">
            {{ $return->status }}
        </span>
    </td>

    <td>{{ $return->resolution }}</td>
</tr>
@endforeach

</tbody>
        </table>
      </div>

      <div class="side">
        <div class="panel">
          <div class="panel-header">
            <div class="title">📊 Return reasons</div>
          </div>
          <div class="reason-list">
            <div class="reason-item"><span class="reason-icon">⚠️</span><span>Defective — 0%</span></div>
            <div class="reason-item"><span class="reason-icon">📦</span><span>Wrong item — 0%</span></div>
            <div class="reason-item"><span class="reason-icon">👤</span><span>Changed mind — 0%</span></div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <div class="title">📈 Refund activity</div>
          </div>
          <div class="refund-list">
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ============================================
       Modal lives OUTSIDE #pageContent so it never
       gets blurred itself.
       ============================================ -->
  <div class="overlay" id="returnOverlay">
    <div class="modal">
      <div class="modal-header">
        <h2>Return request <span id="modalOrderId">#ORD-4821</span></h2>
        <p id="modalCustomerProduct">Maria Santos · Wireless Headphone</p>
      </div>

      <div class="modal-tags">
        <span class="tag priority" id="modalPriority">High priority</span>
        <span class="tag review" id="modalReviewStatus">Pending Review</span>
      </div>

      <div class="modal-body">
        <p class="field-label">Reason for return</p>
        <p class="reason-title" id="modalReasonTitle">Defective - item stopped working after 2 days</p>
        <p class="reason-desc" id="modalReasonDesc">Customer reports the left earcup lost audio and the device won't hold a charge. No visible external damage.</p>

        <div class="meta-row">
          <div>
            <p class="field-label">Order value</p>
            <p class="field-value" id="modalValue">₱67.67</p>
          </div>
          <div>
            <p class="field-label">Requested on</p>
            <p class="field-value" id="modalRequestedOn">July 2, 2026</p>
          </div>
          <div>
            <p class="field-label">In transit</p>
            <p class="field-value" id="modalInTransit">Yes</p>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-close" onclick="closeReturnModal()">Close</button>
        <button class="btn btn-accept" id="modalAcceptBtn" onclick="closeReturnModal()">Accept return</button>
      </div>
    </div>
  </div>

  <div class="filter-overlay" id="filterOverlay"></div>

  <script>
    // Demo data keyed by return id. Swap this for a fetch() call to your
    // backend if you want live data instead of hardcoded values.


// Returns created by the admin cancelling an order (rather than a customer
// requesting a return) have nothing to accept/reject — they're just moving
// stock back to the warehouse — so the modal shows Close only, no Accept.
const ADMIN_CANCEL_REASONS = ['Cancelled while shipping', 'Cancelled before shipping'];

function openReturnModal(row)
{
    document.getElementById('modalOrderId').textContent =
        row.dataset.orderId;

    document.getElementById('modalCustomerProduct').textContent =
        row.dataset.customer + ' · ' + row.dataset.product;

    document.getElementById('modalPriority').textContent =
        row.dataset.status;

    document.getElementById('modalReviewStatus').textContent =
        row.dataset.resolution;

    document.getElementById('modalReasonTitle').textContent =
        row.dataset.reason;

    const isAdminCancellation = ADMIN_CANCEL_REASONS.includes(row.dataset.reason);
    document.getElementById('modalAcceptBtn').style.display =
        isAdminCancellation ? 'none' : '';

    document.getElementById('pageContent')
        .classList.add('blurred');

    document.getElementById('returnOverlay')
        .classList.add('active');
}

    function closeReturnModal() {
      document.getElementById('pageContent').classList.remove('blurred');
      document.getElementById('returnOverlay').classList.remove('active');
    }

    /* ===================== Search + Filter (working) ===================== */
    const returnRows     = Array.from(document.querySelectorAll('.return-row'));
    const searchInput    = document.getElementById('returnSearch');
    const filterBtn      = document.getElementById('filterBtn');
    const filterPanel    = document.getElementById('filterPanel');
    const filterOverlay  = document.getElementById('filterOverlay');
    const filterBadge    = document.getElementById('filterBadge');
    const noResultsRow   = document.getElementById('noResultsRow');
    const statusChecks   = document.querySelectorAll('.status-check');

    function activeStatus() {
      const checked = Array.from(statusChecks).find(c => c.checked);
      return checked ? checked.value : '';
    }

    function applyReturnFilters() {
      const query = searchInput.value.trim().toLowerCase();
      const active = activeStatus();
      let visibleCount = 0;

      returnRows.forEach(function (row) {
        const d = row.dataset;
        const haystack = [d.orderId, d.customer, d.product, d.reason, d.status, d.resolution]
          .join(' ')
          .toLowerCase();

        const matchesSearch = query === '' || haystack.includes(query);
        const matchesStatus = active === '' || d.status === active;
        const visible = matchesSearch && matchesStatus;

        row.style.display = visible ? '' : 'none';
        if (visible) visibleCount++;
      });

      noResultsRow.style.display = visibleCount === 0 ? '' : 'none';

      if (active !== '') {
        filterBtn.classList.add('active');
        filterBadge.style.display = 'inline-block';
        filterBadge.textContent = '1';
      } else {
        filterBtn.classList.remove('active');
        filterBadge.style.display = 'none';
      }
    }

    function openFilterPanel() {
      filterPanel.classList.add('show');
      filterOverlay.classList.add('show');
      filterBtn.classList.add('open');
    }

    function closeFilterPanel() {
      filterPanel.classList.remove('show');
      filterOverlay.classList.remove('show');
      filterBtn.classList.remove('open');
    }

    filterBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      filterPanel.classList.contains('show') ? closeFilterPanel() : openFilterPanel();
    });

    filterOverlay.addEventListener('click', closeFilterPanel);

    statusChecks.forEach(function (c) {
      c.addEventListener('change', applyReturnFilters);
    });

    searchInput.addEventListener('input', applyReturnFilters);
    /* =================== end Search + Filter =================== */
  </script>

</body>
</html>