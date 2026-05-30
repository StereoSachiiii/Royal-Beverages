/**
 * OrderItems.js — Order Items domain module.
 * Uses EntityBuilder to eliminate boilerplate.
 */

import { API_ROUTES, buildQueryString } from '../../dashboard.routes.js';
import { apiRequest, escapeHtml, formatDate, getTemplate, closeModal } from '../../utils.js';
import { createEntityModule } from '../../components/EntityBuilder.js';

async function fetchOrderItems(limit = 20, offset = 0, query = '') {
  try {
    const url =
      API_ROUTES.ORDER_ITEMS.LIST +
      buildQueryString({ limit, offset, ...(query ? { search: query } : {}) });
    const res = await apiRequest(url);
    if (!res.success) throw new Error(res.message || 'Failed to fetch order items');
    return res.data?.items || (Array.isArray(res.data) ? res.data : []);
  } catch (err) {
    console.error('[OrderItems] Fetch failed', err);
    return [];
  }
}

async function fetchOrderItem(id) {
  try {
    const res = await apiRequest(API_ROUTES.ORDER_ITEMS.GET(id));
    if (!res.success) throw new Error(res.message || 'Failed to fetch order item');
    return res.data;
  } catch (err) {
    throw err;
  }
}

function getStatusClass(status) {
  switch ((status || 'pending').toLowerCase()) {
    case 'delivered':
    case 'completed':
    case 'paid':
      return 'badge-active';
    case 'processing':
    case 'shipped':
      return 'badge-info';
    case 'cancelled':
    case 'returned':
      return 'badge-inactive';
    case 'pending':
      return 'badge-warning';
    default:
      return 'badge-info';
  }
}

function formatCurrency(cents) {
  return (cents / 100).toFixed(2);
}

// ─── Row Renderer ─────────────────────────────────────────────────────────────

function renderRow(it) {
  const subtotal = (it.price_cents || 0) * (it.quantity || 0);
  return `<tr class="group hover:bg-gray-50/50 transition-colors">
        <td class="px-6 py-4 text-[10px] font-bold text-gray-300 font-mono whitespace-nowrap">#${escapeHtml(String(it.id))}</td>
        <td class="px-6 py-4">
            <div class="font-bold text-black" style="font-size:13px;">${escapeHtml(it.product_name || 'Unknown Product')}</div>
            <div class="text-slate-500 font-mono" style="font-size:11px;">Order #${it.order_id}</div>
        </td>
        <td class="px-6 py-4">
            <div class="font-semibold text-black" style="font-size:12px;">${escapeHtml(it.user_name || 'Anonymous')}</div>
            <div class="text-slate-500" style="font-size:11px;">${escapeHtml(it.user_email || '')}</div>
        </td>
        <td class="px-6 py-4 text-center font-mono font-bold" style="font-size:13px;">x${it.quantity || 0}</td>
        <td class="px-6 py-4 font-mono text-slate-500" style="font-size:12px;">Rs ${formatCurrency(it.price_cents || 0)}</td>
        <td class="px-6 py-4 font-bold font-mono text-black" style="font-size:12px;">Rs ${formatCurrency(subtotal)}</td>
        <td class="px-6 py-4"><span class="badge ${getStatusClass(it.order_status)}" style="font-size:10px;">${escapeHtml(it.order_status || 'N/A')}</span></td>
        <td class="px-6 py-4 text-slate-500" style="font-size:11px;white-space:nowrap;">${it.created_at ? formatDate(it.created_at) : '—'}</td>
        <td class="px-6 py-4 text-right">
            <div class="flex items-center justify-end gap-2">
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-view" data-id="${it.id}"><span class="text-[10px]">👁️</span></button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-edit" data-id="${it.id}"><span class="text-[10px]">✏️</span></button>
            </div>
        </td>
    </tr>`;
}

// ─── View Modal ───────────────────────────────────────────────────────────────

