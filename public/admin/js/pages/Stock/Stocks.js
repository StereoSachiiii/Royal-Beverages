/**
 * Stocks.js — Stock & Warehouse management module.
 * Uses EntityBuilder to eliminate boilerplate.
 */

import { API_ROUTES, buildQueryString } from '../../dashboard.routes.js';
import { apiRequest, escapeHtml, formatDate, getTemplate } from '../../utils.js';
import { createEntityModule } from '../../components/EntityBuilder.js';

async function fetchStock(limit = 20, offset = 0, query = '') {
  try {
    const base = API_ROUTES.ADMIN_VIEWS.LIST('stock');
    const url = base + buildQueryString({ limit, offset, ...(query ? { search: query } : {}) });
    const res = await apiRequest(url);
    if (!res.success) throw new Error(res.message || 'Failed to fetch stock');
    return res.data?.items || (Array.isArray(res.data) ? res.data : []);
  } catch (err) {
    console.error('[Stock] Fetch failed', err);
    return [];
  }
}

async function fetchStockItem(id) {
  try {
    const url = API_ROUTES.ADMIN_VIEWS.DETAIL('stock', id);
    const res = await apiRequest(url);
    if (!res.success) throw new Error(res.message || 'Failed to fetch stock details');
    return res.data;
  } catch (err) {
    throw err;
  }
}

async function fetchProducts() {
  try {
    const res = await apiRequest(API_ROUTES.PRODUCTS.LIST + '?limit=100');
    return res.success ? res.data || [] : [];
  } catch (err) {
    console.error(err);
    return [];
  }
}

async function fetchWarehouses() {
  try {
    const res = await apiRequest(API_ROUTES.WAREHOUSES.LIST + '?limit=100');
    return res.success ? res.data || [] : [];
  } catch (err) {
    console.error(err);
    return [];
  }
}

// ─── Row Renderer ─────────────────────────────────────────────────────────────

function renderRow(s) {
  const total = s.quantity || 0;
  const reserved = s.reserved || 0;
  const available = s.available ?? total - reserved;

  const availBadge =
    available <= 0
      ? `<span class="inline-flex items-center px-2 py-1 text-[9px] font-black uppercase tracking-wider bg-red-100 text-red-700 rounded-none">Out of Stock</span>`
      : available < 20
        ? `<span class="inline-flex items-center px-2 py-1 text-[9px] font-black uppercase tracking-wider bg-amber-100 text-amber-700 rounded-none">Low — ${available}</span>`
        : `<span class="inline-flex items-center px-2 py-1 text-[9px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700 rounded-none">${available}</span>`;

  const updated = s.updated_at ? formatDate(s.updated_at) : '—';

  return `<tr class="group hover:bg-gray-50/50 transition-colors">
        <td class="px-6 py-4 text-[10px] font-bold text-gray-300 font-mono whitespace-nowrap">#${escapeHtml(String(s.id))}</td>
        <td class="px-6 py-4">
            <div class="font-semibold text-black text-sm">${escapeHtml(s.product_name || '—')}</div>
            <div class="text-[10px] text-gray-400 mt-0.5">Product #${s.product_id}</div>
        </td>
        <td class="px-6 py-4">
            <div class="font-medium text-gray-800 text-sm">${escapeHtml(s.warehouse_name || '—')}</div>
        </td>
        <td class="px-6 py-4 text-center font-bold text-black tabular-nums">${total}</td>
        <td class="px-6 py-4 text-center font-medium text-amber-600 tabular-nums">${reserved}</td>
        <td class="px-6 py-4 text-center">${availBadge}</td>
        <td class="px-6 py-4 text-gray-400 text-[10px] whitespace-nowrap">${updated}</td>
        <td class="px-6 py-4 text-right">
            <div class="flex items-center justify-end gap-2">
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-view" data-id="${s.id}" title="View details">
                    <span class="text-[10px]">👁️</span>
                </button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-edit" data-id="${s.id}" title="Adjust stock">
                    <span class="text-[10px]">✏️</span>
                </button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-red-500 hover:bg-red-500 hover:text-white hover:border-red-500 transition-all js-delete" data-id="${s.id}" title="Remove entry">
                    <span class="text-[10px]">🗑️</span>
                </button>
            </div>
        </td>
    </tr>`;
}

// ─── View Modal ───────────────────────────────────────────────────────────────

