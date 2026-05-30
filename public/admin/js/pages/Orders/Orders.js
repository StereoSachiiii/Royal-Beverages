/**
 * Orders.js — Modernized Orders domain module.
 * Uses dashboard-tailwind.css classes throughout.
 * Uses EntityBuilder to eliminate boilerplate.
 */

import { API_ROUTES, buildQueryString } from '../../dashboard.routes.js';
import { apiRequest, escapeHtml, formatDate, getTemplate, closeModal } from '../../utils.js';
import { createEntityModule } from '../../components/EntityBuilder.js';

async function fetchOrders(limit = 20, offset = 0, query = '') {
  try {
    const url =
      API_ROUTES.ORDERS.LIST +
      buildQueryString({
        limit,
        offset,
        ...(query ? { search: query } : {}),
      });
    const res = await apiRequest(url);
    if (!res.success) throw new Error(res.message || 'Failed to fetch orders');
    return res.data?.items || (Array.isArray(res.data) ? res.data : []);
  } catch (err) {
    console.error('[Orders] Fetch failed', err);
    return [];
  }
}

async function fetchOrder(id) {
  try {
    const url = API_ROUTES.ADMIN_VIEWS.DETAIL('orders', id);
    const res = await apiRequest(url);
    if (!res.success) throw new Error(res.message || 'Failed to fetch order details');
    return res.data;
  } catch (err) {
    throw err;
  }
}

async function fetchDependencies() {
  try {
    const [uRes, cRes] = await Promise.all([
      apiRequest(API_ROUTES.USERS.LIST + '?limit=200'),
      apiRequest(API_ROUTES.CARTS.LIST + '?limit=100'),
    ]);
    return {
      users: uRes.data?.items || uRes.data || [],
      carts: cRes.data?.items || cRes.data || [],
    };
  } catch (err) {
    console.error('[Orders] Dependency fetch failed', err);
    return { users: [], carts: [] };
  }
}

async function fetchUserAddresses(userId) {
  if (!userId) return [];
  try {
    const url = API_ROUTES.USER_ADDRESSES.BY_USER(userId);
    const res = await apiRequest(url);
    return res.success ? res.data || [] : [];
  } catch (err) {
    console.error('[Orders] Address fetch failed', err);
    return [];
  }
}

// ─── Utils ───────────────────────────────────────────────────────────────────

