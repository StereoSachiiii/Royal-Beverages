/**
 * CartItems.js — Cart Items domain module.
 * Uses EntityBuilder to eliminate boilerplate.
 */

import { API_ROUTES, buildQueryString } from '../../dashboard.routes.js';
import {
  getImageUrl,
  apiRequest,
  escapeHtml,
  formatDate,
  getTemplate,
  closeModal,
} from '../../utils.js';
import { createEntityModule } from '../../components/EntityBuilder.js';

async function fetchCartItems(limit = 20, offset = 0, query = '') {
  try {
    const url =
      API_ROUTES.CART_ITEMS.LIST +
      buildQueryString({ limit, offset, ...(query ? { search: query } : {}) });
    const res = await apiRequest(url);
    if (!res.success) throw new Error(res.message || 'Failed to fetch cart items');
    return res.data?.items || (Array.isArray(res.data) ? res.data : []);
  } catch (err) {
    console.error('[CartItems] Fetch failed', err);
    return [];
  }
}

async function fetchCartItem(id) {
  try {
    const res = await apiRequest(API_ROUTES.CART_ITEMS.GET(id));
    if (!res.success) throw new Error(res.message || 'Failed to fetch cart item');
    return res.data;
  } catch (err) {
    throw err;
  }
}

async function fetchDependencies() {
  try {
    const [cRes, pRes] = await Promise.all([
      apiRequest(API_ROUTES.CARTS.LIST + '?limit=200'),
      apiRequest(API_ROUTES.PRODUCTS.LIST + '?limit=200'),
    ]);
    return {
      carts: cRes.data?.items || cRes.data || [],
      products: pRes.data?.items || pRes.data || [],
    };
  } catch (err) {
    return { carts: [], products: [] };
  }
}

function getStatusClass(status) {
  switch ((status || 'active').toLowerCase()) {
    case 'active':
      return 'badge-active';
    case 'converted':
      return 'badge-info';
    case 'abandoned':
      return 'badge-warning';
    case 'expired':
      return 'badge-inactive';
    default:
      return 'badge-secondary';
  }
}

function formatCurrency(cents) {
  return (cents / 100).toFixed(2);
}

// ─── Row Renderer ─────────────────────────────────────────────────────────────

function renderRow(it) {
  const subtotal = (it.price_at_add_cents || 0) * (it.quantity || 0);
  return `<tr class="group hover:bg-gray-50/50 transition-colors">
        <td class="px-6 py-4 text-[10px] font-bold text-gray-300 font-mono whitespace-nowrap">#${escapeHtml(String(it.id))}</td>
        <td class="px-6 py-4">
            <div class="font-bold text-black" style="font-size:13px;">${escapeHtml(it.product_name || 'Unknown')}</div>
            <div class="text-slate-500 font-mono" style="font-size:11px;">Cart #${it.cart_id}</div>
        </td>
        <td class="px-6 py-4">
            <div class="font-semibold text-black" style="font-size:12px;">${escapeHtml(it.user_name || 'Guest')}</div>
        </td>
        <td class="px-6 py-4 text-center font-mono font-bold" style="font-size:13px;">x${it.quantity || 0}</td>
        <td class="px-6 py-4 font-mono text-slate-500" style="font-size:12px;">Rs ${formatCurrency(it.price_at_add_cents || 0)}</td>
        <td class="px-6 py-4 font-bold font-mono text-black" style="font-size:12px;">Rs ${formatCurrency(subtotal)}</td>
        <td class="px-6 py-4 text-slate-500" style="font-size:11px;white-space:nowrap;">${it.created_at ? formatDate(it.created_at) : '—'}</td>
        <td class="px-6 py-4 text-right">
            <div class="flex items-center justify-end gap-2">
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-view" data-id="${it.id}"><span class="text-[10px]">👁️</span></button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-edit" data-id="${it.id}"><span class="text-[10px]">✏️</span></button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-red-500 hover:bg-red-500 hover:text-white transition-all js-delete" data-id="${it.id}"><span class="text-[10px]">🗑️</span></button>
            </div>
        </td>
    </tr>`;
}

// ─── View Modal ───────────────────────────────────────────────────────────────

