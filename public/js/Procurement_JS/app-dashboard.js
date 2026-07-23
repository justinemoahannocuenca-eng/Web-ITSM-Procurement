  /* ---------- Tabs (filter queue) ---------- */
  function setActiveFilter(filter){
    document.querySelectorAll('#approval-tabs .tab').forEach(t=>{
      t.classList.toggle('active', t.dataset.filter === filter);
    });
    document.querySelectorAll('.queue-row').forEach(row=>{
      const match = filter === 'all' || row.dataset.type === filter;
      row.classList.toggle('filtered-out', !match);
    });
    checkEmpty();
  }
  document.querySelectorAll('#approval-tabs .tab').forEach(tab=>{
    tab.addEventListener('click', ()=> setActiveFilter(tab.dataset.filter));
  });

  function filterQueueByType(type){
    setActiveFilter(type);
    document.getElementById('approval-tabs').scrollIntoView({behavior:'smooth', block:'nearest'});
    const labelMap = { po:'Purchase Orders', req:'Requisitions', inv:'Invoices', all:'all requests' };
    showToast(`Filtered queue to ${labelMap[type]}`, 'info');
  }

  /* ---------- Donut chart: draw-in + hover sync + click filter ---------- */
  function initDonut(){
    const segs = document.querySelectorAll('.donut-seg');
    // draw-in animation: start collapsed, then apply real dasharray/offset
    requestAnimationFrame(()=>{
      setTimeout(()=>{
        segs.forEach(seg=>{
          seg.setAttribute('stroke-dasharray', seg.dataset.dasharray);
          seg.setAttribute('stroke-dashoffset', seg.dataset.dashoffset);
        });
      }, 120);
    });

    const centerVal = document.getElementById('donut-center-val');
    const centerLabel = document.getElementById('donut-center-label');
    const hole = document.getElementById('donut-center');
    const defaultVal = centerVal.textContent;

    function light(type){
      segs.forEach(seg=>{
        const match = seg.dataset.type === type;
        seg.classList.toggle('dim', type && !match);
        seg.classList.toggle('raise', type && match);
      });
      document.querySelectorAll('.legend-row').forEach(row=>{
        row.style.background = (type && row.dataset.type === type) ? 'var(--bg)' : '';
      });
      if(type){
        const seg = document.querySelector(`.donut-seg[data-type="${type}"]`);
        centerVal.textContent = seg.dataset.pct + '%';
        centerLabel.textContent = seg.dataset.label;
        hole.classList.add('active');
      } else {
        centerVal.textContent = defaultVal;
        centerLabel.textContent = 'total';
        hole.classList.remove('active');
      }
    }

    segs.forEach(seg=>{
      seg.addEventListener('mouseenter', ()=> light(seg.dataset.type));
      seg.addEventListener('mouseleave', ()=> light(null));
      seg.addEventListener('click', ()=> filterQueueByType(seg.dataset.type === 'other' ? 'all' : seg.dataset.type));
    });
    document.querySelectorAll('.legend-row').forEach(row=>{
      row.addEventListener('mouseenter', ()=> light(row.dataset.type));
      row.addEventListener('mouseleave', ()=> light(null));
    });
  }

  /* ---------- Report date-range chips ---------- */
  const rangeData = {
    mtd:    { label: 'Jul 1 – Jul 7, 2026',   values: [142,158,149,171,164,189], months:['Feb','Mar','Apr','May','Jun','Jul'] },
    quarter:{ label: 'Apr 1 – Jul 7, 2026',   values: [149,171,164,189,201,214], months:['Apr','May','Jun','Jul','Aug*','Sep*'] },
    ytd:    { label: 'Jan 1 – Jul 7, 2026',   values: [131,138,149,171,164,189,201], months:['Jan','Feb','Mar','Apr','May','Jun','Jul'] },
    custom: { label: 'Choose a custom range', values: [142,158,149,171,164,189], months:['Feb','Mar','Apr','May','Jun','Jul'] }
  };
  document.querySelectorAll('#report-chips .chip').forEach(chip=>{
    chip.addEventListener('click', ()=>{
      document.querySelectorAll('#report-chips .chip').forEach(c=>c.classList.remove('active'));
      chip.classList.add('active');
      const range = rangeData[chip.dataset.range];
      document.getElementById('date-range-label').lastChild.textContent = ' ' + range.label;
      const maxVal = Math.max(...range.values);
      const bars = document.getElementById('spend-bars');
      bars.innerHTML = range.values.map((v,i)=>{
        const isLast = i === range.values.length - 1;
        const h = Math.round((v/maxVal)*170);
        return `<div class="bar-col">
                  <div class="bar-val">$${v}k</div>
                  <div class="bar" style="height:0px;background:${isLast ? 'var(--blue)' : '#c9d8fb'};transition:height .5s ease;" data-h="${h}"></div>
                  <div class="bar-label">${range.months[i]}</div>
                </div>`;
      }).join('');
      requestAnimationFrame(()=>{
        bars.querySelectorAll('.bar').forEach(b => b.style.height = b.dataset.h + 'px');
      });
      showToast(`Showing ${chip.textContent.trim()}`, 'info');
    });
  });

  /* ---------- Generate / Download reports ---------- */
  function handleGenerate(btn){
    const row = btn.closest('.report-row');
    const name = row.dataset.report;
    const original = btn.textContent;
    btn.disabled = true;
    btn.innerHTML = `<span class="spin-icon" style="display:inline-block;">⟳</span> Generating`;
    setTimeout(()=>{
      btn.innerHTML = '✓ Generated';
      const meta = row.querySelector('[data-meta]');
      meta.textContent = 'Last generated Just now';
      showToast(`${name} generated`, 'ok');
      setTimeout(()=>{
        btn.disabled = false;
        btn.textContent = original;
      }, 1400);
    }, 1200);
  }

  function handleDownload(btn){
    const row = btn.closest('.report-row');
    const name = row.dataset.report;
    const format = row.dataset.format.toUpperCase();
    const original = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Downloading…';
    setTimeout(()=>{
      btn.textContent = original;
      btn.disabled = false;
      showToast(`${name}.${row.dataset.format} downloaded`, 'info');
    }, 900);
  }

  /* ---------- Dashboard entrance animations ---------- */
  function animateDashboard(){
    // Animate stat cards with staggered delay
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, i) => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(15px)';
      card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      setTimeout(() => {
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
      }, 100 + (i * 80));
    });
    
    // Animate panels with staggered delay
    const panels = document.querySelectorAll('.dash-grid-3 .panel');
    panels.forEach((panel, i) => {
      panel.style.opacity = '0';
      panel.style.transform = 'translateY(20px)';
      panel.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      setTimeout(() => {
        panel.style.opacity = '1';
        panel.style.transform = 'translateY(0)';
      }, 200 + (i * 120));
    });
    
    // Animate chart bars (any horizontal fills still on the page)
    const chartBars = document.querySelectorAll('.chart-bar-fill');
    chartBars.forEach((bar, i) => {
      const width = bar.style.width;
      bar.style.width = '0';
      setTimeout(() => {
        bar.style.width = width;
      }, 400 + (i * 60));
    });

    // Animate the Spend-by-Category vertical bars growing up from the baseline.
    const vbars = document.querySelectorAll('#chart-spend-category .vbar-fill');
    vbars.forEach((bar, i) => {
      const height = bar.style.height;
      bar.style.height = '0';
      setTimeout(() => { bar.style.height = height; }, 300 + (i * 70));
    });
    
    // Animate supplier items
    const supplierItems = document.querySelectorAll('.supplier-item');
    supplierItems.forEach((item, i) => {
      item.style.opacity = '0';
      item.style.transform = 'translateX(-10px)';
      item.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
      setTimeout(() => {
        item.style.opacity = '1';
        item.style.transform = 'translateX(0)';
      }, 300 + (i * 50));
    });
    
    // Initialize donut if canvas exists
    const donutCanvas = document.getElementById('dash-donut');
    if (donutCanvas && window.dashboardData && window.dashboardData.poStatus) {
      initDonutFromData(donutCanvas, window.dashboardData.poStatus);
    }
  }
  
  /* ---------- Donut chart from data (with hover highlight + tooltip) ---------- */
  const DONUT_STATUS_COLORS = {
    pending: '#f2994a',
    processing: '#2f6fed',
    approved: '#1fa971',
    rejected: '#eb5757',
    cancelled: '#7c88a3',
    completed: '#14b8a6'
  };

  function initDonutFromData(canvas, statusData){
    if (!canvas || !statusData || Object.keys(statusData).length === 0) return;

    const ctx = canvas.getContext('2d');
    const centerX = canvas.width / 2;
    const centerY = canvas.height / 2;
    const radius = 78;
    const lineWidth = 26;
    const gap = 0.05; // radians of empty space between segments
    const total = Object.values(statusData).reduce((sum, val) => sum + val, 0);

    // Precompute each slice's angle range once so hover hit-testing and
    // redraws don't need to recompute them every mousemove.
    let startAngle = -Math.PI / 2;
    const slices = Object.entries(statusData).map(([status, count]) => {
      const sliceAngle = (count / total) * 2 * Math.PI;
      const slice = { status, count, pct: Math.round((count / total) * 100), startAngle, endAngle: startAngle + sliceAngle };
      startAngle = slice.endAngle;
      return slice;
    });

    function draw(hoveredStatus){
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      // A single 100% status should be a clean, unbroken ring (no rounded gap
      // notch at the top). With more than one status, shrink each slice on both
      // ends so segments read as separate rounded pills with visible gaps.
      const singleFull = slices.length === 1;
      slices.forEach(slice => {
        const isHovered = hoveredStatus && slice.status === hoveredStatus;
        const isDimmed = hoveredStatus && !isHovered;
        const span = slice.endAngle - slice.startAngle;
        const inset = singleFull ? 0 : Math.min(gap / 2, span / 2 - 0.001);
        ctx.lineCap = singleFull ? 'butt' : 'round';
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, slice.startAngle + inset, slice.endAngle - inset);
        ctx.strokeStyle = DONUT_STATUS_COLORS[slice.status] || '#ccc';
        ctx.globalAlpha = isDimmed ? 0.35 : 1;
        ctx.lineWidth = isHovered ? lineWidth + 6 : lineWidth;
        ctx.stroke();
      });
      ctx.globalAlpha = 1;
    }

    function findSliceAt(status){
      return slices.find(s => s.status === status);
    }

    const centerEl = document.getElementById('dash-donut-center');
    const centerVal = centerEl?.querySelector('.donut-center-val');
    const centerLabel = centerEl?.querySelector('.donut-center-label');
    const defaultVal = centerVal ? centerVal.textContent : '';
    const defaultLabel = centerLabel ? centerLabel.textContent : '';
    const tooltip = document.getElementById('dash-donut-tooltip');
    const container = canvas.closest('.donut-chart-container');
    const legendItems = document.querySelectorAll('#dash-donut-legend .donut-legend-item');

    function highlight(status){
      draw(status);
      legendItems.forEach(item => item.classList.toggle('active', status && item.dataset.status === status));
      if(status){
        const slice = findSliceAt(status);
        if(centerVal) centerVal.textContent = slice ? slice.pct + '%' : defaultVal;
        if(centerLabel) centerLabel.textContent = slice ? slice.status.charAt(0).toUpperCase() + slice.status.slice(1) : defaultLabel;
      } else {
        if(centerVal) centerVal.textContent = defaultVal;
        if(centerLabel) centerLabel.textContent = defaultLabel;
        if(tooltip) tooltip.classList.remove('show');
      }
    }

    function statusAtPoint(x, y){
      const dx = x - centerX;
      const dy = y - centerY;
      const dist = Math.sqrt(dx * dx + dy * dy);
      if(dist < radius - lineWidth / 2 - 4 || dist > radius + lineWidth / 2 + 4) return null;
      let angle = Math.atan2(dy, dx);
      if(angle < -Math.PI / 2) angle += 2 * Math.PI;
      const found = slices.find(s => angle >= s.startAngle && angle <= s.endAngle);
      return found ? found.status : null;
    }

    canvas.addEventListener('mousemove', (e) => {
      const rect = canvas.getBoundingClientRect();
      const x = (e.clientX - rect.left) * (canvas.width / rect.width);
      const y = (e.clientY - rect.top) * (canvas.height / rect.height);
      const status = statusAtPoint(x, y);
      if(status){
        const slice = findSliceAt(status);
        highlight(status);
        if(tooltip){
          tooltip.textContent = `${status.charAt(0).toUpperCase() + status.slice(1)}: ${slice.count} (${slice.pct}%)`;
          tooltip.style.left = e.clientX - (container?.getBoundingClientRect().left || 0) + 'px';
          tooltip.style.top = e.clientY - (container?.getBoundingClientRect().top || 0) + 'px';
          tooltip.classList.add('show');
        }
      } else {
        highlight(null);
      }
    });
    canvas.addEventListener('mouseleave', () => highlight(null));

    legendItems.forEach(item => {
      item.addEventListener('mouseenter', () => highlight(item.dataset.status));
      item.addEventListener('mouseleave', () => highlight(null));
    });

    draw(null);
  }

  /* ---------- Spend by Category "View all" modal (ranked list + search) ---------- */
  function renderSpendByCategoryModalList(query){
    const list = document.getElementById('spend-by-category-modal-list');
    if(!list) return;
    const all = (window.dashboardData && window.dashboardData.spendByCategoryAll) || [];
    const q = (query || '').trim().toLowerCase();
    const data = q ? all.filter(d => String(d.category || '').toLowerCase().includes(q)) : all;
    if(!data.length){
      list.innerHTML = `<div style="padding:20px;text-align:center;color:var(--muted);">${all.length ? 'No categories match your search.' : 'No spend data available.'}</div>`;
      return;
    }
    const max = Math.max(...all.map(d => Number(d.total) || 0)) || 1;
    list.innerHTML = data.map((item, i) => `
      <div class="chart-bar-item-h">
        <div class="chart-bar-item-h-top">
          <span class="chart-bar-label">${i + 1}. ${htmlEscape(item.category)}</span>
          <span class="chart-bar-value">${htmlEscape(item.formatted)}</span>
        </div>
        <div class="chart-bar-track">
          <div class="chart-bar-fill" style="width:0"></div>
        </div>
      </div>
    `).join('');
    // Animate the bars in after render.
    requestAnimationFrame(() => {
      [...list.querySelectorAll('.chart-bar-fill')].forEach((bar, i) => {
        bar.style.width = ((Number(data[i].total) / max) * 100) + '%';
      });
    });
  }
  function filterSpendByCategoryModal(query){
    renderSpendByCategoryModalList(query);
  }
  function openSpendByCategoryModal(){
    const modal = document.getElementById('spend-by-category-modal');
    if(!modal) return;
    const search = document.getElementById('spend-by-category-search');
    if(search) search.value = '';
    renderSpendByCategoryModalList('');
    modal.classList.add('open');
    setTimeout(() => search?.focus(), 80);
  }
  function closeSpendByCategoryModal(){
    document.getElementById('spend-by-category-modal')?.classList.remove('open');
  }