function getStatusClass(status) {
  const s = (status || 'pending').toLowerCase();
  switch (s) {
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

function renderRow(o) {
  const statusBadge = `<span class="badge ${getStatusClass(o.status)}">${escapeHtml(o.status || 'pending')}</span>`;
  const created = o.created_at ? formatDate(o.created_at) : '—';
  const orderNum = o.order_number || `#${o.id}`;

  return `<tr class="tr group hover:bg-gray-50/50 transition-colors">
        <td class="px-8 py-5 text-[10px] font-bold text-gray-300 font-mono whitespace-nowrap">#${escapeHtml(String(o.id))}</td>
        <td class="px-8 py-5">
            <div class="font-bold text-black" style="font-size:13px;">${escapeHtml(orderNum)}</div>
            <div class="text-slate-500" style="font-size:11px;">${o.item_count || 0} items</div>
        </td>
        <td class="px-8 py-5">${statusBadge}</td>
        <td class="px-8 py-5 font-bold font-mono text-black" style="font-size:13px;">Rs ${formatCurrency(o.total_cents || 0)}</td>
        <td class="px-8 py-5">
            <div class="font-semibold text-black" style="font-size:12px;">${escapeHtml(o.user_name || 'Guest User')}</div>
            <div class="text-slate-500" style="font-size:11px;">${escapeHtml(o.user_email || '')}</div>
        </td>
        <td class="px-8 py-5 text-slate-500" style="font-size:11px;white-space:nowrap;">${created}</td>
        <td class="px-8 py-5 text-right">
            <div class="flex items-center justify-end gap-2">
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-view" data-id="${o.id}" title="View details">
                    <span class="text-[10px]">👁️</span>
                </button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-edit" data-id="${o.id}" title="Edit Order">
                    <span class="text-[10px]">✏️</span>
                </button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-red-500 hover:bg-red-500 hover:text-white hover:border-red-500 transition-all js-delete" data-id="${o.id}" title="Void Order">
                    <span class="text-[10px]">🗑️</span>
                </button>
            </div>
        </td>
    </tr>`;
}

// ─── View Modal ───────────────────────────────────────────────────────────────

function renderViewModal(o) {
  const items = o.items || [];
  const shipping = o.shipping_address || {};

  const itemsRows = items
    .map(
      (it) => `
        <tr class="tr">
            <td class="td">
                 <div class="font-bold text-black" style="font-size:13px;">${escapeHtml(it.product_name)}</div>
            </td>
            <td class="td text-center font-mono" style="font-size:12px;">x${it.quantity}</td>
            <td class="td text-right font-mono text-slate-500" style="font-size:12px;">Rs ${formatCurrency(it.price_cents)}</td>
            <td class="td text-right font-mono font-bold text-black" style="font-size:12px;">Rs ${formatCurrency(it.price_cents * it.quantity)}</td>
        </tr>
    `
    )
    .join('');

  return `
        <div class="flex flex-col" style="gap:20px; padding: 8px;">
            <div class="flex items-center justify-between" style="padding-bottom:16px;border-bottom:1px solid var(--slate-100);">
                <div>
                     <h3 class="font-bold text-black" style="font-size:22px;letter-spacing:-0.02em;">Order ${escapeHtml(o.order_number || `#${o.id}`)}</h3>
                     <p class="text-sm text-slate-500">Recorded on ${formatDate(o.created_at)}</p>
                </div>
                <div class="flex flex-col items-end" style="gap:6px;">
                    <span class="badge ${getStatusClass(o.status)} uppercase" style="font-size:11px;padding:4px 12px;">${o.status || 'PENDING'}</span>
                    ${o.paid_at ? `<span class="text-success font-bold" style="font-size:10px;text-transform:uppercase;">✓ Paid ${formatDate(o.paid_at)}</span>` : '<span class="text-warning font-black" style="font-size:10px;text-transform:uppercase;letter-spacing:0.05em;">⚠ Awaiting Payment</span>'}
                </div>
            </div>

            <div class="flex" style="gap:20px;">
                <div style="flex:2;display:flex;flex-direction:column;gap:16px;">
                    <div class="google-card flex items-center" style="padding:16px;gap:14px;background:var(--slate-50);">
                         <div class="thumb-md rounded-full bg-white border flex items-center justify-center text-xl shadow-sm">👤</div>
                         <div>
                            <div class="text-slate-400 font-bold uppercase" style="font-size:9px;letter-spacing:0.1em;">Customer Details</div>
                            <div class="font-bold text-black" style="font-size:14px;">${escapeHtml(o.user_name || 'Guest User')}</div>
                            <div class="text-xs text-slate-500">${escapeHtml(o.user_email || 'no-email@provided')}</div>
                         </div>
                    </div>

                    <div>
                         <h4 class="text-slate-400 font-bold uppercase" style="font-size:11px;letter-spacing:0.05em;margin-bottom:8px;">Manifest (${items.length} items)</h4>
                         <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr class="tr">
                                        <th class="th">Product</th>
                                        <th class="th text-center">Qty</th>
                                        <th class="th text-right">Unit</th>
                                        <th class="th text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${itemsRows || `<tr class="tr"><td colspan="4" class="td text-center text-slate-400" style="padding:24px;">No items found</td></tr>`}
                                </tbody>
                            </table>
                            <div style="background:var(--slate-50);padding:16px;display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
                                <div class="flex justify-between" style="width:200px;font-size:12px;"><span class="text-slate-500">Subtotal</span><span class="font-mono">Rs ${formatCurrency(o.total_cents)}</span></div>
                                <div class="flex justify-between" style="width:200px;font-size:14px;font-weight:900;border-top:1px solid var(--slate-200);margin-top:4px;padding-top:4px;color:var(--black);"><span class="uppercase">Order Total</span><span class="font-mono text-lg">Rs ${formatCurrency(o.total_cents)}</span></div>
                            </div>
                         </div>
                    </div>
                </div>

                <div style="flex:1;display:flex;flex-direction:column;gap:16px;">
                    <div class="google-card" style="padding:16px;">
                        <h4 class="text-slate-400 font-bold uppercase" style="font-size:11px;letter-spacing:0.05em;margin-bottom:12px;border-bottom:1px solid var(--slate-100);padding-bottom:4px;">Logistics</h4>
                        ${
                          shipping.address_line1
                            ? `
                            <div class="flex flex-col" style="gap:8px;">
                                <div>
                                    <div class="text-slate-400 font-bold uppercase" style="font-size:9px;letter-spacing:0.05em;">Recipient</div>
                                    <div class="font-bold text-black" style="font-size:13px;">${escapeHtml(shipping.recipient_name)}</div>
                                </div>
                                <div style="margin-top:4px;">
                                    <div class="text-slate-400 font-bold uppercase" style="font-size:9px;letter-spacing:0.05em;">Destination</div>
                                    <div class="text-black" style="font-size:12px;line-height:1.4;">
                                        ${escapeHtml(shipping.address_line1)}<br>
                                        ${shipping.address_line2 ? `${escapeHtml(shipping.address_line2)}<br>` : ''}
                                        <span class="font-semibold">${escapeHtml(shipping.city)}, ${escapeHtml(shipping.postal_code)}</span><br>
                                        <span class="uppercase font-bold" style="font-size:10px;">${escapeHtml(shipping.country)}</span>
                                    </div>
                                </div>
                            </div>
                        `
                            : '<div class="text-slate-400 italic" style="font-size:11px;padding:10px 0;">No logistics data (Self-Pickup or Guest)</div>'
                        }
                    </div>

                    <div style="padding:14px;background:#fffbeb;border:1px solid #fde68a;border-radius:12px;">
                         <h4 class="text-amber-500 font-bold uppercase" style="font-size:10px;letter-spacing:0.05em;margin-bottom:6px;">Internal Narrative</h4>
                         <p class="text-amber-800 italic" style="font-size:11px;line-height:1.5;">${escapeHtml(o.notes || 'No internal annotations for this session.')}</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end" style="padding-top:12px;border-top:1px solid var(--slate-100);gap:8px;">
                <button class="btn btn-primary js-edit" data-id="${o.id}" style="padding:0 32px;">✏️ Modify Order</button>
            </div>
        </div>`;
}

// ─── Form Builder ─────────────────────────────────────────────────────────────

async function renderFormModal(id) {
  const isEdit = id !== null;
  const [deps, o] = await Promise.all([
    fetchDependencies(),
    isEdit ? fetchOrder(id) : Promise.resolve({}),
  ]);

  let addresses = [];
  if (o.user_id) {
    addresses = await fetchUserAddresses(o.user_id);
  }

  const frag = getTemplate('tpl-order-form', {
    id: isEdit ? id : '',
    order_number: escapeHtml(o.order_number || ''),
    total_cents: o.total_cents || '',
    notes: escapeHtml(o.notes || ''),
    created_at: o.created_at ? formatDate(o.created_at) : 'N/A',
    paid_at: o.paid_at ? formatDate(o.paid_at) : 'Not paid',
    create_only: isEdit ? 'hidden' : '',
    edit_only: isEdit ? '' : 'hidden',
    cart_required: isEdit ? '' : 'required',
    delete_display: isEdit ? '' : 'hidden',
    submit_text: isEdit ? 'Save Protocol' : 'Execute Order',
  });

  const uSel = frag.querySelector('#ord-user-select');
  if (uSel) {
    uSel.innerHTML =
      '<option value="">Select Customer (Required)</option>' +
      deps.users
        .map(
          (u) =>
            `<option value="${u.id}" ${o.user_id == u.id ? 'selected' : ''}>${escapeHtml(u.name || u.username)} (${escapeHtml(u.email)})</option>`
        )
        .join('');
  }

  if (!isEdit) {
    const cSel = frag.querySelector('#ord-cart-select');
    if (cSel) {
      cSel.innerHTML =
        '<option value="">Synthesize from Active Cart...</option>' +
        deps.carts
          .map(
            (c) =>
              `<option value="${c.id}">Cart #${c.id} • User: ${escapeHtml(c.user_name || c.user_id)}</option>`
          )
          .join('');
    }
  }

  if (isEdit) {
    const statusSel = frag.querySelector('#ord-status-select');
    if (statusSel) statusSel.value = o.status || 'pending';
  }

  const sSel = frag.querySelector('#ord-shipping-select');
  const bSel = frag.querySelector('#ord-billing-select');
  const addrOptions = addresses
    .map(
      (a) =>
        `<option value="${a.id}">${escapeHtml(a.recipient_name || 'Address')} - ${escapeHtml(a.address_line1)}, ${escapeHtml(a.city)} (${escapeHtml(a.address_type)})</option>`
    )
    .join('');

  if (sSel) {
    sSel.innerHTML += addrOptions;
    if (isEdit && o.shipping_address_id) sSel.value = o.shipping_address_id;
  }
  if (bSel) {
    bSel.innerHTML += addrOptions;
    if (isEdit && o.billing_address_id) bSel.value = o.billing_address_id;
  }

  if (isEdit) {
    const footer = frag.querySelector('.flex.justify-end.gap-3.pt-6');
    if (footer) {
      const del = document.createElement('button');
      del.type = 'button';
      del.className = 'btn btn-outline text-danger mr-auto js-delete-btn';
      del.innerHTML = '🗑️ Void Order';
      footer.prepend(del);
    }
  }

  return frag;
}

