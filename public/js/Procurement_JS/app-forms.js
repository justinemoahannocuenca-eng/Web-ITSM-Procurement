  /* ---------- Add Modals (PO / Supplier / Req / Delivery / Invoice) ---------- */
  const ADD_MODAL_MAP = {
    po: 'add-po-modal',
    supplier: 'add-supplier-modal',
    req: 'add-req-modal',
    delivery: 'add-delivery-modal',
    invoice: 'add-invoice-modal'
  };

  // simple auto-increment counters (initial values based on existing sample data)
  const NEXT_ID = { po: 420, req: 45, dr: 232, inv: 3, sup: 20 };
  const ID_COUNTS = { po: 419, req: 44, dr: 231 }; // Track highest used number per year
  const procurementUrl = window.procurementUrl || ((path = '') => `/procurement/${String(path).replace(/^\/+/, '')}`);

  function pad(n, len){ return String(n).padStart(len, '0'); }
  function todayISO(){ return new Date().toISOString().slice(0,10); }
  function fmtDate(iso){
    if(!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
  }
  function initials(name){
    return name.split(/\s+/).filter(Boolean).slice(0,2).map(w=>w[0].toUpperCase()).join('');
  }
  function randomColor(seed){
    const colors = ['#2f6fed','#22c55e','#f2994a','#7a5af0','#eb5757','#0ea5e9','#1fa971','#e0338c'];
    let h = 0; for(const c of seed) h = (h*31 + c.charCodeAt(0)) & 0xffff;
    return colors[h % colors.length];
  }

  function priorityBadge(label = 'Normal'){
    const raw = String(label || 'Normal').trim();
    const normalized = raw.toUpperCase();
    const type = ['URGENT','HIGH','LOW'].includes(normalized) ? normalized.toLowerCase() : 'normal';
    return `<span class="priority-pill ${type}">${htmlEscape(normalized)}</span>`;
  }

  function refreshDeliverySupplierOptions(){
    const select = document.getElementById('delivery-filter-supplier');
    if(!select) return;
    const selected = select.value || '';
    const supplierNames = [...document.querySelectorAll('#suppliers-table tbody tr')]
      .map(row => supplierNameFromCell(row.children[0]) || textFrom(row.children[0]))
      .filter(Boolean);
    const uniqueNames = [...new Set(supplierNames)];
    select.innerHTML = '<option value="">All Suppliers</option>' + uniqueNames.map(name => `<option value="${htmlEscape(name)}">${htmlEscape(name)}</option>`).join('');
    select.value = uniqueNames.includes(selected) ? selected : '';
  }

  function setModalFieldValue(modal, name, value){
    const field = modal.querySelector(`[name="${name}"]`);
    if(field) field.value = value;
  }

  function refreshPoSupplierOptions(modal){
    const form = modal?.querySelector('#add-po-form');
    if(!form) return Promise.resolve();
    const supplierField = form.querySelector('[name="supplier"]');
    if(!supplierField) return;
    const selectedSupplier = supplierField.value || '';
    const supplierRows = [...document.querySelectorAll('#suppliers-table tbody tr')]
      .map(row => {
        const supplierName = supplierNameFromCell(row.children[0]) || textFrom(row.children[0]);
        if(!supplierName) return null;
        return { name: supplierName, brand: row.dataset.category || row.dataset.brand || '', warehouseId: row.dataset.warehouseId || '', products: getSupplierProducts(row) };
      })
      .filter(Boolean);
    if(supplierRows.length > 0){
      supplierField.innerHTML = '<option value="">Select supplier...</option>' + supplierRows.map(s => `<option value="${htmlEscape(s.name)}">${htmlEscape(s.name)}</option>`).join('');
      supplierField.value = supplierRows.some(s => s.name === selectedSupplier) ? selectedSupplier : '';
      // populate client-side catalog from rows (with each product's category,
      // used by the PO modal's supplier -> category -> item cascade)
      window.SUPPLIER_CATALOG = window.SUPPLIER_CATALOG || {};
      supplierRows.forEach(s => {
        window.SUPPLIER_CATALOG[s.name] = {
          brand: s.brand || s.name,
          warehouseId: s.warehouseId || '',
          products: s.products.map(p => ({ name: p.name, unitPrice: Number(p.price || p.unitPrice || 0), category: p.category || '' }))
        };
      });
      refreshAllPoItemRowsForSupplier(modal);
      refreshDeliverySupplierOptions();
      return Promise.resolve();
    }
    // No supplier rows on this page — fetch suppliers JSON from server
    return fetch(procurementUrl('suppliers'), { headers: { 'Accept': 'application/json' } })
      .then(res => res.json())
      .then(json => {
        const list = (json?.data || []);
        supplierField.innerHTML = '<option value="">Select supplier...</option>' + list.map(s => `<option value="${htmlEscape(s.name)}">${htmlEscape(s.name)}</option>`).join('');
        supplierField.value = list.some(s => s.name === selectedSupplier) ? selectedSupplier : '';
        window.SUPPLIER_CATALOG = window.SUPPLIER_CATALOG || {};
        list.forEach(s => {
          window.SUPPLIER_CATALOG[s.name] = { brand: s.brand || s.name, warehouseId: s.warehouse_id || '', products: (s.products || []).map(p => ({ name: p.name, unitPrice: Number(p.price || p.unitPrice || 0), category: p.category || '' })) };
        });
        refreshAllPoItemRowsForSupplier(modal);
        refreshDeliverySupplierOptions();
      }).catch(() => {
        // ignore errors; leave supplierField as-is
      });
  }

  function addSupplierOptionToPoForm(name){
    if(!name) return;
    const modal = document.getElementById('add-po-modal');
    if(!modal) return;
    const supplierField = modal.querySelector('[name="supplier"]');
    if(!supplierField) return;
    const exists = [...supplierField.options].some(opt => opt.value === name);
    if(exists) return;
    const option = document.createElement('option');
    option.value = name;
    option.textContent = name;
    supplierField.appendChild(option);
  }

  /* ---------- PO multi-item rows: Category -> Item cascading ---------- */
  // Categories come from the currently-selected supplier's products. A
  // product with no category is bucketed as "Uncategorized" so older catalog
  // entries (saved before the category field existed) stay selectable.
  function supplierCategoriesFor(entry){
    const cats = (entry?.products || []).map(p => String(p.category || '').trim() || 'Uncategorized');
    return [...new Set(cats)];
  }
  function itemsForCategory(entry, category){
    const target = String(category || '').trim().toLowerCase();
    return (entry?.products || []).filter(p => (String(p.category || '').trim() || 'Uncategorized').toLowerCase() === target);
  }
  function currentPoSupplierEntry(modal){
    const supplierField = modal?.querySelector('#add-po-form [name="supplier"]');
    return getSupplierCatalogEntry((supplierField?.value || '').trim());
  }

  function populatePoRowCategorySelect(row, entry){
    const catField = row.querySelector('.po-item-category');
    if(!catField) return;
    const categories = supplierCategoriesFor(entry);
    catField.innerHTML = '<option value="">Select category...</option>' + categories.map(c => `<option value="${htmlEscape(c)}">${htmlEscape(c)}</option>`).join('');
    catField.value = '';
    populatePoRowItemSelect(row, entry, '');
  }
  function populatePoRowItemSelect(row, entry, category){
    const itemField = row.querySelector('.po-item-name');
    const priceField = row.querySelector('.po-item-price');
    if(!itemField) return;
    const items = category ? itemsForCategory(entry, category) : [];
    itemField.innerHTML = '<option value="">Select item...</option>' + items.map(p => {
      const name = String(p.name || '').trim();
      const price = Number(p.unitPrice || p.price || 0);
      return `<option value="${htmlEscape(name)}" data-unit-price="${price}">${htmlEscape(name)}</option>`;
    }).join('');
    itemField.disabled = !category;
    itemField.value = '';
    if(priceField) priceField.value = '';
    recomputePoRowAmount(row);
  }

  function recomputePoRowAmount(row){
    const qty = Number(row.querySelector('.po-item-qty')?.value || 0);
    const price = Number(row.querySelector('.po-item-price')?.value || 0);
    const amountField = row.querySelector('.po-item-amount');
    if(amountField) amountField.value = money(qty * price);
  }

  function recomputePoTotals(modal){
    const form = modal?.querySelector('#add-po-form');
    if(!form) return;
    let totalAmount = 0;
    form.querySelectorAll('.po-item-row').forEach(row => {
      const qty = Number(row.querySelector('.po-item-qty')?.value || 0);
      const price = Number(row.querySelector('.po-item-price')?.value || 0);
      totalAmount += qty * price;
    });
    const amountField = form.querySelector('[name="amount"]');
    if(amountField) amountField.value = totalAmount.toFixed(2);
  }

  function bindPoItemRow(modal, row){
    const catField = row.querySelector('.po-item-category');
    const itemField = row.querySelector('.po-item-name');
    const qtyField = row.querySelector('.po-item-qty');
    catField?.addEventListener('change', () => {
      populatePoRowItemSelect(row, currentPoSupplierEntry(modal), catField.value);
      recomputePoTotals(modal);
    });
    itemField?.addEventListener('change', () => {
      const priceField = row.querySelector('.po-item-price');
      const selected = itemField.selectedOptions?.[0];
      if(priceField) priceField.value = selected?.dataset?.unitPrice || '';
      recomputePoRowAmount(row);
      recomputePoTotals(modal);
    });
    qtyField?.addEventListener('input', () => { recomputePoRowAmount(row); recomputePoTotals(modal); });
    qtyField?.addEventListener('change', () => { recomputePoRowAmount(row); recomputePoTotals(modal); });
  }

  function addPoItemRow(modal){
    const container = modal?.querySelector('#po-items-rows');
    const template = document.getElementById('po-item-row-template');
    if(!container || !template) return null;
    const row = template.content.firstElementChild.cloneNode(true);
    container.appendChild(row);
    bindPoItemRow(modal, row);
    populatePoRowCategorySelect(row, currentPoSupplierEntry(modal));
    updatePoItemRemoveButtons(modal);
    recomputePoTotals(modal);
    return row;
  }
  function removePoItemRow(btn){
    const modal = btn.closest('.modal-overlay');
    const container = modal?.querySelector('#po-items-rows');
    if(!container || container.children.length <= 1) return; // keep at least one row
    btn.closest('.po-item-row')?.remove();
    updatePoItemRemoveButtons(modal);
    recomputePoTotals(modal);
  }
  function updatePoItemRemoveButtons(modal){
    const rows = [...(modal?.querySelectorAll('.po-item-row') || [])];
    rows.forEach(r => {
      const btn = r.querySelector('.po-item-remove');
      if(btn) btn.style.visibility = rows.length > 1 ? 'visible' : 'hidden';
    });
  }
  // Reset the item rows back to exactly one empty row (called when the PO
  // modal opens/closes so a previous session's extra rows don't linger).
  function resetPoItemRows(modal){
    const container = modal?.querySelector('#po-items-rows');
    if(!container) return;
    container.innerHTML = '';
    addPoItemRow(modal);
  }
  // Supplier changed: every row shares the same supplier, so repopulate every
  // row's category (and clear its item/price, since the old item may not
  // belong to the new supplier) rather than just the row being edited.
  function refreshAllPoItemRowsForSupplier(modal){
    const entry = currentPoSupplierEntry(modal);
    modal?.querySelectorAll('.po-item-row').forEach(row => populatePoRowCategorySelect(row, entry));
    recomputePoTotals(modal);
  }
  // Reads the item rows back out for submission: {category, name, qty, unitPrice, amount}.
  function collectPoItemRows(modal){
    return [...(modal?.querySelectorAll('.po-item-row') || [])].map(row => {
      const category = row.querySelector('.po-item-category')?.value || '';
      const name = row.querySelector('.po-item-name')?.value || '';
      const qty = Number(row.querySelector('.po-item-qty')?.value || 0);
      const unitPrice = Number(row.querySelector('.po-item-price')?.value || 0);
      return { category, name, qty, unitPrice, amount: qty * unitPrice };
    }).filter(r => r.name && r.qty > 0);
  }

  function bindPoFormAutofill(modal){
    const form = modal?.querySelector('#add-po-form');
    if(!form || form.__poAutofillBound) return;
    form.__poAutofillBound = true;
    const supplierField = form.querySelector('[name="supplier"]');
    // Selecting a supplier only loads that supplier's categories/items into
    // every item row — it must never touch a row's unit price on its own.
    supplierField?.addEventListener('change', () => refreshAllPoItemRowsForSupplier(modal));
  }

  function refreshDeliveryPoOptions(){
    const modal = document.getElementById('add-delivery-modal');
    const poField = modal?.querySelector('[name="po"]');
    if(!poField) return;

    const currentValue = poField.value || '';
    const approvedRows = [...document.querySelectorAll('#po-table tbody tr')].filter(row => {
      const status = String(row.dataset.status || textFrom(row.children[5]) || '').toLowerCase().trim();
      return status === 'approved';
    });
    const noApprovedText = 'No approved purchase orders available';

    if(approvedRows.length){
      poField.innerHTML = '<option value="">Select PO...</option>' + approvedRows.map(row => {
        const poNumber = htmlEscape(textFrom(row.children[0]));
        return `<option value="${poNumber}"${poNumber === currentValue ? ' selected' : ''}>${poNumber}</option>`;
      }).join('');
      window.APPROVED_PO_CACHE = approvedRows.reduce((acc,row) => {
        const poNumber = textFrom(row.children[0]);
        acc[poNumber] = {
          po: poNumber,
          supplier: supplierNameFromCell(row.children[1]),
          item: row.dataset.item || textFrom(row.children[2]) || '',
          items: getPoItems(row),
          qty: row.dataset.qty || '',
          unitPrice: row.dataset.unitPrice || '',
          amount: row.dataset.amount || '',
          status: String(row.dataset.status || textFrom(row.children[5]) || '').toLowerCase().trim(),
          expected: row.dataset.expected || ''
        };
        return acc;
      }, {});
      return;
    }

    poField.innerHTML = `<option value="">${noApprovedText}</option>`;
    fetch(procurementUrl('purchase-orders/approved'), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(res => res.ok ? res.json() : Promise.reject()).then(data => {
      if(!Array.isArray(data)) throw new Error('Invalid response');
      if(!data.length){
        poField.innerHTML = `<option value="">${noApprovedText}</option>`;
        window.APPROVED_PO_CACHE = {};
        return;
      }
      window.APPROVED_PO_CACHE = data.reduce((acc, item) => {
        if(item.po_number){
          acc[item.po_number] = {
            po: item.po_number,
            supplier: item.supplier_name || '—',
            item: item.item || '',
            items: Array.isArray(item.items) ? item.items : [],
            qty: item.qty || '',
            unitPrice: item.unit_price || '',
            amount: item.amount || '',
            status: String(item.status || 'approved').toLowerCase().trim(),
            expected: item.expected_delivery_date || ''
          };
        }
        return acc;
      }, {});
      poField.innerHTML = '<option value="">Select PO...</option>' + data.map(po => {
        const poNumber = htmlEscape(po.po_number || '');
        return `<option value="${poNumber}"${poNumber === currentValue ? ' selected' : ''}>${poNumber}</option>`;
      }).join('');
    }).catch(() => {
      poField.innerHTML = `<option value="">${noApprovedText}</option>`;
    });
  }

  function getPoInfo(poNumber){
    const trimmed = String(poNumber || '').trim();
    if(!trimmed) return null;
    const domRow = findPoRowByNumber(trimmed);
    if(domRow){
      return {
        po: trimmed,
        supplier: resolveSupplierByPO(trimmed) || '',
        item: domRow.dataset.item || textFrom(domRow.children[2]) || '',
        items: getPoItems(domRow),
        qty: domRow.dataset.qty || '',
        unitPrice: domRow.dataset.unitPrice || '',
        amount: domRow.dataset.amount || '',
        status: String(domRow.dataset.status || textFrom(domRow.children[5]) || '').toLowerCase().trim(),
        expected: domRow.dataset.expected || ''
      };
    }
    return window.APPROVED_PO_CACHE?.[trimmed] || null;
  }

  /* ---------- Delivery: items-purchased chips (replaces the manual Item field) ---------- */
  function renderDeliveryItemChips(poInfo){
    const wrap = document.getElementById('delivery-items-chips');
    const hidden = document.getElementById('delivery-items-value');
    if(!wrap) return;
    const items = Array.isArray(poInfo?.items) && poInfo.items.length
      ? poInfo.items
      : (poInfo?.item ? [{ name: poInfo.item, qty: poInfo.qty, unitPrice: poInfo.unitPrice }] : []);
    if(!items.length){
      wrap.innerHTML = '<div class="product-list-empty">No items found for this purchase order.</div>';
      if(hidden) hidden.value = '';
      return;
    }
    wrap.innerHTML = items.map(it => `<span class="product-chip"><span>${htmlEscape(it.name || 'Item')}</span><span class="meta">Qty ${Number(it.qty || 0)}${it.unitPrice ? ' · ₱' + Number(it.unitPrice).toFixed(2) : ''}</span></span>`).join('');
    if(hidden) hidden.value = items.map(it => it.name).filter(Boolean).join(', ');
  }
  function resetDeliveryItemChips(){
    const wrap = document.getElementById('delivery-items-chips');
    const hidden = document.getElementById('delivery-items-value');
    if(wrap) wrap.innerHTML = '<div class="product-list-empty">Select a PO to load its items.</div>';
    if(hidden) hidden.value = '';
  }

  function bindDeliveryPoAutofill(modal){
    const form = modal?.querySelector('#add-delivery-form');
    if(!form || form.__deliveryPoBound) return;
    form.__deliveryPoBound = true;
    const poField = form.querySelector('[name="po"]');
    const supplierField = form.querySelector('[name="supplier"]');
    const amountField = form.querySelector('[name="amount"]');
    const update = () => {
      const poNumber = (poField?.value || '').trim();
      if(!poNumber){ resetDeliveryItemChips(); return; }
      const poInfo = getPoInfo(poNumber);
      if(!poInfo) return;
      if(supplierField){
        supplierField.value = poInfo.supplier || '';
      }
      renderDeliveryItemChips(poInfo);
      // Total Amount mirrors the total amount that was ordered on the PO.
      if(amountField){
        const poAmount = Number(poInfo.amount || 0);
        if(poAmount) amountField.value = poAmount.toFixed(2);
      }
    };
    poField?.addEventListener('change', update);
    poField?.addEventListener('input', update);
  }

  function openAddModal(kind, reqData = null){
    const modal = document.getElementById(ADD_MODAL_MAP[kind]);
    if(!modal) return;
    
    // Handle PO modal title for requisition conversion
    const poTitle = modal.querySelector('#add-po-modal h3');
    if(poTitle && kind === 'po'){
      if(reqData && reqData.reqNum){
        poTitle.textContent = `Create New Purchase - REQ ${reqData.reqNum}`;
      } else {
        poTitle.textContent = 'Create New Purchase Order';
      }
    }

    if(kind==='supplier'){
      setSupplierProductEditor('supplier-products-list', 'supplier-products-json');
      resetSupplierProductDraft();
    }

    // Pre-fill auto-generated IDs & default dates
    const yr = new Date().getFullYear();
    if(kind==='po'){
      bindPoFormAutofill(modal);
      resetPoItemRows(modal);
      // Prefer the server-provided real next sequence (window.nextPoSeq) so the
      // number doesn't collide with existing POs; fall back to the local counter.
      const poNum = Number(window.nextPoSeq) > 0 ? Number(window.nextPoSeq) : (ID_COUNTS.po + 1);
      setModalFieldValue(modal, 'po', `PO-${yr}-${pad(poNum,4)}`);
      const exp = new Date(); exp.setDate(exp.getDate()+7);
      setModalFieldValue(modal, 'expected', exp.toISOString().slice(0,10));
      refreshPoSupplierOptions(modal);

      // Auto-fill from requisition data — Qty, the reference number, and the
      // requested Priority carry over. Item/Category/Supplier are chosen by
      // the person creating the PO, not copied from the request.
      // Always set reqRef/priority explicitly (both directions) so a PO created
      // straight from this page doesn't inherit a leftover requisition
      // reference/priority from a previous conversion — form.reset() can't
      // clear these because their defaultValue gets mutated once assigned.
      if(reqData){
        setModalFieldValue(modal, 'reqRef', reqData.reqNum || '');
        // A requisition may carry several item lines. Generate one PO item row
        // per requested line and auto-fill ONLY the quantity — the category and
        // item selections are left for the user to pick. Single-item
        // requisitions fall back to filling the first row's quantity.
        const reqItems = Array.isArray(reqData.items) && reqData.items.length
          ? reqData.items
          : (reqData.qty ? [{ qty: reqData.qty }] : []);
        if(reqItems.length){
          resetPoItemRows(modal);
          reqItems.forEach((it, i) => {
            const row = i === 0
              ? modal.querySelector('#po-items-rows .po-item-row')
              : addPoItemRow(modal);
            const qtyField = row?.querySelector('.po-item-qty');
            if(qtyField) qtyField.value = it.qty ?? '';
          });
        }
        setModalFieldValue(modal, 'priority', normalizePriorityLabel(reqData.priority));
      } else {
        setModalFieldValue(modal, 'reqRef', '');
        setModalFieldValue(modal, 'priority', 'Normal');
      }
      setTimeout(() => {
        // wait for supplier options/catalog to be ready before recomputing totals
        refreshPoSupplierOptions(modal).then(() => {
          recomputePoTotals(modal);
        });
      }, 60);
    } else if(kind==='req'){
      const reqNum = ID_COUNTS.req + 1;
      setModalFieldValue(modal, 'rq', `REQ-${yr}-${pad(reqNum,4)}`);
      setModalFieldValue(modal, 'dateReq', todayISO());
    } else if(kind==='delivery'){
      const shpNum = Number(window.nextShipmentSeq) > 0 ? Number(window.nextShipmentSeq) : (ID_COUNTS.dr + 1);
      setModalFieldValue(modal, 'dr', `SHP-${yr}-${pad(shpNum,4)}`);
      setModalFieldValue(modal, 'delDate', todayISO());
      resetDeliveryItemChips();
      refreshDeliveryPoOptions();
      bindDeliveryPoAutofill(modal);
    } else if(kind==='invoice'){
      setModalFieldValue(modal, 'inv', `INV-${yr}-${pad(NEXT_ID.inv,3)}`);
      setModalFieldValue(modal, 'invDate', todayISO());
      const due = new Date(); due.setDate(due.getDate()+30);
      setModalFieldValue(modal, 'dueDate', due.toISOString().slice(0,10));
    } else if(kind==='supplier'){
      setModalFieldValue(modal, 'sid', `SUP-${pad(NEXT_ID.sup,4)}`);
    }

    modal.classList.add('open');
    // focus first editable input
    setTimeout(()=>{
      const focusable = modal.querySelector('input:not([readonly]), select, textarea');
      if(focusable) focusable.focus();
    }, 60);
  }

  function closeAddModal(kind){
    const modal = document.getElementById(ADD_MODAL_MAP[kind]);
    if(modal){
      modal.classList.remove('open');
      const form = modal.querySelector('form');
      if(form) form.reset();
      
      // Reset PO modal title when closing
      if(kind === 'po'){
        const poTitle = modal.querySelector('h3');
        if(poTitle) poTitle.textContent = 'Create New Purchase Order';
        resetPoItemRows(modal);
      }
    }
    if(kind==='supplier'){
      resetSupplierProductDraft();
    }
    if(kind==='delivery'){
      resetDeliveryItemChips();
    }
  }

  // Normalize any priority string (e.g. "URGENT", "high") to the Title-case
  // label stored on the PO (Urgent / High / Normal / Low).
  function normalizePriorityLabel(value){
    const key = String(value || '').trim().toLowerCase();
    const map = { urgent: 'Urgent', high: 'High', normal: 'Normal', low: 'Low' };
    return map[key] || 'Normal';
  }

  // Convert Requisition to PO — carry the requisition's priority through so the
  // new PO keeps the requested priority instead of defaulting to Normal.
  function convertReqToPO(reqNum, item, qty, priority, items){
    const reqData = { reqNum, item, qty, priority, items: Array.isArray(items) ? items : null };
    openAddModal('po', reqData);
  }

  let cancelPOData = null;
  function createPOFromView(){
    const modal = document.getElementById('view-modal');
    const row = modal.__row;
    if(!row) return;
    const record = buildRecord(row);
    if(record.type === 'req'){
      convertReqToPO(record.ref, record.item, record.qty, record.priority);
      closeViewModal();
    }
  }
  let supplierProductDraft = [];
  let supplierProductCounter = 1;
  let supplierProductEditor = { listId: 'supplier-products-list', hiddenId: 'supplier-products-json' };

  function setSupplierProductEditor(listId, hiddenId){
    supplierProductEditor = { listId, hiddenId };
  }

  function resetSupplierProductDraft(){
    supplierProductDraft = [];
    supplierProductCounter = 1;
    renderSupplierProductList();
  }

  function renderSupplierProductList(){
    const list = document.getElementById(supplierProductEditor.listId);
    const hidden = document.getElementById(supplierProductEditor.hiddenId);
    if(list){
      if(!supplierProductDraft.length){
        list.innerHTML = '<div class="product-list-empty">No products added yet.</div>';
      } else {
        // Editable rows: name, category, and price are inline inputs so
        // existing products can be edited, not just added or removed.
        list.innerHTML = supplierProductDraft.map((item, idx) => `
          <div class="product-chip product-chip-edit">
            <input type="text" class="product-chip-name" value="${htmlEscape(item.name || '')}" placeholder="Product name" oninput="updateSupplierProduct(${idx}, 'name', this.value)">
            <input type="text" class="product-chip-category-input" value="${htmlEscape(item.category || '')}" placeholder="Category" oninput="updateSupplierProduct(${idx}, 'category', this.value)">
            <span class="product-chip-sku">${htmlEscape(item.sku || 'SKU pending')}</span>
            <span class="product-chip-price">₱<input type="number" min="0" step="0.01" class="product-chip-price-input" value="${Number(item.price || 0)}" placeholder="0.00" oninput="updateSupplierProduct(${idx}, 'price', this.value)"></span>
            <button type="button" class="remove" onclick="removeSupplierProduct(${idx})" title="Remove">×</button>
          </div>
        `).join('');
      }
    }
    if(hidden){ hidden.value = JSON.stringify(supplierProductDraft); }
  }

  function removeSupplierProduct(index){
    supplierProductDraft.splice(index, 1);
    renderSupplierProductList();
  }

  function updateSupplierProduct(index, field, value){
    if(!supplierProductDraft[index]) return;
    if(field === 'price'){
      supplierProductDraft[index].price = Number(value || 0);
    } else {
      supplierProductDraft[index][field] = value;
    }
    // Update only the hidden JSON payload — don't re-render, or the input the
    // user is typing into would lose focus on every keystroke.
    const hidden = document.getElementById(supplierProductEditor.hiddenId);
    if(hidden){ hidden.value = JSON.stringify(supplierProductDraft); }
  }

  function openSupplierProductModal(){
    const modal = document.getElementById('add-supplier-product-modal');
    const form = document.getElementById('add-supplier-product-form');
    if(!modal || !form) return;

    const skuInput = form.querySelector('[name="productSku"]');
    const skuType = form.querySelector('[name="productSkuType"]');
    if(skuType) skuType.value = 'auto';
    form.reset();
    if(skuInput){
      skuInput.readOnly = true;
      skuInput.value = generateSupplierProductSku('');
    }
    modal.classList.add('open');
    const nameField = form.querySelector('[name="productName"]');
    if(nameField){ nameField.focus(); }
  }

  function closeSupplierProductModal(){
    const modal = document.getElementById('add-supplier-product-modal');
    if(modal){
      modal.classList.remove('open');
      const form = document.getElementById('add-supplier-product-form');
      if(form) form.reset();
    }
  }

  function updateSupplierProductSkuType(select){
    const form = document.getElementById('add-supplier-product-form');
    const skuInput = form?.querySelector('[name="productSku"]');
    if(!skuInput) return;
    if(select.value === 'manual'){
      skuInput.readOnly = false;
      skuInput.focus();
    } else {
      skuInput.readOnly = true;
      const nameField = form?.querySelector('[name="productName"]');
      skuInput.value = nameField ? generateSupplierProductSku(nameField.value) : `SKU-${String(supplierProductCounter).padStart(3, '0')}`;
    }
  }

  function syncSupplierProductSku(nameInput){
    const form = document.getElementById('add-supplier-product-form');
    const skuType = form?.querySelector('[name="productSkuType"]');
    const skuInput = form?.querySelector('[name="productSku"]');
    if(!skuType || !skuInput) return;
    if(skuType.value !== 'manual'){
      skuInput.value = generateSupplierProductSku(nameInput.value);
    }
  }

  function generateSupplierProductSku(name){
    const trimmed = (name || '').trim();
    const base = trimmed ? trimmed.toUpperCase().replace(/[^A-Z0-9]+/g, '').slice(0, 8) : 'PRD';
    const suffix = String(supplierProductCounter).padStart(3, '0');
    return `${base}${suffix}`;
  }

  function submitSupplierProduct(e){
    e.preventDefault();
    const form = e.target;
    const d = Object.fromEntries(new FormData(form).entries());
    const name = (d.productName || '').trim();
    const category = (d.productCategory || '').trim();
    const price = Number(d.productPrice || 0);
    if(!name){ return; }
    const sku = (d.productSku || '').trim() || generateSupplierProductSku(name);
    supplierProductDraft.push({ name, category, sku, price });
    supplierProductCounter += 1;
    renderSupplierProductList();
    form.reset();
    const skuInput = form.querySelector('[name="productSku"]');
    if(skuInput){ skuInput.value = generateSupplierProductSku(''); }
    const nameField = form.querySelector('[name="productName"]');
    if(nameField){ nameField.focus(); }
  }

  function openCancelModal(btn){
    const row = btn.closest('tr');
    const poNum = row?.cells[0]?.textContent?.trim() || 'this PO';
    cancelPOData = { row, poNum };
    document.getElementById('cancel-po-number').textContent = poNum;
    document.getElementById('cancel-po-modal').classList.add('open');
  }
  function openCancelModalFromRow(row){
    const poNum = row?.cells[0]?.textContent?.trim() || 'this PO';
    cancelPOData = { row, poNum };
    document.getElementById('cancel-po-number').textContent = poNum;
    document.getElementById('cancel-po-modal').classList.add('open');
    closeViewModal();
  }
  function closeCancelModal(){
    document.getElementById('cancel-po-modal').classList.remove('open');
    cancelPOData = null;
  }
  function confirmCancelPO(){
    if(cancelPOData && cancelPOData.row){
      const row = cancelPOData.row;
      row.dataset.status = 'cancelled';
      row.classList.add('cancelled-row');
      row.cells[6].innerHTML = '<span class="status-pill cancelled">Cancelled</span>';
      // Previously this only updated the DOM — the cancellation was never sent
      // to the server, so refreshing the page silently reverted the PO (and any
      // requisition derived from it) back to its old status.
      if(typeof persistPurchaseOrderStatus === 'function'){
        persistPurchaseOrderStatus(row, 'Cancelled').then(() => {
          if(typeof syncRelatedRequisitionStatusForPO === 'function') syncRelatedRequisitionStatusForPO(row, 'Cancelled');
        }).catch(() => {});
      }
      showToast(`PO ${cancelPOData.poNum} cancelled`, 'no');
    }
    closeCancelModal();
  }

  document.addEventListener('keydown', (e)=>{
    if(e.key === 'Escape'){
      Object.keys(ADD_MODAL_MAP).forEach(closeAddModal);
      closeCancelModal();
    }
  });

  /* ---------- Submit handlers ---------- */

  function submitAddPO(e){
    e.preventDefault();
    const form = e.target;
    const modal = form.closest('.modal-overlay');
    const d = Object.fromEntries(new FormData(form).entries());
    const items = collectPoItemRows(modal);
    if(!items.length){
      showToast('Add at least one item (category, item, and quantity) before submitting.', 'no');
      return;
    }
    const qtyNum = items.reduce((sum, it) => sum + it.qty, 0);
    const amountNum = items.reduce((sum, it) => sum + it.amount, 0);
    const primaryCategory = items[0].category || '';
    const itemSummary = items.map(it => it.name).join(', ');
    const poDate = todayISO();
    const priorityLabel = d.priority || 'Normal';

    fetch(procurementUrl('purchase-orders'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: new URLSearchParams({
        po: d.po,
        supplier: d.supplier,
        category: primaryCategory,
        items: JSON.stringify(items),
        priority: priorityLabel,
        expected: d.expected || '',
        createdBy: d.createdBy || '',
        remarks: d.remarks || '',
        reqRef: d.reqRef || ''
      }).toString()
    }).then(res => res.json().then(json => ({ ok: res.ok, json }))).then(({ ok, json }) => {
      if(!ok){
        showToast(json?.message || 'Unable to save purchase order right now.', 'no');
        return;
      }
      if(json && json.po_number) d.po = json.po_number;
      const table = document.querySelector('#po-table tbody');
      if(table){
        const tr = document.createElement('tr');
        tr.dataset.status = 'pending';
        tr.dataset.date = poDate;
        tr.dataset.amount = amountNum;
        tr.dataset.item = itemSummary;
        tr.dataset.items = JSON.stringify(items.map(it => ({ name: it.name, qty: it.qty, unitPrice: it.unitPrice })));
        tr.dataset.expected = d.expected || '';
        tr.dataset.remarks = d.remarks || '';
        tr.dataset.requestedBy = d.createdBy || 'Procurement Team';
        tr.dataset.supplier = d.supplier || '';
        tr.dataset.brand = primaryCategory;
        tr.dataset.qty = qtyNum;
        tr.dataset.unitPrice = items[0].unitPrice || 0;
        tr.dataset.reqRef = d.reqRef || '';
        tr.dataset.priority = priorityLabel;
        tr.dataset.delivery = 'Pending';
        tr.innerHTML = `
          <td><a class="po-link">${d.po}</a></td>
          <td>${supplierPill(d.supplier || 'Unknown Supplier')}</td>
          <td style="max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis" title="${htmlEscape(itemSummary)}">${htmlEscape(items[0].name || '—')}${items.length > 1 ? `<span class="item-more">+${items.length - 1} more</span>` : ''}</td>
          <td><b>${money(amountNum)}</b></td>
          <td>${priorityBadge(priorityLabel)}</td>
          <td>${statusPill('Pending')}</td>
          <td>${fmtDate(poDate)}</td>
          <td><span class="row-actions"><button title="View"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg></button><button title="Edit"><svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l10-10-4-4L4 16v4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></button><button class="del" title="Delete"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button></span></td>`;
        if(json && (json.id || (json.data && json.data.id))) tr.dataset.id = json.id || json.data.id;
        table.prepend(tr);
      }
      NEXT_ID.po++;
      ID_COUNTS.po++;
      if(d.reqRef){
        const reqRow = findReqRowByRef(d.reqRef);
        if(reqRow){
          reqRow.dataset.po = d.po;
          reqRow.dataset.status = 'processing';
          reqRow.children[6].innerHTML = statusPill('Processing');
          updateRowStatus(reqRow, 'Processing');
          const reqId = reqRow.dataset.id;
          if(reqId){
            fetch(procurementUrl(`requisitions/${reqId}`), {
              method: 'PUT',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
              },
              body: new URLSearchParams({ status: 'Processing' }).toString()
            }).then(() => {}).catch(() => {
              console.warn('Unable to persist requisition status update for', reqId);
            });
          }
        }
      }
      initRowActionButtons();
      updateStatusCounts();
      showToast(`Purchase Order ${d.po} created`, 'ok');
      closeAddModal('po');
      if(typeof pollLiveStats === 'function') pollLiveStats();
    }).catch(() => {
      showToast('Unable to save purchase order right now.', 'no');
    });
  }

  function submitAddSupplier(e){
    e.preventDefault();
    const d = Object.fromEntries(new FormData(e.target).entries());
    const products = Array.isArray(JSON.parse(d.productsJson || '[]')) ? JSON.parse(d.productsJson || '[]') : [];

    fetch(procurementUrl('suppliers'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: new URLSearchParams({
        sid: d.sid || '',
        name: d.name || '',
        contact: d.contact || '',
        email: d.email || '',
        phone: d.phone || '',
        address: d.address || '',
        brand: d.brand || '',
        status: d.status || 'active',
        warehouse_id: d.warehouse_id || '',
        productsJson: JSON.stringify(products)
      }).toString()
    }).then(async res => {
      const json = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(json?.message || 'Unable to save supplier right now.');
      return json;
    }).then(json => {
      const table = document.querySelector('#suppliers-table tbody');
      if(table){
        const tr = document.createElement('tr');
        tr.dataset.sid = d.sid || '';
        tr.dataset.category = d.brand || '';
        tr.dataset.brand = d.brand || '';
        tr.dataset.warehouseId = d.warehouse_id || '';
        tr.dataset.status = (d.status || 'active').replace(/^./, m => m.toUpperCase());
        tr.dataset.terms = 'Net 30';
        tr.dataset.lastActivity = 'Newly onboarded';
        tr.dataset.itemName = products[0]?.name || '';
        tr.dataset.sku = products[0]?.sku || '';
        tr.dataset.unitPrice = products[0]?.price || '';
        tr.dataset.products = JSON.stringify(products);
        tr.innerHTML = `
          <td><div class="supplier-pill-cell">${supplierPill(d.name)}</div></td>
          <td>${htmlEscape(d.brand || '—')}</td>
          <td>${htmlEscape(d.contact || '—')}</td>
          <td>${htmlEscape(d.email || '—')}</td>
          <td>${htmlEscape(d.phone || '—')}</td>
          <td>${htmlEscape(d.address || '—')}</td>
          <td><span class="row-actions"><button title="View"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg></button><button title="Edit"><svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l10-10-4-4L4 16v4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></button><button class="del" title="Delete"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button></span></td>`;
        if(json && (json.id || (json.data && json.data.id))) tr.dataset.id = json.id || json.data.id;
        table.prepend(tr);
      }
      NEXT_ID.sup++;
      // supplier IDs are NEXT_ID-based; no change to ID_COUNTS for suppliers
      initRowActionButtons();
      addSupplierOptionToPoForm(d.name);
      refreshPoSupplierOptions(document.getElementById('add-po-modal'));
      refreshDeliverySupplierOptions();
      showToast(`Supplier ${d.name} added successfully`, 'ok');
      closeAddModal('supplier');
    }).catch(error => {
      showToast(error.message || 'Unable to save supplier right now.', 'no');
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    refreshDeliverySupplierOptions();
  });

  function submitAddReq(e){
    e.preventDefault();
    const d = Object.fromEntries(new FormData(e.target).entries());

    fetch(procurementUrl('requisitions'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: new URLSearchParams({
        rq: d.rq,
        item: d.item || '',
        qty: d.qty || '1',
        uom: d.uom || 'pcs',
        dept: d.dept || '',
        requester: d.requester || '',
        dateReq: d.dateReq || '',
        notes: d.notes || ''
      }).toString()
    }).then(res => res.json()).then(json => {
      const table = document.querySelector('#requisitions-table tbody');
      if(table){
        const tr = document.createElement('tr');
        tr.dataset.status = 'pending';
        tr.dataset.date = d.dateReq;
        tr.dataset.uom = d.uom || 'pcs';
        tr.dataset.notes = d.notes || '';
        tr.innerHTML = `
          <td><a class="po-link">${d.rq}</a></td>
          <td>${d.item}</td>
          <td>${d.qty}</td>
          <td>${deliveryBadge('Scheduled')}</td>
          <td>${d.dept}</td>
          <td>${d.requester}</td>
          <td>${statusPill('Pending')}</td>
          <td>${fmtDate(d.dateReq)}</td>
          <td><span class="row-actions"><button title="View"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg></button><button title="Edit"><svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l10-10-4-4L4 16v4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></button><button class="del" title="Delete"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button></span></td>`;
        if(json && (json.id || (json.data && json.data.id))) tr.dataset.id = json.id || json.data.id;
        table.prepend(tr);
      }
      NEXT_ID.req++;
      ID_COUNTS.req++;
      initRowActionButtons();
      showToast(`Requisition ${d.rq} submitted for approval`, 'ok');
      closeAddModal('req');
      if(typeof pollLiveStats === 'function') pollLiveStats();
    }).catch(() => {
      showToast('Unable to save requisition right now.', 'no');
    });
  }

  function submitAddDelivery(e){
    e.preventDefault();
    const d = Object.fromEntries(new FormData(e.target).entries());
    const poInfo = getPoInfo(d.po || '');
    if(!poInfo || poInfo.status !== 'approved'){
      showToast('Only approved purchase orders can be logged in deliveries.', 'info');
      return;
    }
    if(!(d.items || '').trim()){
      showToast('Select a purchase order so its items can be loaded before logging the delivery.', 'no');
      return;
    }
    const expectedDate = poInfo.expected || '';
    const isDelayed = Boolean(expectedDate && new Date(expectedDate) < new Date(todayISO()));
    const statusLabel = isDelayed ? 'Delayed' : 'intransit';
    const stage = isDelayed ? '1' : '2';

    fetch(procurementUrl('deliveries'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: new URLSearchParams({
        dr: d.dr,
        po: d.po,
        supplier: d.supplier,
        items: d.items || '',
        qty: d.qty || '1',
        delDate: d.delDate || '',
        status: statusLabel,
        remarks: d.remarks || '',
        carrier: d.carrier || '',
        warehouse_id: d.warehouse_id || ''
      }).toString()
    }).then(res => res.json().then(json => ({ ok: res.ok, json }))).then(({ ok, json }) => {
      if(!ok){
        showToast(json?.message || 'Only approved purchase orders can be logged in deliveries.', 'info');
        return;
      }
      const shipmentNumber = json?.shipment_number || json?.data?.shipment_number || d.dr;
      const table = document.querySelector('#deliveries-table tbody');
      const poRow = findPoRowByNumber(d.po || '');
      if(table){
        const tr = document.createElement('tr');
        tr.dataset.status = isDelayed ? 'delayed' : 'intransit';
        tr.dataset.date = d.delDate;
        tr.dataset.ship = shipmentNumber;
        tr.dataset.po = d.po;
        tr.dataset.sup = d.supplier;
        tr.dataset.items = d.items || '';
        tr.dataset.stage = stage;
        tr.dataset.note = d.remarks || `${d.items} · Qty ${d.qty}`;
        tr.dataset.carrier = d.carrier || 'Assigned carrier';
        tr.dataset.expected = expectedDate;
        tr.dataset.warehouse = (document.querySelector('#delivery-warehouse-select')?.selectedOptions?.[0]?.textContent || '').trim();
        // Only the first item is shown in the table; the rest live in the
        // tracking details modal (data-items keeps the full list).
        const delItemsList = String(d.items || '').split(',').map(s => s.trim()).filter(Boolean);
        const delItemsCell = `${htmlEscape(delItemsList[0] || '—')}${delItemsList.length > 1 ? `<span class="item-more">+${delItemsList.length - 1} more</span>` : ''}`;
        tr.innerHTML = `
          <td><a class="po-link">${htmlEscape(shipmentNumber)}</a></td>
          <td><a class="po-link">${d.po}</a></td>
          <td>${supplierPill(d.supplier)}</td>
          <td title="${htmlEscape(d.items || '')}">${delItemsCell}</td>
          <td>${fmtDate(expectedDate)}</td>
          <td>${statusPill(statusLabel)}</td>
          <td>${fmtDate(d.delDate)}</td>
          <td><span class="row-actions"><button title="Track" onclick="openTrackModal(this)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg></button></span></td>`;
        if(json && (json.id || (json.data && json.data.id))) tr.dataset.id = json.id || json.data.id;
        table.prepend(tr);
      }
      if(poRow){
        poRow.dataset.status = 'processing';
        poRow.children[5].innerHTML = statusPill('Processing');
      }
      const reqRow = findReqRowByRef(d.po);
      if(reqRow){
        updateRowStatus(reqRow, 'intransit');
        reqRow.children[6].innerHTML = statusPill('intransit');
        reqRow.dataset.status = 'intransit';
        persistRequisitionStatus(reqRow, 'intransit');
      }
      const shipmentSequence = Number(String(shipmentNumber).match(/(\d+)$/)?.[1] || 0);
      ID_COUNTS.dr = Math.max(ID_COUNTS.dr, shipmentSequence);
      NEXT_ID.dr = ID_COUNTS.dr + 1;
      initRowActionButtons();
      updateStatusCounts();
      showToast(`Delivery ${shipmentNumber} logged`, 'ok');
      closeAddModal('delivery');
      if(typeof pollLiveStats === 'function') pollLiveStats();
    }).catch(() => {
      showToast('Unable to save delivery right now.', 'no');
    });
  }

  function submitAddInvoice(e){
    e.preventDefault();
    const d = Object.fromEntries(new FormData(e.target).entries());
    const table = document.querySelector('#invoices-table tbody');
    if(!table){ closeAddModal('invoice'); return; }
    const statusLabel = ({unpaid:'Unpaid', partial:'Overdue', paid:'Paid', overdue:'Overdue'})[d.status] || 'Unpaid';
    const tr = document.createElement('tr');
    tr.dataset.status = statusLabel.toLowerCase();
    tr.dataset.date = d.invDate;
    tr.dataset.amount = Number(d.amount || 0);
    tr.dataset.supplier = d.supplier || '';
    tr.dataset.dueDate = fmtDate(d.dueDate);
    tr.dataset.method = d.method || '';
    tr.dataset.notes = d.notes || '';
    tr.innerHTML = `
      <td><a class="po-link">${d.inv}</a></td>
      <td><a class="po-link">${d.po}</a></td>
      <td>${fmtDate(d.invDate)}</td>
      <td><b>${money(d.amount)}</b></td>
      <td>${statusPill(statusLabel)}</td>
      <td><span class="row-actions"><button title="View"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg></button><button title="Edit"><svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l10-10-4-4L4 16v4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></button><button class="del" title="Delete"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button></span></td>`;
    table.prepend(tr);
    NEXT_ID.inv++;
    initRowActionButtons();
    showToast(`Invoice ${d.inv} recorded`, 'ok');
    closeAddModal('invoice');
  }
