/**
 * UserAddresses.js — User Addresses domain module.
 * Uses EntityBuilder to eliminate boilerplate.
 */

import { API_ROUTES, buildQueryString } from '../../dashboard.routes.js';
import { apiRequest, escapeHtml, formatDate, getTemplate, closeModal } from '../../utils.js';
import { createEntityModule } from '../../components/EntityBuilder.js';

async function fetchAddresses(limit = 20, offset = 0, query = '') {
  try {
    const url =
      API_ROUTES.USER_ADDRESSES.LIST +
      buildQueryString({ limit, offset, ...(query ? { search: query } : {}) });
    const res = await apiRequest(url);
    if (!res.success) throw new Error(res.message || 'Failed to fetch addresses');
    return res.data?.items || (Array.isArray(res.data) ? res.data : []);
  } catch (err) {
    console.error('[UserAddresses] Fetch failed', err);
    return [];
  }
}

async function fetchAddress(id) {
  try {
    const res = await apiRequest(API_ROUTES.ADMIN_VIEWS.DETAIL('user_addresses', id));
    if (!res.success) throw new Error(res.message || 'Failed to fetch address');
    return res.data;
  } catch (err) {
    throw err;
  }
}

async function fetchUsers() {
  try {
    const res = await apiRequest(API_ROUTES.USERS.LIST + '?limit=200');
    return res.success ? res.data || [] : [];
  } catch (err) {
    return [];
  }
}

// ─── Row Renderer ─────────────────────────────────────────────────────────────

function renderRow(a) {
  const defaultBadge = a.is_default
    ? `<span class="badge badge-active" style="font-size:10px;">Primary</span>`
    : `<span class="badge badge-inactive" style="font-size:10px;">Secondary</span>`;

  return `<tr class="group hover:bg-gray-50/50 transition-colors">
        <td class="px-6 py-4 text-[10px] font-bold text-gray-300 font-mono whitespace-nowrap">#${escapeHtml(String(a.id))}</td>
        <td class="px-6 py-4">
            <div class="font-bold text-black" style="font-size:13px;">${escapeHtml(a.user_name || 'N/A')}</div>
            <div class="text-slate-500 font-mono" style="font-size:10px;">${escapeHtml(a.user_email || '')}</div>
        </td>
        <td class="px-6 py-4 text-center">
            <span class="badge badge-secondary uppercase" style="font-size:9px;padding:2px 8px;">${escapeHtml(a.address_type || 'both')}</span>
        </td>
        <td class="px-6 py-4">
            <div class="font-bold text-black" style="font-size:12px;">${escapeHtml(a.city || 'N/A')}</div>
            <div class="text-slate-400" style="font-size:10px;">${escapeHtml(a.country || '')}</div>
        </td>
        <td class="px-6 py-4">${defaultBadge}</td>
        <td class="px-6 py-4 text-slate-500 font-mono" style="font-size:11px;">${formatDate(a.created_at)}</td>
        <td class="px-6 py-4 text-right">
            <div class="flex items-center justify-end gap-2">
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-view" data-id="${a.id}"><span class="text-[10px]">👁️</span></button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-edit" data-id="${a.id}"><span class="text-[10px]">✏️</span></button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-red-500 hover:bg-red-500 hover:text-white transition-all js-delete" data-id="${a.id}"><span class="text-[10px]">🗑️</span></button>
            </div>
        </td>
    </tr>`;
}

// ─── View Modal ───────────────────────────────────────────────────────────────