// ─── Custom Form Handlers ─────────────────────────────────────────────────────

function initFormHandlersOverride(modalRoot, id, onSuccess, closeModalFn, showFormErrorFn) {
  const isEdit = id !== null;
  const form = modalRoot.querySelector('#ord-form');
  if (!form) return;

  const uSel = form.querySelector('#ord-user-select');
  const sSel = form.querySelector('#ord-shipping-select');
  const bSel = form.querySelector('#ord-billing-select');

  if (uSel) {
    uSel.addEventListener('change', async () => {
      const uid = uSel.value;
      if (!uid) {
        if (sSel) sSel.innerHTML = '<option value="">Select shipping address...</option>';
        if (bSel) bSel.innerHTML = '<option value="">Select billing address...</option>';
        return;
      }
      if (sSel) {
        sSel.disabled = true;
        sSel.innerHTML = '<option>Loading addresses...</option>';
      }
      if (bSel) {
        bSel.disabled = true;
        bSel.innerHTML = '<option>Loading addresses...</option>';
      }

      const addrs = await fetchUserAddresses(uid);
      const options = addrs
        .map(
          (a) =>
            `<option value="${a.id}">${escapeHtml(a.recipient_name || 'Address')} - ${escapeHtml(a.address_line1)}, ${escapeHtml(a.city)} (${escapeHtml(a.address_type)})</option>`
        )
        .join('');

      if (sSel) {
        sSel.disabled = false;
        sSel.innerHTML = '<option value="">Select shipping address...</option>' + options;
        const ship = addrs.find((a) => a.address_type === 'shipping' || a.address_type === 'both');
        if (ship) sSel.value = ship.id;
      }
      if (bSel) {
        bSel.disabled = false;
        bSel.innerHTML = '<option value="">Select billing address...</option>' + options;
        const bill = addrs.find((a) => a.address_type === 'billing' || a.address_type === 'both');
        if (bill) bSel.value = bill.id;
      }
    });
  }

  const cancel = modalRoot.querySelector('#ord-cancel');
  if (cancel) cancel.addEventListener('click', closeModalFn);

  const delBtn = modalRoot.querySelector('.js-delete-btn');
  if (delBtn) {
    delBtn.addEventListener('click', async () => {
      if (!delBtn.dataset.confirmed) {
        delBtn.dataset.confirmed = '1';
        delBtn.innerHTML = '⚠️ Confirm Void?';
        delBtn.classList.add('btn-warning');
        setTimeout(() => {
          if (delBtn.isConnected) {
            delete delBtn.dataset.confirmed;
            delBtn.innerHTML = '🗑️ Void Order';
            delBtn.classList.remove('btn-warning');
          }
        }, 3000);
        return;
      }
      delBtn.disabled = true;
      delBtn.innerHTML = 'Voiding…';
      try {
        await apiRequest(API_ROUTES.ORDERS.DELETE(id), { method: 'DELETE' });
        closeModalFn();
        onSuccess?.(null, 'deleted');
      } catch (err) {
        showFormErrorFn(form, err.message);
        delBtn.disabled = false;
        delBtn.innerHTML = '🗑️ Void Order';
      }
    });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submit = form.querySelector('button[type="submit"]');
    const orig = submit.innerHTML;
    submit.disabled = true;
    submit.innerHTML = isEdit ? 'Saving…' : 'Creating Order…';
    try {
      const formData = new FormData(form);
      const payload = {
        user_id: parseInt(formData.get('user_id')),
        status: formData.get('status'),
        order_number: formData.get('order_number'),
        total_cents: parseInt(formData.get('total_cents')),
        notes: formData.get('notes') || null,
        shipping_address_id: formData.get('shipping_address_id')
          ? parseInt(formData.get('shipping_address_id'))
          : null,
        billing_address_id: formData.get('billing_address_id')
          ? parseInt(formData.get('billing_address_id'))
          : null,
        ...(isEdit ? {} : { cart_id: parseInt(formData.get('cart_id')) }),
      };
      const url = isEdit ? API_ROUTES.ORDERS.UPDATE(id) : API_ROUTES.ORDERS.CREATE;
      const res = await apiRequest(url, { method: isEdit ? 'PUT' : 'POST', body: payload });
      if (!res.success) throw new Error(res.message);
      closeModalFn();
      onSuccess?.(res.data, isEdit ? 'updated' : 'created');
    } catch (err) {
      showFormErrorFn(form, err.message);
      submit.disabled = false;
      submit.innerHTML = orig;
    }
  });
}

// ─── Entity Builder ───────────────────────────────────────────────────────────

const { Render: Orders, Init: initOrders } = createEntityModule({
  entityName: 'Transaction Ledger',
  entitySubtitle: 'Managing purchase history and synthesized orders',
  apiRoutes: {
    list: API_ROUTES.ORDERS.LIST,
    detail: (id) => API_ROUTES.ADMIN_VIEWS.DETAIL('orders', id),
    create: API_ROUTES.ORDERS.CREATE,
    update: (id) => API_ROUTES.ORDERS.UPDATE(id),
    delete: (id) => API_ROUTES.ORDERS.DELETE(id),
  },
  fetchList: fetchOrders,
  fetchSingle: fetchOrder,
  tableHeaderHtml: `<tr>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">ID</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Reference/Items</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Status</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Total</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Customer Profile</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Recorded At</th>
        <th class="px-8 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Actions</th>
    </tr>`,
  renderRow,
  renderViewModal,
  renderFormModal,
  initFormHandlersOverride,
  searchPlaceholder: 'Search by Order # or User…',
  createBtnText: '➕ New Order',
});

export { Orders, initOrders };