function renderViewModal(it) {
  const subtotal = (it.price_cents || 0) * (it.quantity || 0);
  return `
        <div class="flex flex-col" style="gap:20px; padding:8px;">
            <div class="flex items-center justify-between" style="padding-bottom:16px;border-bottom:1px solid var(--slate-100);">
                <div>
                     <h3 class="font-bold text-black" style="font-size:20px;letter-spacing:-0.02em;">Order Item #${it.id}</h3>
                     <p class="text-sm text-slate-500">Order Ref #${it.order_id} • ${formatDate(it.created_at)}</p>
                </div>
                <span class="badge ${getStatusClass(it.order_status)} uppercase" style="font-size:10px;padding:4px 10px;">${it.order_status || 'UNKNOWN'}</span>
            </div>

            <div class="flex" style="gap:20px;">
                <div style="flex:1.2;display:flex;flex-direction:column;gap:16px;">
                    <div class="google-card" style="padding:16px;background:var(--slate-50);">
                         <div class="text-slate-400 font-bold uppercase" style="font-size:9px;letter-spacing:0.1em;margin-bottom:8px;">Product</div>
                         <div class="font-bold text-black" style="font-size:16px;">${escapeHtml(it.product_name)}</div>
                         <div class="flex justify-between" style="margin-top:12px;font-size:12px;color:var(--slate-500);">
                            <span>Catalog ID</span><span class="font-mono text-black font-semibold">#${it.product_id}</span>
                         </div>
                    </div>
                    <div class="google-card" style="padding:16px;">
                         <div class="text-slate-400 font-bold uppercase" style="font-size:9px;letter-spacing:0.1em;margin-bottom:10px;">Logistics</div>
                         <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div style="background:var(--slate-50);padding:10px;border-radius:8px;">
                                <div class="text-slate-400 font-bold uppercase" style="font-size:9px;">Warehouse</div>
                                <div class="font-mono font-bold text-black" style="font-size:13px;">${it.warehouse_id || 'Global'}</div>
                            </div>
                            <div style="background:var(--slate-50);padding:10px;border-radius:8px;">
                                <div class="text-slate-400 font-bold uppercase" style="font-size:9px;">Qty Dispatched</div>
                                <div class="font-mono font-bold text-black" style="font-size:13px;">x${it.quantity}</div>
                            </div>
                         </div>
                    </div>
                </div>

                <div style="flex:1;display:flex;flex-direction:column;gap:16px;">
                    <div style="background:var(--black);color:white;padding:20px;border-radius:16px;">
                        <div class="text-slate-500 font-bold uppercase text-center" style="font-size:9px;letter-spacing:0.1em;margin-bottom:12px;">Financial Summary</div>
                        <div class="flex justify-between" style="font-size:12px;margin-bottom:8px;"><span class="text-slate-400">Unit Price</span><span class="font-mono">Rs ${formatCurrency(it.price_cents)}</span></div>
                        <div class="flex justify-between" style="font-size:12px;border-top:1px solid #334155;padding-top:8px;margin-bottom:12px;"><span class="text-slate-400">Quantity</span><span class="font-mono">x${it.quantity}</span></div>
                        <div class="flex justify-between items-baseline">
                            <span class="text-indigo-400 font-black uppercase" style="font-size:12px;">Total</span>
                            <span class="font-mono font-black" style="font-size:24px;">Rs ${formatCurrency(subtotal)}</span>
                        </div>
                    </div>
                    <div class="google-card" style="padding:16px;">
                         <div class="text-slate-400 font-bold uppercase" style="font-size:9px;letter-spacing:0.1em;margin-bottom:8px;border-bottom:1px solid var(--slate-100);padding-bottom:4px;">Customer</div>
                         <div class="font-bold text-black" style="font-size:13px;">${escapeHtml(it.user_name || 'Anonymous')}</div>
                         <div class="text-xs text-slate-500">${escapeHtml(it.user_email || '—')}</div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end" style="padding-top:12px;border-top:1px solid var(--slate-100);gap:8px;">
                <button class="btn btn-primary js-edit" data-id="${it.id}" style="padding:0 32px;">✏️ Adjust Item</button>
            </div>
        </div>`;
}

// ─── Form Builder ─────────────────────────────────────────────────────────────

async function renderFormModal(id) {
  const it = await fetchOrderItem(id);
  const frag = getTemplate('tpl-order-item-form', {
    id: it.id,
    order_id: it.order_id,
    product_name: escapeHtml(it.product_name),
    quantity: it.quantity,
    unit_price: formatCurrency(it.price_cents),
    subtotal: formatCurrency(it.price_cents * it.quantity),
    warehouse_id: it.warehouse_id || '',
  });
  return frag;
}

// ─── Custom Form Handlers ─────────────────────────────────────────────────────

function initFormHandlersOverride(modalRoot, id, onSuccess, closeModalFn, showFormErrorFn) {
  const form = modalRoot.querySelector('#oit-form');
  const submit = modalRoot.querySelector('#oit-submit');
  if (!form) return;

  const cancel = modalRoot.querySelector('#oit-cancel');
  if (cancel) cancel.addEventListener('click', closeModalFn);

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!submit.dataset.confirmed) {
      submit.dataset.confirmed = '1';
      submit.innerHTML = '⚠️ Confirm?';
      submit.classList.remove('btn-primary');
      submit.classList.add('btn-warning');
      setTimeout(() => {
        if (submit.dataset.confirmed) {
          delete submit.dataset.confirmed;
          submit.innerHTML = 'Save Changes';
          submit.classList.add('btn-primary');
          submit.classList.remove('btn-warning');
        }
      }, 3000);
      return;
    }
    submit.disabled = true;
    submit.innerHTML = 'Saving…';
    try {
      const formData = new FormData(form);
      const payload = {
        quantity: parseInt(formData.get('quantity')),
        warehouse_id: formData.get('warehouse_id') ? parseInt(formData.get('warehouse_id')) : null,
      };
      await apiRequest(API_ROUTES.ORDER_ITEMS.UPDATE(id), { method: 'PUT', body: payload });
      closeModalFn();
      onSuccess?.(null, 'updated');
    } catch (err) {
      showFormErrorFn(form, err.message);
      delete submit.dataset.confirmed;
      submit.disabled = false;
      submit.innerHTML = 'Save Changes';
      submit.classList.add('btn-primary');
      submit.classList.remove('btn-warning');
    }
  });
}

// ─── Entity Builder ───────────────────────────────────────────────────────────

const { Render: OrderItems, Init: initOrderItems } = createEntityModule({
  entityName: 'Order Items',
  entitySubtitle: 'View individual line items within orders',
  apiRoutes: {
    list: API_ROUTES.ORDER_ITEMS.LIST,
    detail: (id) => API_ROUTES.ORDER_ITEMS.GET(id),
    update: (id) => API_ROUTES.ORDER_ITEMS.UPDATE(id),
  },
  fetchList: fetchOrderItems,
  fetchSingle: fetchOrderItem,
  hideCreateBtn: true,
  tableHeaderHtml: `<tr class="tr">
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">ID</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Product / Order</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Customer</th>
        <th class="px-8 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Qty</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Unit Price</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Total</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Status</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Created</th>
        <th class="px-8 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Actions</th>
    </tr>`,
  renderRow,
  renderViewModal,
  renderFormModal,
  initFormHandlersOverride,
  searchPlaceholder: 'Search by product, order # or user…',
  createBtnText: '',
});

export { OrderItems, initOrderItems };
