/**
 * Carts.js — Modernized Carts domain module.
 * Uses EntityBuilder to eliminate boilerplate.
 */

import { API_ROUTES, buildQueryString } from '../../dashboard.routes.js';
import { apiRequest, escapeHtml, formatDate, getTemplate, closeModal } from '../../utils.js';
import { createEntityModule } from '../../components/EntityBuilder.js';
import { initSearchableSelect } from '../../FormHelpers.js';

async function fetchCarts(limit = 20, offset = 0, query = '') {
  try {
    const url =
      API_ROUTES.CARTS.LIST +
      buildQueryString({ limit, offset, ...(query ? { search: query } : {}) });
    const res = await apiRequest(url);
    if (!res.success) throw new Error(res.message || 'Failed to fetch carts');
    return res.data?.items || (Array.isArray(res.data) ? res.data : []);
  } catch (err) {
    console.error('[Carts] Fetch failed', err);
    return [];
  }
}

async function fetchCart(id) {
  try {
    const res = await apiRequest(API_ROUTES.ADMIN_VIEWS.DETAIL('carts', id));
    if (!res.success) throw new Error(res.message || 'Failed to fetch cart details');
    return res.data;
  } catch (err) {
    throw err;
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

function renderRow(c) {
  const statusBadge = `<span class="badge ${getStatusClass(c.status)}">${escapeHtml(c.status || 'active')}</span>`;
  const sessionId = (c.session_id || '').substring(0, 12);

  return `<tr class="group hover:bg-gray-50/50 transition-colors">
        <td class="px-6 py-4 text-[10px] font-bold text-gray-300 font-mono whitespace-nowrap">#${escapeHtml(String(c.id))}</td>
        <td class="px-6 py-4">
            <div class="font-bold text-black" style="font-size:13px;">${escapeHtml(sessionId)}...</div>
            <div class="text-slate-500" style="font-size:11px;">${c.item_count || 0} items</div>
        </td>
        <td class="px-6 py-4">${statusBadge}</td>
        <td class="px-6 py-4 font-bold font-mono text-black" style="font-size:13px;">Rs ${formatCurrency(c.total_cents || 0)}</td>
        <td class="px-6 py-4">
            <div class="font-semibold text-black" style="font-size:12px;">${escapeHtml(c.user_name || 'Anonymous Guest')}</div>
            <div class="text-slate-500" style="font-size:11px;">${escapeHtml(c.user_email || '')}</div>
        </td>
        <td class="px-6 py-4 text-slate-500 text-[10px] whitespace-nowrap">${c.created_at ? formatDate(c.created_at) : '—'}</td>
        <td class="px-6 py-4 text-right">
            <div class="flex items-center justify-end gap-2">
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-view" data-id="${c.id}"><span class="text-[10px]">👁️</span></button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-edit" data-id="${c.id}"><span class="text-[10px]">✏️</span></button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-red-500 hover:bg-red-500 hover:text-white transition-all js-delete" data-id="${c.id}"><span class="text-[10px]">🗑️</span></button>
            </div>
        </td>
    </tr>`;
}

// ─── View Modal ───────────────────────────────────────────────────────────────

function renderViewModal(c) {
  const items = c.items || [];
  const itemsRows = items
    .map(
      (it) => `
        <tr class="tr">
            <td class="td"><div class="font-bold text-black" style="font-size:13px;">${escapeHtml(it.product_name)}</div></td>
            <td class="td text-center font-mono" style="font-size:12px;">x${it.quantity}</td>
            <td class="td text-right font-mono text-slate-500" style="font-size:12px;">Rs ${formatCurrency(it.price_at_add_cents || 0)}</td>
        </tr>
    `
    )
    .join('');

  return `
        <div class="flex flex-col" style="gap:20px; padding:8px;">
            <div class="flex items-center justify-between" style="padding-bottom:16px;border-bottom:1px solid var(--slate-100);">
                <div>
                     <h3 class="font-bold text-black" style="font-size:22px;letter-spacing:-0.02em;">Cart #${c.id}</h3>
                     <p class="text-sm text-slate-500">Session: <span class="font-mono">${escapeHtml(c.session_id || '—')}</span></p>
                </div>
                <div class="flex flex-col items-end" style="gap:6px;">
                    <span class="badge ${getStatusClass(c.status)} uppercase" style="font-size:11px;padding:4px 12px;">${c.status || 'ACTIVE'}</span>
                    <span class="text-slate-400" style="font-size:10px;">Created: ${formatDate(c.created_at)}</span>
                </div>
            </div>

            <div class="flex" style="gap:20px;">
                <div style="flex:1.5;display:flex;flex-direction:column;gap:16px;">
                    <div>
                         <h4 class="text-slate-400 font-bold uppercase" style="font-size:11px;letter-spacing:0.05em;margin-bottom:8px;">Cart Items (${items.length})</h4>
                         <div class="table-container">
                            <table class="table">
                                <thead><tr class="tr"><th class="th">Product</th><th class="th text-center">Qty</th><th class="th text-right">Price @ Entry</th></tr></thead>
                                <tbody>${itemsRows || `<tr class="tr"><td colspan="3" class="td text-center text-slate-400" style="padding:24px;">Cart is empty</td></tr>`}</tbody>
                            </table>
                            <div style="background:var(--slate-50);padding:16px;display:flex;flex-direction:column;align-items:flex-end;">
                                <div class="flex justify-between" style="width:200px;font-size:14px;font-weight:900;color:var(--black);"><span>Total</span><span class="font-mono text-lg">Rs ${formatCurrency(c.total_cents || 0)}</span></div>
                            </div>
                         </div>
                    </div>
                </div>
                <div style="flex:1;display:flex;flex-direction:column;gap:16px;">
                    <div class="google-card" style="padding:16px;background:var(--slate-50);">
                         <div class="text-slate-400 font-bold uppercase" style="font-size:9px;letter-spacing:0.1em;margin-bottom:12px;">Cart Owner</div>
                         <div class="flex items-center" style="gap:12px;">
                             <div class="thumb-md rounded-full bg-white border flex items-center justify-center text-xl shadow-sm">👤</div>
                             <div>
                                <div class="font-bold text-black" style="font-size:14px;">${escapeHtml(c.user_name || 'Anonymous Guest')}</div>
                                <div class="text-xs text-slate-500">${escapeHtml(c.user_email || '—')}</div>
                             </div>
                         </div>
                    </div>
                    <div class="google-card" style="padding:16px;">
                        <h4 class="text-slate-400 font-bold uppercase" style="font-size:10px;letter-spacing:0.1em;margin-bottom:12px;border-bottom:1px solid var(--slate-100);padding-bottom:4px;">Metadata</h4>
                        <div class="flex flex-col" style="gap:6px;">
                            <div class="flex justify-between" style="font-size:12px;"><span class="text-slate-500">Converted</span><span>${c.converted_at ? formatDate(c.converted_at) : 'Not converted'}</span></div>
                            <div class="flex justify-between" style="font-size:12px;"><span class="text-slate-500">Last Updated</span><span>${formatDate(c.updated_at)}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end" style="padding-top:12px;border-top:1px solid var(--slate-100);gap:8px;">
                <button class="btn btn-primary js-edit" data-id="${c.id}" style="padding:0 32px;">✏️ Edit Cart</button>
            </div>
        </div>`;
}

// ─── Form Builder ─────────────────────────────────────────────────────────────

async function renderFormModal(id = null) {
  const isEdit = id !== null;
  const c = isEdit ? await fetchCart(id) : {};

  const frag = getTemplate('tpl-cart-form', {
    id: isEdit ? id : '',
    user_name: escapeHtml(c.user_name || ''),
    user_email: escapeHtml(c.user_email || ''),
    session_id: c.session_id || `sess_${Math.random().toString(36).substr(2, 9)}`,
    total: formatCurrency(c.total_cents || 0),
    item_count: c.item_count || 0,
    created_at: c.created_at ? formatDate(c.created_at) : 'N/A',
    updated_at: c.updated_at ? formatDate(c.updated_at) : 'N/A',
    create_only: isEdit ? 'hidden' : '',
    edit_only: isEdit ? '' : 'hidden',
    delete_display: isEdit ? '' : 'hidden',
    submit_text: isEdit ? 'Save Changes' : 'Create Cart',
  });

  if (isEdit) {
    const select = frag.querySelector('#crt-status-select');
    if (select) select.value = c.status || 'active';
  }

  if (isEdit) {
    const footer = frag.querySelector('.flex.justify-end.gap-3.pt-6');
    if (footer) {
      const del = document.createElement('button');
      del.type = 'button';
      del.className = 'btn btn-outline text-danger mr-auto js-delete-btn';
      del.innerHTML = '🗑️ Delete Cart';
      footer.prepend(del);
    }
  }

  return frag;
}

// ─── Custom Form Handlers ─────────────────────────────────────────────────────

function initFormHandlersOverride(modalRoot, id, onSuccess, closeModalFn, showFormErrorFn) {
  const isEdit = id !== null;
  const form = modalRoot.querySelector('#crt-form');
  if (!form) return;

  const cancel = modalRoot.querySelector('#crt-cancel');
  if (cancel) cancel.addEventListener('click', closeModalFn);

  if (!isEdit) {
    const userSearchWrapper = modalRoot.querySelector('#crt-user-search-wrapper');
    if (userSearchWrapper) {
      initSearchableSelect(userSearchWrapper, {
        placeholder: 'Search user by name/email...',
        name: 'user_id',
        searchUrlBuilder: (q) =>
          `${API_ROUTES.USERS.LIST}${buildQueryString({ search: q, limit: 12 })}`,
        itemRenderer: (u) =>
          `<div class="p-2 hover:bg-slate-50 cursor-pointer flex justify-between items-center"><span class="font-bold text-black">${escapeHtml(u.name || u.username)}</span><span class="text-[10px] text-slate-400 font-mono">${escapeHtml(u.email)}</span></div>`,
      });
    }
  }

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
            delBtn.innerHTML = '🗑️ Delete Cart';
            delBtn.classList.remove('btn-warning');
          }
        }, 3000);
        return;
      }
      delBtn.disabled = true;
      delBtn.innerHTML = 'Deleting…';
      try {
        await apiRequest(API_ROUTES.CARTS.DELETE(id), { method: 'DELETE' });
        closeModalFn();
        onSuccess?.(null, 'deleted');
      } catch (err) {
        const msg = err.message.includes('foreign key')
          ? 'Cart is linked to an Order and cannot be deleted.'
          : err.message;
        showFormErrorFn(form, msg);
        delBtn.disabled = false;
        delBtn.innerHTML = '🗑️ Delete Cart';
      }
    });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submit = form.querySelector('button[type="submit"]');
    const orig = submit.innerHTML;
    submit.disabled = true;
    submit.innerHTML = isEdit ? 'Saving…' : 'Creating…';
    try {
      const formData = new FormData(form);
      const payload = isEdit
        ? { status: formData.get('status') }
        : { user_id: parseInt(formData.get('user_id')), status: 'active' };
      const url = isEdit ? API_ROUTES.CARTS.UPDATE(id) : API_ROUTES.CARTS.CREATE;
      const res = await apiRequest(url, { method: isEdit ? 'PUT' : 'POST', body: payload });
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

const { Render: Carts, Init: initCarts } = createEntityModule({
  entityName: 'Shopping Carts',
  entitySubtitle: 'Monitor active and abandoned customer shopping carts',
  apiRoutes: {
    list: API_ROUTES.CARTS.LIST,
    detail: (id) => API_ROUTES.ADMIN_VIEWS.DETAIL('carts', id),
    create: API_ROUTES.CARTS.CREATE,
    update: (id) => API_ROUTES.CARTS.UPDATE(id),
    delete: (id) => API_ROUTES.CARTS.DELETE(id),
  },
  fetchList: fetchCarts,
  fetchSingle: fetchCart,
  tableHeaderHtml: `<tr class="tr">
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">ID</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Session</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Status</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Total</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Owner</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Created</th>
        <th class="px-8 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Actions</th>
    </tr>`,
  renderRow,
  renderViewModal,
  renderFormModal,
  initFormHandlersOverride,
  searchPlaceholder: 'Search by user name or email…',
  createBtnText: '➕ New Cart',
});

export { Carts, initCarts };