function renderViewModal(s) {
  const total = s.quantity || 0;
  const reserved = s.reserved || 0;
  const available = s.available ?? total - reserved;
  const unitPrice = (s.price_cents || 0) / 100;
  const portfolioValue = (total * (s.price_cents || 0)) / 100;

  const historyRows =
    Array.isArray(s.recent_movements) && s.recent_movements.length
      ? s.recent_movements
          .map(
            (m) => `
            <tr class="tr">
                <td class="td font-mono" style="font-size:11px;">${escapeHtml(m.order_number || 'Manual')}</td>
                <td class="td"><span class="badge badge-secondary" style="font-size:10px;">${escapeHtml(m.status || 'Recorded')}</span></td>
                <td class="td text-right font-bold" style="font-size:12px;">${m.quantity || 0}</td>
            </tr>`
          )
          .join('')
      : `<tr class="tr"><td colspan="3" class="td text-center text-slate-400" style="padding:20px;font-style:italic;">No recent movements</td></tr>`;

  return `
        <div class="flex flex-col" style="gap:24px; padding: 8px;">
            <div class="flex items-center justify-between" style="padding-bottom:16px;border-bottom:1px solid var(--slate-100);">
                <div class="flex items-center" style="gap:14px;">
                    <div class="thumb-xl rounded-2xl bg-slate-50 border flex items-center justify-center text-3xl shadow-sm">📦</div>
                    <div>
                         <h3 class="font-bold text-black" style="font-size:22px;letter-spacing:-0.02em;">${escapeHtml(s.product_name || '—')}</h3>
                         <p class="text-sm text-slate-500">Location: <span class="font-semibold text-black">${escapeHtml(s.warehouse_name || '—')}</span></p>
                    </div>
                </div>
                <div>
                    ${available <= 0 ? '<span class="badge badge-inactive" style="padding:6px 16px;font-size:12px;">Out of Stock</span>' : available < 20 ? '<span class="badge badge-warning" style="padding:6px 16px;font-size:12px;">Running Low</span>' : '<span class="badge badge-active" style="padding:6px 16px;font-size:12px;">Well Stocked</span>'}
                </div>
            </div>

            <div class="flex" style="gap:12px;">
                <div class="google-card flex-1 text-center" style="padding:16px;border-left:4px solid var(--primary);">
                    <div class="uppercase text-slate-500 font-bold" style="font-size:10px;letter-spacing:0.1em;margin-bottom:4px;">Total Units</div>
                    <div class="font-bold text-black" style="font-size:24px;font-family:monospace;">${total}</div>
                </div>
                <div class="google-card flex-1 text-center" style="padding:16px;border-left:4px solid var(--warning);">
                    <div class="uppercase text-slate-500 font-bold" style="font-size:10px;letter-spacing:0.1em;margin-bottom:4px;">Reserved</div>
                    <div class="font-bold text-black" style="font-size:24px;font-family:monospace;">${reserved}</div>
                </div>
                <div class="google-card flex-1 text-center" style="padding:16px;border-left:4px solid ${available <= 0 ? 'var(--danger)' : 'var(--success)'};">
                    <div class="uppercase text-slate-500 font-bold" style="font-size:10px;letter-spacing:0.1em;margin-bottom:4px;">Available</div>
                    <div class="font-bold text-black" style="font-size:24px;font-family:monospace;">${available}</div>
                </div>
            </div>

            <div class="flex" style="gap:20px;">
                <div style="flex:1;display:flex;flex-direction:column;gap:16px;">
                    <div class="google-card" style="padding:20px;background:var(--slate-900);color:white;">
                        <h4 class="text-slate-500 font-bold uppercase" style="font-size:9px;letter-spacing:0.1em;margin-bottom:12px;">Inventory Value</h4>
                        <div class="flex justify-between" style="font-size:13px;margin-bottom:8px;"><span class="text-slate-400">Unit Price</span><span class="font-mono text-indigo-300">Rs ${unitPrice.toFixed(2)}</span></div>
                        <div class="flex justify-between items-baseline" style="border-top:1px solid #334155;padding-top:12px;margin-top:4px;">
                            <span class="text-slate-400 font-black uppercase" style="font-size:11px;">Total Value</span>
                            <span class="font-mono font-black" style="font-size:28px;color:var(--success);">Rs ${portfolioValue.toFixed(2)}</span>
                        </div>
                    </div>
                    <div class="google-card" style="padding:16px;">
                         <h4 class="text-slate-400 font-bold uppercase" style="font-size:10px;letter-spacing:0.1em;margin-bottom:12px;border-bottom:1px solid var(--slate-100);padding-bottom:4px;">Record Info</h4>
                         <div class="flex flex-col" style="gap:6px;">
                            <div class="flex justify-between" style="font-size:12px;"><span class="text-slate-500">Stock ID</span><span class="font-mono font-bold">#${s.id}</span></div>
                            <div class="flex justify-between" style="font-size:12px;"><span class="text-slate-500">Warehouse</span><span class="font-bold">${escapeHtml(s.warehouse_name || '—')}</span></div>
                            <div class="flex justify-between" style="font-size:12px;"><span class="text-slate-500">Last Updated</span><span class="font-bold">${formatDate(s.updated_at)}</span></div>
                         </div>
                    </div>
                </div>
                <div style="flex:1.2;display:flex;flex-direction:column;gap:16px;">
                     <div>
                        <h4 class="text-slate-400 font-bold uppercase" style="font-size:11px;letter-spacing:0.05em;margin-bottom:10px;">Recent Order Movements</h4>
                        <div class="table-container">
                            <table class="table">
                                <thead><tr class="tr"><th class="th">Order Ref</th><th class="th">Status</th><th class="th text-right">Qty</th></tr></thead>
                                <tbody>${historyRows}</tbody>
                            </table>
                        </div>
                     </div>
                </div>
            </div>

            <div class="flex justify-end" style="padding-top:12px;border-top:1px solid var(--slate-100);gap:8px;">
                <button class="btn btn-primary js-edit" data-id="${s.id}" style="padding:0 32px;">✏️ Adjust Stock Level</button>
            </div>
        </div>`;
}