function renderViewModal(it) {
  const priceDiff = (it.current_price_cents || 0) - (it.price_at_add_cents || 0);
  return `
        <div class="flex flex-col" style="gap:20px; padding:8px;">
            <div class="flex items-center justify-between" style="padding-bottom:16px;border-bottom:1px solid var(--slate-100);">
                <div>
                     <h3 class="font-bold text-black" style="font-size:20px;letter-spacing:-0.02em;">Cart Item #${it.id}</h3>
                     <p class="text-sm text-slate-500">Added ${formatDate(it.created_at)} · Cart #${it.cart_id}</p>
                </div>
                <span class="badge ${getStatusClass(it.cart_status)} uppercase" style="font-size:10px;padding:4px 10px;">Cart: ${it.cart_status || 'UNKNOWN'}</span>
            </div>

            <div class="flex" style="gap:20px;">
                <div style="flex:1.2;display:flex;flex-direction:column;gap:16px;">
                    <div class="google-card" style="padding:20px;background:var(--slate-50);">
                         <div class="text-slate-400 font-bold uppercase" style="font-size:9px;letter-spacing:0.1em;margin-bottom:12px;">Product</div>
                         <div class="flex items-center" style="gap:16px;">
                             ${it.product_image ? `<img src="${escapeHtml(getImageUrl(it.product_image))}" class="thumb-xl rounded-2xl border shadow-sm bg-white" alt="${escapeHtml(it.product_name)}">` : `<div class="thumb-xl rounded-2xl bg-white border flex items-center justify-center text-3xl shadow-sm">📦</div>`}
                             <div>
                                <div class="font-bold text-black" style="font-size:18px;line-height:1.2;">${escapeHtml(it.product_name)}</div>
                                <div class="text-xs text-slate-500 font-mono mt-1">ID: #${it.product_id}</div>
                             </div>
                         </div>
                    </div>
                    <div class="google-card" style="padding:16px;">
                         <div class="text-slate-400 font-bold uppercase" style="font-size:9px;letter-spacing:0.1em;margin-bottom:10px;">Customer</div>
                         <div class="font-bold text-black" style="font-size:14px;">${escapeHtml(it.user_name || 'Guest')}</div>
                         <div class="text-xs text-slate-500">${escapeHtml(it.user_email || '—')}</div>
                    </div>
                </div>

                <div style="flex:1;display:flex;flex-direction:column;gap:16px;">
                    <div style="background:var(--black);color:white;padding:24px;border-radius:16px;">
                        <div class="text-slate-500 font-bold uppercase text-center" style="font-size:9px;letter-spacing:0.1em;margin-bottom:16px;">Pricing Summary</div>
                        <div class="flex justify-between" style="font-size:12px;margin-bottom:10px;"><span class="text-slate-400">Price at Add</span><span class="font-mono">Rs ${formatCurrency(it.price_at_add_cents)}</span></div>
                        <div class="flex justify-between" style="font-size:12px;margin-bottom:10px;"><span class="text-slate-400">Current Price</span><span class="font-mono">Rs ${formatCurrency(it.current_price_cents)}</span></div>
                        <div class="flex justify-between" style="font-size:12px;border-top:1px solid #334155;padding-top:10px;margin-bottom:16px;">
                            <span class="text-slate-400 font-black uppercase" style="font-size:10px;">Price Delta</span>
                            <span class="font-mono font-bold ${priceDiff > 0 ? 'text-green-400' : priceDiff < 0 ? 'text-red-400' : 'text-slate-500'}">
                                ${priceDiff > 0 ? '+' : ''}Rs ${formatCurrency(priceDiff)}
                            </span>
                        </div>
                        <div class="flex justify-between items-baseline pt-2 border-t border-slate-800">
                             <span class="text-indigo-400 font-black uppercase" style="font-size:11px;">Total</span>
                             <span class="font-mono font-black" style="font-size:26px;">Rs ${formatCurrency(it.price_at_add_cents * it.quantity)}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end" style="padding-top:12px;border-top:1px solid var(--slate-100);gap:8px;">
                <button class="btn btn-primary js-edit" data-id="${it.id}" style="padding:0 32px;">✏️ Edit Item</button>
            </div>
        </div>`;
}

// ─── Form Builder ─────────────────────────────────────────────────────────────

