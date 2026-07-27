/* ---------- Delivery tracking modal ---------- */
  function openTrackModal(btn){
    const row = btn.closest('tr');
    const d = row.dataset;
    const stage = parseInt(d.stage || 0, 10);
    const ship = d.ship || textFrom(row.children[0]);
    const supplier = d.sup || supplierNameFromCell(row.children[2]);
    const dateLabel = d.date || row.getAttribute('data-date') || textFrom(row.children[6]);
    let currentStatus = (d.status || '').toLowerCase();
    const expectedDate = d.expected || '';
    if (!['delivered','completed'].includes(currentStatus) && expectedDate && new Date(expectedDate) < new Date(todayISO())) {
      currentStatus = 'delayed';
      row.dataset.status = 'delayed';
      row.dataset.stage = '1';
      row.children[5].innerHTML = statusPill('Delayed');
    }
    // All purchased items are listed here in the details, even though the
    // deliveries table only shows the first one.
    const itemsList = String(d.items || '').split(',').map(s => s.trim()).filter(Boolean);
    const itemsMarkup = itemsList.length
      ? `<div class="supplier-product-inline">${itemsList.map(n => `<span class="supplier-product-tag">${htmlEscape(n)}</span>`).join('')}</div>`
      : '<div class="modal-helper">No items recorded.</div>';
    document.getElementById('track-title').textContent = `${ship} · ${supplier}`;
    document.getElementById('track-body').innerHTML = `
      <div class="detail-grid">
        <div class="detail-card"><h4>Shipment summary</h4><div class="modal-row"><span>Shipment no.</span><span>${ship}</span></div><div class="modal-row"><span>PO number</span><span>${d.po || textFrom(row.children[1])}</span></div><div class="modal-row"><span>Supplier</span><span>${supplier}</span></div><div class="modal-row"><span>Status</span><span>${textFrom(row.children[5])}</span></div></div>
        <div class="detail-card"><h4>Tracking info</h4><div class="modal-row"><span>Date</span><span>${dateLabel}</span></div><div class="modal-row"><span>Deliver to</span><span>${d.warehouse || '—'}</span></div><div class="modal-row"><span>Carrier</span><span>${d.carrier || 'Assigned carrier'}</span></div><div class="modal-row"><span>Current stage</span><span>${Math.min(stage + 1, 5)} / 5</span></div><div class="modal-row"><span>Delivery note</span><span>${d.note || 'No additional note'}</span></div></div>
        <div class="detail-card full"><h4>Items</h4>${itemsMarkup}</div>
      </div>
    `;
    // Delivered status is driven by the existing (Inventory-received) flow, not
    // a manual button here. Only a Delivered shipment can be marked Completed.
    const markCompletedBtn = document.getElementById('mark-completed-btn');
    if (markCompletedBtn) {
      markCompletedBtn.style.display = currentStatus === 'delivered' ? 'block' : 'none';
    }
    document.getElementById('track-modal').__row = row;
    document.getElementById('track-modal').classList.add('open');
  }
  function closeTrackModal(){
    document.getElementById('track-modal').classList.remove('open');
  }
  // Persist a shipment status change and reflect it on the PO / requisition
  // rows. The server (DeliveryController@update) cascades the delivery status
  // to the parent PO, so we only PUT the delivery; the PO/req rows are updated
  // client-side for instant feedback and re-derive correctly on reload.
  function persistShipmentStatus(row, deliveryStatus, poLabel, reqLabel){
    const delId = row.dataset.id;
    if(delId){
      fetch(procurementUrl(`deliveries/${delId}`), { method: 'PUT', headers: { 'Content-Type':'application/x-www-form-urlencoded', 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }, body: new URLSearchParams({ status: deliveryStatus, remarks: row.dataset.note || '' }).toString() }).then(()=>{}).catch(()=>showToast('Unable to persist delivery status to server.', 'no'));
    }
    const poRow = findPoRowByNumber(row.dataset.po || '');
    if(poRow){
      poRow.dataset.status = poLabel.toLowerCase();
      poRow.children[5].innerHTML = statusPill(poLabel);
    }
    const reqRow = findReqRowByRef(row.dataset.po || '');
    if(reqRow){
      updateRequisitionStatus(row.dataset.po || '', reqLabel);
    }
  }

  function markCompleted(){
    const row = document.getElementById('track-modal').__row;
    if(row){
      row.dataset.status = 'completed';
      row.dataset.stage = '4';
      row.children[5].innerHTML = statusPill('Completed');
      persistShipmentStatus(row, 'completed', 'Completed', 'Completed');
      findDefectRowsByPO(row.dataset.po || '').forEach(r => updateDefectStatus(r, 'Completed'));
      showToast(`${row.dataset.ship} marked as completed`, 'ok');
    }
    closeTrackModal();
  }