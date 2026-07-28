/* ---------- Page switching ---------- */
  const ALL_PAGES = ['dashboard','purchase-orders','suppliers','requisitions','invoices','deliveries','approvals','reports'];
  function toggleNotifPanel(e){
    e?.stopPropagation();
    const panel = document.getElementById('notif-panel');
    if(!panel) return;
    const willOpen = !panel.classList.contains('open');
    panel.classList.toggle('open');
    if(willOpen){ loadNotifications(panel); }
  }

  /* ---------- Authoritative requisition counts ----------
     The sidebar badge and the Dashboard "REQUISITIONS" card were each fed by
     their own server number (one blank on the Requisitions page itself, one
     counting rows across ALL clients on Dashboard), so they never agreed with
     what the Requisitions page table actually shows. The Requisitions page's
     own status chart is the one number that is always correctly scoped, so
     every other page now borrows it instead of trusting its own count. */
  const REQ_STATS_CACHE_KEY = 'nexora_req_stats_v1';
  const REQ_STATS_MAX_AGE_MS = 5 * 60 * 1000;

  function readReqStatsFromChart(root){
    const chart = root.getElementById ? root.getElementById('requisition-status-chart') : null;
    if(!chart) return null;
    let total = 0, pending = 0;
    chart.querySelectorAll('.status-chart-item').forEach(item => {
      const val = parseInt((item.querySelector('.status-count')?.textContent || '0').trim(), 10) || 0;
      total += val;
      if(item.dataset.status === 'pending') pending = val;
    });
    return { total, pending };
  }
  function cacheReqStats(stats){
    try{ sessionStorage.setItem(REQ_STATS_CACHE_KEY, JSON.stringify({ ...stats, ts: Date.now() })); }catch(e){}
  }
  function readCachedReqStats(){
    try{
      const raw = sessionStorage.getItem(REQ_STATS_CACHE_KEY);
      if(!raw) return null;
      const parsed = JSON.parse(raw);
      if(Date.now() - parsed.ts > REQ_STATS_MAX_AGE_MS) return null;
      return parsed;
    }catch(e){ return null; }
  }
  async function fetchReqDoc(){
    const res = await fetch(procurementUrl('requisitions'), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const html = await res.text();
    return new DOMParser().parseFromString(html, 'text/html');
  }
  async function getAuthoritativeReqStats(){
    const live = readReqStatsFromChart(document);
    if(live){ cacheReqStats(live); return live; }
    const cached = readCachedReqStats();
    if(cached) return cached;
    try{
      const remote = readReqStatsFromChart(await fetchReqDoc());
      if(remote) cacheReqStats(remote);
      return remote;
    }catch(e){ return null; }
  }
  function applyReqStats(stats){
    if(!stats) return;
    setLiveStat('dash-stat-req', stats.total);
    const reqBadge = document.querySelector("a[href*='requisitions'] .nav-badge");
    if(reqBadge){
      if(reqBadge.textContent.trim() !== String(stats.pending)){
        reqBadge.textContent = stats.pending;
        reqBadge.classList.remove('badge-pulse'); void reqBadge.offsetWidth; reqBadge.classList.add('badge-pulse');
      }
      reqBadge.classList.toggle('red', stats.pending > 0);
    }
  }

  function rowsFromTable(root, tableId, mapFn){
    const table = root.getElementById ? root.getElementById(tableId) : null;
    if(!table) return [];
    return Array.from(table.querySelectorAll('tbody tr[data-id]')).map(mapFn);
  }
  const mapReqRow = tr => ({
    ref: tr.querySelector('td a')?.textContent.trim() || '',
    item: tr.children[1]?.textContent.trim() || '',
    qty: tr.children[2]?.textContent.trim() || '',
    department: tr.children[4]?.textContent.trim() || '',
    requester: tr.children[5]?.textContent.trim() || '',
    status: tr.dataset.status || 'pending',
  });
  const mapDelRow = tr => ({
    ref: tr.dataset.ship || tr.children[0]?.textContent.trim() || '',
    item: (tr.dataset.items || tr.children[3]?.textContent.trim() || '').split(',')[0].trim(),
    status: tr.dataset.status || '',
  });
  async function getRequisitionRows(){
    if(document.getElementById('requisitions-table')) return rowsFromTable(document, 'requisitions-table', mapReqRow);
    try{ return rowsFromTable(await fetchReqDoc(), 'requisitions-table', mapReqRow); }catch(e){ return []; }
  }
  async function getDeliveryRows(){
    if(document.getElementById('deliveries-table')) return rowsFromTable(document, 'deliveries-table', mapDelRow);
    try{
      const res = await fetch(procurementUrl('deliveries'), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
      return rowsFromTable(doc, 'deliveries-table', mapDelRow);
    }catch(e){ return []; }
  }

  async function loadNotifications(panel){
    if(!panel) return;
    // No "Loading…" placeholder — the panel keeps its current content until the
    // fresh list is ready, so opening it never flashes a loading state.
    try{
      const [reqStats, reqRows, delRows] = await Promise.all([
        getAuthoritativeReqStats(),
        getRequisitionRows(),
        getDeliveryRows()
      ]);

      const pendingReqs = reqRows.filter(r => (r.status || 'pending') === 'pending');
      const activeDels = delRows.filter(r => ['pending','scheduled','intransit','shipment'].includes(r.status || ''));

      const items = [];
      pendingReqs.slice(0,5).forEach(r => {
        items.push({ type: 'req', title: r.ref || 'Requisition', text: `${r.item || ''} · Qty ${r.qty || ''}`, meta: r.requester || r.department || '' });
      });
      activeDels.slice(0,5).forEach(d => {
        items.push({ type: 'del', title: d.ref || 'Delivery', text: `${d.item || ''}`, meta: d.status || '' });
      });

      if(items.length === 0){
        panel.innerHTML = `<div class="notif-item ok"><span class="notif-icon">✓</span><div class="notif-content"><strong>No alerts</strong>You have no new notifications right now.<small>System · live</small></div></div>`;
      } else {
        panel.innerHTML = '';
        items.forEach(it => {
          const div = document.createElement('div');
          div.className = 'notif-item ' + (it.type === 'req' ? 'warn' : 'ok');
          div.innerHTML = `<span class="notif-icon">${it.type==='req' ? 'R' : 'D'}</span><div class="notif-content"><strong>${escapeHtml(it.title)}</strong><div>${escapeHtml(it.text)}</div><small>${escapeHtml(it.meta)}</small></div>`;
          panel.appendChild(div);
        });
      }
      // Never re-derive the requisitions badge from this panel's own item
      // count — reqStats (the status chart) is the correct source; only the
      // deliveries badge comes from what we counted here.
      applyReqStats(reqStats);
      const delBadge = document.querySelector("a[href*='deliveries'] .nav-badge");
      if(delBadge){ delBadge.textContent = activeDels.length; delBadge.classList.toggle('red', activeDels.length>0); }
    }catch(err){
      panel.innerHTML = `<div style="padding:12px;color:#c34">Unable to load notifications</div>`;
      console.error('loadNotifications', err);
    }
  }

  function escapeHtml(s){ return (s||'').toString().replace(/[&<>"]+/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]||c)); }

  document.addEventListener('click', (e)=>{
    const panel = document.getElementById('notif-panel');
    if(panel && !panel.contains(e.target) && !e.target.closest('.notif-badge')){
      panel.classList.remove('open');
    }
  });

  /* ---------- Live stats: poll dashboard cards + sidebar badges (no refresh) ---------- */
  function setLiveStat(id, val){
    const el = document.getElementById(id);
    if(!el || val == null) return;
    if(String(el.textContent).trim() !== String(val)){
      el.textContent = val;
      el.classList.remove('bump'); void el.offsetWidth; el.classList.add('bump');
    }
  }
  function setLiveBadge(selector, val){
    const el = document.querySelector(selector);
    if(!el || val == null) return;
    if(el.textContent.trim() !== String(val)){
      el.textContent = val;
      el.classList.toggle('red', Number(val) > 0);
      el.classList.remove('badge-pulse'); void el.offsetWidth; el.classList.add('badge-pulse');
    }
  }
  async function pollLiveStats(){
    try{
      const res = await fetch(procurementUrl('live-stats'), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
      if(res.ok){
        const j = await res.json();
        if(j){
          if(j.cards){
            setLiveStat('dash-stat-po',  j.cards.activePos);
            setLiveStat('dash-stat-sup', j.cards.suppliers);
            setLiveStat('dash-stat-inv', j.cards.deliveries);
          }
          if(j.badges){
            setLiveBadge("a[href*='purchase-orders'] .nav-badge", j.badges.purchaseOrders);
            setLiveBadge("a[href*='deliveries'] .nav-badge", j.badges.deliveries);
          }
        }
      }
    }catch(err){ /* keep last known values */ }
    // Requisitions total/pending never come from live-stats — that endpoint
    // isn't scoped the same way the Requisitions page is — always recompute
    // from the authoritative source instead.
    applyReqStats(await getAuthoritativeReqStats());
  }
  window.pollLiveStats = pollLiveStats;
  document.addEventListener('DOMContentLoaded', ()=>{
    pollLiveStats();
    setInterval(pollLiveStats, 15000);
  });

  function showPage(page, navEl){
    ALL_PAGES.forEach(p => {
      const sec = document.getElementById('page-' + p);
      if(sec) sec.classList.toggle('hidden', p !== page);
    });
    const active = document.getElementById('page-' + page);
    if(active){
      active.style.animation = 'none';
      void active.offsetWidth;
      active.style.animation = '';
    }
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    if(navEl){ navEl.classList.add('active'); }

    // Trigger entrance animations that depend on layout
    if(page === 'dashboard') animateDashboard();
    if(page === 'deliveries') animateDeliveryPipeline();
    
    // Update status counts when viewing status chart pages
    if(page === 'requisitions' || page === 'purchase-orders' || page === 'deliveries'){
      updateStatusCounts();
    }
  }

  // Hook called after adding a record and when opening a status-chart page.
  // It must exist: several flows (submitAddPO / submitAddDelivery / showPage)
  // call it, and while it was missing those handlers threw a ReferenceError on
  // this line — right before closing their modal — which is why the Add PO and
  // Log Delivery modals stayed open after a successful submit.
  //
  // The per-status chart counts are rendered server-side from full-table
  // totals (the PO table itself only loads the 8 most recent rows), so we must
  // NOT recompute them from the DOM here — that would undercount. The live
  // cards/badges are refreshed separately by pollLiveStats(); the chart totals
  // refresh on the next page load.
  function updateStatusCounts(){ /* intentionally a no-op — see note above */ }

  /* ---------- Theme (light / dark) ---------- */
  function applyStoredTheme(){
    const saved = localStorage.getItem('procurement-theme');
    if(saved) document.documentElement.setAttribute('data-theme', saved);
  }
  function toggleTheme(){
    const current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('procurement-theme', next);
  }
  applyStoredTheme();

  /* ---------- Profile dropdown ---------- */
  function toggleProfileMenu(e){
    if(e) e.stopPropagation();
    document.getElementById('profile-dropdown')?.classList.toggle('open');
  }
  document.addEventListener('click', (e) => {
    const menu = document.querySelector('.profile-menu');
    if(menu && !menu.contains(e.target)){
      document.getElementById('profile-dropdown')?.classList.remove('open');
    }
  });

  // Requisitions page tabs: "Requests" (the requisition table) and
  // "Defect Items" (placeholder table, built out later).
  function switchReqTab(tab, el){
    document.querySelectorAll('#req-tabs .tab').forEach(t => t.classList.remove('active'));
    if(el) el.classList.add('active');
    const requests = document.getElementById('req-tab-requests');
    const defects = document.getElementById('req-tab-defects');
    if(requests) requests.classList.toggle('hidden', tab !== 'requests');
    if(defects) defects.classList.toggle('hidden', tab !== 'defects');
  }

  function animateDashboard(){
    // Bars grow
    document.querySelectorAll('#dash-category-bars .bar').forEach(b => { b.style.height = '0px'; });
    setTimeout(()=>{
      document.querySelectorAll('#dash-category-bars .bar').forEach(b => { b.style.height = (b.dataset.h||0) + 'px'; });
    }, 60);
    // Donut redraw
    document.querySelectorAll('#po-status-donut .donut-seg').forEach(seg => {
      seg.setAttribute('stroke-dasharray', '0 427.3');
    });
    setTimeout(()=>{
      document.querySelectorAll('#po-status-donut .donut-seg').forEach(seg => {
        seg.setAttribute('stroke-dasharray', seg.dataset.dasharray);
        seg.setAttribute('stroke-dashoffset', seg.dataset.dashoffset);
      });
    }, 120);
    // Top supplier bars
    document.querySelectorAll('#page-dashboard .ts-bar-fill').forEach(bar => { bar.style.width = '0'; });
    setTimeout(()=>{
      document.querySelectorAll('#page-dashboard .ts-bar-fill').forEach(bar => { bar.style.width = bar.dataset.w; });
    }, 240);
  }

  function animateDeliveryPipeline(){
    document.querySelectorAll('#page-deliveries .ts-bar-fill').forEach(bar => { bar.style.width = '0'; });
    setTimeout(()=>{
      document.querySelectorAll('#page-deliveries .ts-bar-fill').forEach(bar => { bar.style.width = bar.dataset.w; });
    }, 180);
  }

  /* ---------- Toasts ---------- */
  function showToast(message, kind){
    const stack = document.getElementById('toast-stack');
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `<span class="toast-dot ${kind}"></span><span>${message}</span>`;
    stack.appendChild(toast);
    setTimeout(()=>{
      toast.classList.add('leaving');
      setTimeout(()=> toast.remove(), 260);
    }, 2600);
  }

  /* ---------- Stat helpers ---------- */
  function bumpStat(id, delta){
    const el = document.getElementById(id);
    if(!el) return;
    el.textContent = Math.max(0, parseInt(el.textContent,10) + delta);
    el.classList.remove('bump');
    void el.offsetWidth;
    el.classList.add('bump');
  }

  function refreshTabCounts(){
    const rows = document.querySelectorAll('#approval-tabs ~ .queue-row, .queue-row');
    const all = document.querySelectorAll('.queue-row').length;
    const po = document.querySelectorAll('.queue-row[data-type="po"]').length;
    const req = document.querySelectorAll('.queue-row[data-type="req"]').length;
    const inv = document.querySelectorAll('.queue-row[data-type="inv"]').length;
    const countAllEl = document.getElementById('count-all');
    if(countAllEl) countAllEl.textContent = all;
    const countPoEl = document.getElementById('count-po');
    if(countPoEl) countPoEl.textContent = po;
    const countReqEl = document.getElementById('count-req');
    if(countReqEl) countReqEl.textContent = req;
    const countInvEl = document.getElementById('count-inv');
    if(countInvEl) countInvEl.textContent = inv;
    const approvalBadgeEl = document.getElementById('approval-nav-badge');
    if(approvalBadgeEl) approvalBadgeEl.textContent = all;
    const statPendingEl = document.getElementById('stat-pending');
    if(statPendingEl) statPendingEl.textContent = all;
    const sub = document.getElementById('stat-pending-sub');
    if(sub) sub.textContent = all === 0 ? '✓ All clear' : '● Needs action';
    checkEmpty();
  }

  function checkEmpty(){
    const activeFilter = document.querySelector('#approval-tabs .tab.active')?.dataset.filter || 'all';
    const visible = [...document.querySelectorAll('.queue-row')].filter(r =>
      !r.classList.contains('removing') && (activeFilter==='all' || r.dataset.type===activeFilter)
    );
    const emptyEl = document.getElementById('queue-empty');
    if(emptyEl) emptyEl.classList.toggle('show', visible.length === 0);
  }

  /* ---------- Approve / Reject ---------- */
  function handleDecision(btn, action){
    const row = btn.closest('.queue-row');
    const ref = row.dataset.ref;
    btn.closest('.qactions').querySelectorAll('button').forEach(b => b.disabled = true);
    row.classList.add('removing');

    if(action === 'approve'){
      bumpStat('stat-approved', 1);
      showToast(`${ref} approved`, 'ok');
    } else {
      bumpStat('stat-rejected', 1);
      showToast(`${ref} rejected`, 'no');
    }

    setTimeout(()=>{
      row.remove();
      refreshTabCounts();
    }, 340);
  }