// ─── Form Builders ────────────────────────────────────────────────────────────

async function renderCreateForm() {
  const [products, warehouses] = await Promise.all([fetchProducts(), fetchWarehouses()]);
  const frag = getTemplate('tpl-stock-create-form');
  const pSel = frag.querySelector('#stk-create-product');
  const wSel = frag.querySelector('#stk-create-warehouse');
  if (pSel)
    pSel.innerHTML =
      '<option value="" disabled selected>Select a product</option>' +
      products.map((p) => `<option value="${p.id}">${escapeHtml(p.name)}</option>`).join('');
  if (wSel)
    wSel.innerHTML =
      '<option value="" disabled selected>Select a warehouse</option>' +
      warehouses.map((w) => `<option value="${w.id}">${escapeHtml(w.name)}</option>`).join('');
  return frag;
}

async function renderAdjustForm(id) {
  const s = await fetchStockItem(id);
  const available = (s.quantity || 0) - (s.reserved || 0);
  const frag = getTemplate('tpl-stock-adjust-form', {
    total: s.quantity || 0,
    reserved: s.reserved || 0,
    available,
    pending_orders: s.pending_orders?.length || 0,
  });
  frag.firstElementChild.dataset.total = s.quantity || 0;
  frag.firstElementChild.dataset.reserved = s.reserved || 0;

  const footer = frag.querySelector('.flex.justify-end.gap-3.pt-6');
  if (footer) {
    const del = document.createElement('button');
    del.type = 'button';
    del.className = 'btn btn-outline text-danger mr-auto js-delete-btn';
    del.innerHTML = '🗑️ Delete Entry';
    footer.prepend(del);
  }
  return frag;
}

async function renderFormModal(id = null) {
  if (id === null) return await renderCreateForm();
  return await renderAdjustForm(id);
}

// ─── Form Handlers ────────────────────────────────────────────────────────────

function initCreateHandlers(modalRoot, onSuccess, closeModalFn, showFormErrorFn) {
  const form = modalRoot.querySelector('#stk-create-form');
  if (!form) return;
  modalRoot.querySelector('#stk-create-cancel')?.addEventListener('click', closeModalFn);
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submit = form.querySelector('button[type="submit"]');
    submit.disabled = true;
    submit.innerHTML = 'Creating…';
    try {
      const formData = new FormData(form);
      const payload = {
        product_id: parseInt(formData.get('product_id')),
        warehouse_id: parseInt(formData.get('warehouse_id')),
        quantity: parseInt(formData.get('quantity')) || 0,
        reserved: 0,
        reason: formData.get('reason').trim(),
      };
      const res = await apiRequest(API_ROUTES.STOCK.CREATE, { method: 'POST', body: payload });
      if (!res.success) throw new Error(res.message);
      closeModalFn();
      onSuccess?.(res.data, 'created');
    } catch (err) {
      showFormErrorFn(form, err.message);
      submit.disabled = false;
      submit.innerHTML = 'Create Entry';
    }
  });
}