function renderViewModal(a) {
  return `
        <div class="flex flex-col" style="gap:24px; padding:8px;">
            <div class="flex items-center justify-between" style="padding-bottom:16px;border-bottom:1px solid var(--slate-100);">
                <div class="flex items-center" style="gap:16px;">
                    <div class="thumb-lg rounded-2xl bg-slate-50 border flex items-center justify-center text-2xl shadow-inner">📍</div>
                    <div>
                         <h3 class="font-bold text-black" style="font-size:22px;letter-spacing:-0.02em;">Address #${a.id}</h3>
                         <p class="text-sm text-slate-500">Type: <span class="font-bold text-black uppercase">${escapeHtml(a.address_type)}</span></p>
                    </div>
                </div>
                <div class="flex flex-col items-end" style="gap:6px;">
                    ${a.is_default ? '<span class="badge badge-active uppercase font-black tracking-widest" style="font-size:10px;padding:4px 12px;">✓ PRIMARY</span>' : '<span class="badge badge-inactive uppercase font-black tracking-widest" style="font-size:10px;padding:4px 12px;">SECONDARY</span>'}
                    <span class="text-slate-400 font-mono" style="font-size:10px;">Added ${formatDate(a.created_at)}</span>
                </div>
            </div>

            <div class="flex" style="gap:24px;">
                <div style="flex:1.5;display:flex;flex-direction:column;gap:16px;">
                    <div class="google-card" style="padding:24px;background:var(--slate-50);border:1px solid var(--slate-200);">
                         <h4 class="text-slate-400 font-bold uppercase" style="font-size:10px;letter-spacing:0.1em;margin-bottom:16px;">Recipient Details</h4>
                         <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div>
                                <div class="text-slate-400 font-bold uppercase" style="font-size:9px;">Recipient Name</div>
                                <div class="font-bold text-black" style="font-size:18px;">${escapeHtml(a.recipient_name || a.user_name)}</div>
                                <div class="text-sm text-slate-500 font-mono mt-1">${escapeHtml(a.phone || '—')}</div>
                            </div>
                            <div>
                                <div class="text-slate-400 font-bold uppercase" style="font-size:9px;">Account</div>
                                <div class="font-bold text-black" style="font-size:16px;">${escapeHtml(a.user_name)}</div>
                                <div class="text-xs text-slate-500 font-mono mt-1">${escapeHtml(a.user_email)}</div>
                            </div>
                         </div>
                    </div>

                    <div class="google-card" style="padding:24px;">
                        <h4 class="text-slate-400 font-bold uppercase" style="font-size:10px;letter-spacing:0.1em;margin-bottom:12px;border-bottom:1px solid var(--slate-100);padding-bottom:6px;">Address</h4>
                        <div class="text-slate-800 font-medium" style="font-size:16px;line-height:1.7;">
                             <div class="font-bold" style="font-size:18px;">${escapeHtml(a.address_line1)}</div>
                             ${a.address_line2 ? `<div class="text-slate-500">${escapeHtml(a.address_line2)}</div>` : ''}
                             <div style="margin-top:4px;">${escapeHtml(a.city)}, ${escapeHtml(a.state || 'N/A')}</div>
                             <div class="flex items-center" style="gap:10px;margin-top:2px;">
                                <span class="bg-slate-100 px-2 py-0.5 rounded text-xs font-mono font-bold text-slate-600">${escapeHtml(a.postal_code)}</span>
                                <span class="text-slate-400 uppercase tracking-widest font-black" style="font-size:12px;">${escapeHtml(a.country)}</span>
                             </div>
                        </div>
                    </div>
                </div>

                <div style="flex:1;display:flex;flex-direction:column;gap:16px;">
                    <div class="google-card" style="padding:20px;background:var(--slate-900);color:white;">
                         <h4 class="text-slate-500 font-bold uppercase" style="font-size:9px;letter-spacing:0.1em;margin-bottom:12px;">Usage Statistics</h4>
                         <div style="display:flex;flex-direction:column;gap:12px;">
                            <div class="flex justify-between items-center" style="border-bottom:1px solid #334155;padding-bottom:8px;">
                                <span class="text-slate-400 text-xs">As Shipping</span>
                                <span class="font-mono text-xl font-black text-indigo-400">${a.used_as_shipping || 0}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-400 text-xs">As Billing</span>
                                <span class="font-mono text-xl font-black text-emerald-400">${a.used_as_billing || 0}</span>
                            </div>
                         </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end" style="padding-top:12px;border-top:1px solid var(--slate-100);gap:8px;">
                <button class="btn btn-primary js-edit" data-id="${a.id}" style="padding:0 32px;">✏️ Edit Address</button>
            </div>
        </div>`;
}

// ─── Form Builder ─────────────────────────────────────────────────────────────

