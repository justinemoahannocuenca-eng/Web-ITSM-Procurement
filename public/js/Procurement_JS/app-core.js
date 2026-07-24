
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

  async function loadNotifications(panel){
    if(!panel) return;
    // No "Loading…" placeholder — the panel keeps its current content until the
    // fresh list is ready, so opening it never flashes a loading state.
    try{
      const headers = { 'X-Requested-With': 'XMLHttpRequest' };
      // Fetch recent requisitions
      const [reqRes, delRes] = await Promise.all([
        fetch(procurementUrl('requisitions'), { headers }),
        fetch(procurementUrl('deliveries'), { headers })
      ]);
      const reqJson = await safeJson(reqRes);
      const delJson = await safeJson(delRes);
      const reqs = Array.isArray(reqJson)
        ? reqJson
        : (reqJson && Array.isArray(reqJson.data) ? reqJson.data : []);
      const dels = Array.isArray(delJson)
        ? delJson
        : (delJson && Array.isArray(delJson.data) ? delJson.data : []);

      // Build items
      const items = [];
      // Prioritize pending requisitions
      (reqs || []).slice(0,5).forEach(r => {
        items.push({ type: 'req', title: r.rq || r.ref || r.id || 'Requisition', text: `${r.item || ''} · Qty ${r.qty || r.quantity || ''}`, meta: r.requester || r.dept || '' });
      });
      // Add deliveries with relevant statuses
      (dels || []).slice(0,5).forEach(d => {
        items.push({ type: 'del', title: d.dr || d.id || d.ref || 'Delivery', text: `${d.items || d.items || ''} · Qty ${d.qty || ''}`, meta: d.status || '' });
      });

      if(items.length === 0){
        panel.innerHTML = `<div class="notif-item ok"><span class="notif-icon">✓</span><div class="notif-content"><strong>No alerts</strong>You have no new notifications right now.<small>System · live</small></div></div>`;
        updateNavCounts(0,0);
        return;
      }

      panel.innerHTML = '';
      let reqCount = 0, delCount = 0;
      items.forEach(it => {
        const div = document.createElement('div');
        div.className = 'notif-item ' + (it.type === 'req' ? 'warn' : 'ok');
        div.innerHTML = `<span class="notif-icon">${it.type==='req' ? 'R' : 'D'}</span><div class="notif-content"><strong>${escapeHtml(it.title)}</strong><div>${escapeHtml(it.text)}</div><small>${escapeHtml(it.meta)}</small></div>`;
        panel.appendChild(div);
        if(it.type === 'req') reqCount++; else if(it.type === 'del') delCount++;
      });
      updateNavCounts(reqCount, delCount);
    }catch(err){
      panel.innerHTML = `<div style="padding:12px;color:#c34">Unable to load notifications</div>`;
      console.error('loadNotifications', err);
    }
  }

  async function safeJson(res){
    try{ return await res.json(); }catch(e){ return null; }
  }

  function escapeHtml(s){ return (s||'').toString().replace(/[&<>"]+/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]||c)); }

  function updateNavCounts(reqCount, delCount){
    const reqBadge = document.querySelector("a[href*='requisitions'] .nav-badge");
    const delBadge = document.querySelector("a[href*='deliveries'] .nav-badge");
    if(reqBadge){ reqBadge.textContent = reqCount; reqBadge.classList.toggle('red', reqCount>0); }
    if(delBadge){ delBadge.textContent = delCount; delBadge.classList.toggle('red', delCount>0); }
  }

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
      if(!res.ok) return;
      const j = await res.json();
      if(!j) return;
      if(j.cards){
        setLiveStat('dash-stat-po',  j.cards.activePos);
        setLiveStat('dash-stat-sup', j.cards.suppliers);
        setLiveStat('dash-stat-req', j.cards.requisitions);
        setLiveStat('dash-stat-inv', j.cards.deliveries);
      }
      if(j.badges){
        setLiveBadge("a[href*='purchase-orders'] .nav-badge", j.badges.purchaseOrders);
        setLiveBadge("a[href*='requisitions'] .nav-badge", j.badges.requisitions);
        setLiveBadge("a[href*='deliveries'] .nav-badge", j.badges.deliveries);
      }
    }catch(err){ /* keep last known values */ }
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