function initAdjustHandlers(modalRoot, id, onSuccess, closeModalFn, showFormErrorFn) {
  const form = modalRoot.querySelector('#stk-adjust-form');
  if (!form) return;
  const typeSel = form.querySelector('#stk-adj-type');
  const amountInp = form.querySelector('[name="adjustment_amount"]');
  const preview = form.querySelector('#stk-adj-preview');
  const prevCalc = form.querySelector('#stk-preview-calc');
  const prevStatus = form.querySelector('#stk-preview-status');
  const delBtn = form.querySelector('.js-delete-btn');

  modalRoot.querySelector('#stk-adjust-cancel')?.addEventListener('click', closeModalFn);

  const updatePreview = () => {
    const type = typeSel.value;
    const amount = parseInt(amountInp.value) || 0;
    const total = parseInt(form.dataset.total);
    const res = parseInt(form.dataset.reserved);
    if (!amount) {
      preview.classList.add('hidden');
      return;
    }
    let target = 0;
    if (type === 'add') target = total + amount;
    else if (type === 'remove') target = total - amount;
    else target = amount;
    const valid = target >= res;
    prevCalc.textContent = `New total: ${target}`;
    prevStatus.textContent = valid ? '✓ Looks good' : `✗ Cannot go below reserved amount (${res})`;
    prevStatus.className = `text-[10px] font-bold mt-1 uppercase tracking-widest ${valid ? 'text-green-600' : 'text-danger'}`;
    preview.classList.remove('hidden');
  };
  typeSel?.addEventListener('change', updatePreview);
  amountInp?.addEventListener('input', updatePreview);

  if (delBtn) {
    delBtn.addEventListener('click', async () => {
      if (!delBtn.dataset.confirmed) {
        delBtn.dataset.confirmed = '1';
        delBtn.innerHTML = '⚠️ Confirm Delete?';
        delBtn.classList.add('btn-warning');
        setTimeout(() => {
          if (delBtn.isConnected) {
            delete delBtn.dataset.confirmed;
            delBtn.innerHTML = '🗑️ Delete Entry';
            delBtn.classList.remove('btn-warning');
          }
        }, 3000);
        return;
      }
      try {
        await apiRequest(API_ROUTES.STOCK.DELETE(id), { method: 'DELETE' });
        closeModalFn();
        onSuccess?.(null, 'deleted');
      } catch (err) {
        alert(err.message);
      }
    });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(form);
    const total = parseInt(form.dataset.total);
    const res = parseInt(form.dataset.reserved);
    const amount = parseInt(formData.get('adjustment_amount'));
    let target = 0;

    const type = formData.get('adjustment_type');
    if (type === 'add') target = total + amount;
    else if (type === 'remove') target = total - amount;
    else target = amount;

    if (target < res) {
      showFormErrorFn(form, `Cannot go below reserved amount (${res})`);
      return;
    }
    const submit = form.querySelector('button[type="submit"]');
    submit.disabled = true;
    submit.innerHTML = 'Saving…';
    try {
      const payload = {
        quantity: target,
        reason: `[${formData.get('reason_category').toUpperCase()}] ${formData.get('reason_notes')}`,
      };
      await apiRequest(API_ROUTES.STOCK.UPDATE(id), { method: 'PUT', body: payload });
      closeModalFn();
      onSuccess?.(null, 'updated');
    } catch (err) {
      showFormErrorFn(form, err.message);
      submit.disabled = false;
      submit.innerHTML = 'Apply Adjustment';
    }
  });
}

function initFormHandlersOverride(modalRoot, id, onSuccess, closeModalFn, showFormErrorFn) {
  if (id === null) initCreateHandlers(modalRoot, onSuccess, closeModalFn, showFormErrorFn);
  else initAdjustHandlers(modalRoot, id, onSuccess, closeModalFn, showFormErrorFn);
}

// ─── Entity Builder ───────────────────────────────────────────────────────────

const { Render: Stock, Init: initStock } = createEntityModule({
  entityName: 'Stock & Warehouses',
  entitySubtitle: 'Managing operational nodes and inventory logistics',
  apiRoutes: {
    list: () => API_ROUTES.ADMIN_VIEWS.LIST('stock'),
    detail: (id) => API_ROUTES.ADMIN_VIEWS.DETAIL('stock', id),
    create: API_ROUTES.STOCK.CREATE,
    update: (id) => API_ROUTES.STOCK.UPDATE(id),
    delete: (id) => API_ROUTES.STOCK.DELETE(id),
  },
  fetchList: fetchStock,
  fetchSingle: fetchStockItem,
  tableHeaderHtml: `<tr class="tr">
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">ID</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Product</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Warehouse</th>
        <th class="px-8 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Total</th>
        <th class="px-8 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Reserved</th>
        <th class="px-8 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Available</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Last Updated</th>
        <th class="px-8 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Actions</th>
    </tr>`,
  renderRow,
  renderViewModal,
  renderFormModal,
  initFormHandlersOverride,
  searchPlaceholder: 'Search by product name or warehouse…',
  createBtnText: 'Add Stock Entry',
});

export { Stock, initStock };