async function renderFormModal(id = null) {
  const isEdit = id !== null;
  let a = {};
  if (isEdit) a = await fetchAddress(id);
  const users = isEdit ? [] : await fetchUsers();

  const frag = getTemplate('tpl-user-address-form', {
    recipient_name: escapeHtml(a.recipient_name || ''),
    phone: escapeHtml(a.phone || ''),
    address_line1: escapeHtml(a.address_line1 || ''),
    address_line2: escapeHtml(a.address_line2 || ''),
    city: escapeHtml(a.city || ''),
    state: escapeHtml(a.state || ''),
    postal_code: escapeHtml(a.postal_code || ''),
    country: escapeHtml(a.country || 'Sri Lanka'),
    default_checked: a.is_default ? 'checked' : '',
    delete_display: isEdit ? 'block' : 'none',
    submit_text: isEdit ? 'Save Changes' : 'Add Address',
  });

  const uSel = frag.querySelector('#uadr-user-select');
  if (uSel) {
    if (isEdit) {
      uSel.innerHTML = `<option value="${a.user_id}" selected>${escapeHtml(a.user_name || '')} (${escapeHtml(a.user_email || '')})</option>`;
      uSel.disabled = true;
    } else {
      uSel.innerHTML =
        '<option value="" disabled selected>Select User...</option>' +
        users
          .map((u) => `<option value="${u.id}">${escapeHtml(u.name)} (${u.email})</option>`)
          .join('');
    }
  }

  const tSel = frag.querySelector('#uadr-type-select');
  if (tSel && isEdit) tSel.value = a.address_type || 'both';

  if (isEdit) {
    const footer = frag.querySelector('.flex.justify-end.gap-3.pt-6');
    if (footer) {
      const del = document.createElement('button');
      del.type = 'button';
      del.className = 'btn btn-outline text-danger mr-auto js-delete-btn';
      del.innerHTML = '🗑️ Delete Address';
      footer.prepend(del);
    }
  }
  return frag;
}

// ─── Custom Form Handlers ─────────────────────────────────────────────────────

function initFormHandlersOverride(modalRoot, id, onSuccess, closeModalFn, showFormErrorFn) {
  const isEdit = id !== null;
  const form = modalRoot.querySelector('#uadr-form');
  if (!form) return;

  const cancel = modalRoot.querySelector('#uadr-cancel');
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
            delBtn.innerHTML = '🗑️ Delete Address';
            delBtn.classList.remove('btn-warning');
          }
        }, 3000);
        return;
      }
      delBtn.disabled = true;
      delBtn.innerHTML = 'Deleting…';
      try {
        await apiRequest(API_ROUTES.USER_ADDRESSES.DELETE(id), { method: 'DELETE' });
        closeModalFn();
        onSuccess?.(null, 'deleted');
      } catch (err) {
        showFormErrorFn(form, err.message);
        delBtn.disabled = false;
        delBtn.innerHTML = '🗑️ Delete Address';
      }
    });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submit = form.querySelector('button[type="submit"]');
    const orig = submit.innerHTML;
    submit.disabled = true;
    submit.innerHTML = isEdit ? 'Saving…' : 'Adding…';
    try {
      const formData = new FormData(form);
      const payload = {
        user_id: parseInt(formData.get('user_id')),
        address_type: formData.get('address_type'),
        recipient_name: formData.get('recipient_name') || null,
        phone: formData.get('phone') || null,
        address_line1: formData.get('address_line1'),
        address_line2: formData.get('address_line2') || null,
        city: formData.get('city'),
        state: formData.get('state') || null,
        postal_code: formData.get('postal_code'),
        country: formData.get('country'),
        is_default: formData.get('is_default') !== null,
      };
      const url = isEdit ? API_ROUTES.USER_ADDRESSES.UPDATE(id) : API_ROUTES.USER_ADDRESSES.CREATE;
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

const { Render: UserAddresses, Init: initUserAddresses } = createEntityModule({
  entityName: 'Delivery Addresses',
  entitySubtitle: 'Manage shipping and billing addresses for customers',
  apiRoutes: {
    list: API_ROUTES.USER_ADDRESSES.LIST,
    detail: (id) => API_ROUTES.ADMIN_VIEWS.DETAIL('user_addresses', id),
    create: API_ROUTES.USER_ADDRESSES.CREATE,
    update: (id) => API_ROUTES.USER_ADDRESSES.UPDATE(id),
    delete: (id) => API_ROUTES.USER_ADDRESSES.DELETE(id),
  },
  fetchList: fetchAddresses,
  fetchSingle: fetchAddress,
  tableHeaderHtml: `<tr class="tr">
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">ID</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Account</th>
        <th class="px-8 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Type</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Location</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Priority</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Created</th>
        <th class="px-8 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Actions</th>
    </tr>`,
  renderRow,
  renderViewModal,
  renderFormModal,
  initFormHandlersOverride,
  searchPlaceholder: 'Search by name, city, or email…',
  createBtnText: '➕ New Address',
});

export { UserAddresses, initUserAddresses };