async function renderFormModal(id = null) {
  const isEdit = id !== null;
  const [deps, it] = await Promise.all([
    isEdit ? Promise.resolve({ carts: [], products: [] }) : fetchDependencies(),
    isEdit ? fetchCartItem(id) : Promise.resolve({}),
  ]);

  const frag = getTemplate('tpl-cart-item-form', {
    id: isEdit ? id : '',
    cart_id: it.cart_id || '',
    product_name: escapeHtml(it.product_name || ''),
    quantity: it.quantity || 1,
    unit_price: it.price_at_add_cents ? formatCurrency(it.price_at_add_cents) : '0.00',
    subtotal: it.price_at_add_cents ? formatCurrency(it.price_at_add_cents * it.quantity) : '0.00',
    create_only: isEdit ? 'hidden' : '',
    edit_only: isEdit ? '' : 'hidden',
    create_required: isEdit ? '' : 'required',
  });

  if (!isEdit) {
    const cSel = frag.querySelector('#cit-cart-select');
    const pSel = frag.querySelector('#cit-product-select');
    if (cSel)
      cSel.innerHTML =
        '<option value="">Select Cart...</option>' +
        deps.carts
          .map(
            (c) =>
              `<option value="${c.id}">Cart #${c.id} (${escapeHtml(c.user_name || 'Guest')})</option>`
          )
          .join('');
    if (pSel)
      pSel.innerHTML =
        '<option value="">Select Product...</option>' +
        deps.products
          .map(
            (p) =>
              `<option value="${p.id}">${escapeHtml(p.name)} (Rs ${formatCurrency(p.price_cents)})</option>`
          )
          .join('');
  }

  if (isEdit) {
    const footer = frag.querySelector('.flex.justify-end.gap-3.pt-6');
    if (footer) {
      const del = document.createElement('button');
      del.type = 'button';
      del.className = 'btn btn-outline text-danger mr-auto js-delete-btn';
      del.innerHTML = '🗑️ Remove Item';
      footer.prepend(del);
    }
  }
  return frag;
}

// ─── Custom Form Handlers ─────────────────────────────────────────────────────

function initFormHandlersOverride(modalRoot, id, onSuccess, closeModalFn, showFormErrorFn) {
  const isEdit = id !== null;
  const form = modalRoot.querySelector('#cit-form');
  const submit = form?.querySelector('button[type="submit"]');
  if (!form) return;

  const cancel = modalRoot.querySelector('#cit-cancel');
  if (cancel) cancel.addEventListener('click', closeModalFn);

  const delBtn = modalRoot.querySelector('.js-delete-btn');
  if (delBtn) {
    delBtn.addEventListener('click', async () => {
      if (!delBtn.dataset.confirmed) {
        delBtn.dataset.confirmed = '1';
        delBtn.innerHTML = '⚠️ Confirm?';
        delBtn.classList.add('btn-warning');
        setTimeout(() => {
          if (delBtn.isConnected) {
            delete delBtn.dataset.confirmed;
            delBtn.innerHTML = '🗑️ Remove Item';
            delBtn.classList.remove('btn-warning');
          }
        }, 3000);
        return;
      }
      delBtn.disabled = true;
      delBtn.innerHTML = 'Removing…';
      try {
        await apiRequest(API_ROUTES.CART_ITEMS.DELETE(id), { method: 'DELETE' });
        closeModalFn();
        onSuccess?.(null, 'deleted');
      } catch (err) {
        showFormErrorFn(form, err.message);
        delBtn.disabled = false;
        delBtn.innerHTML = '🗑️ Remove Item';
      }
    });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    submit.disabled = true;
    submit.innerHTML = isEdit ? 'Saving…' : 'Adding…';
    try {
      const formData = new FormData(form);
      const payload = {
        quantity: parseInt(formData.get('quantity')),
        ...(isEdit
          ? {}
          : {
              cart_id: parseInt(formData.get('cart_id')),
              product_id: parseInt(formData.get('product_id')),
            }),
      };
      const url = isEdit ? API_ROUTES.CART_ITEMS.UPDATE(id) : API_ROUTES.CART_ITEMS.CREATE;
      const res = await apiRequest(url, { method: isEdit ? 'PUT' : 'POST', body: payload });
      closeModalFn();
      onSuccess?.(res.data, isEdit ? 'updated' : 'created');
    } catch (err) {
      showFormErrorFn(form, err.message);
      submit.disabled = false;
      submit.innerHTML = isEdit ? 'Save Changes' : 'Add Item';
    }
  });
}

// ─── Entity Builder ───────────────────────────────────────────────────────────

const { Render: CartItems, Init: initCartItems } = createEntityModule({
  entityName: 'Cart Items',
  entitySubtitle: 'View individual products inside customer carts',
  apiRoutes: {
    list: API_ROUTES.CART_ITEMS.LIST,
    detail: (id) => API_ROUTES.CART_ITEMS.GET(id),
    create: API_ROUTES.CART_ITEMS.CREATE,
    update: (id) => API_ROUTES.CART_ITEMS.UPDATE(id),
    delete: (id) => API_ROUTES.CART_ITEMS.DELETE(id),
  },
  fetchList: fetchCartItems,
  fetchSingle: fetchCartItem,
  tableHeaderHtml: `<tr class="tr">
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">ID</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Product / Cart</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Customer</th>
        <th class="px-8 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Qty</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Unit Price</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Total</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Added</th>
        <th class="px-8 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Actions</th>
    </tr>`,
  renderRow,
  renderViewModal,
  renderFormModal,
  initFormHandlersOverride,
  searchPlaceholder: 'Search by product or cart ID…',
  createBtnText: '➕ Add Item',
});

export { CartItems, initCartItems };
