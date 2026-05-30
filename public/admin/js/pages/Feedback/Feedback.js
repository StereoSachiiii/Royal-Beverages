/**
 * Feedback.js — Modernized Feedback domain module.
 * Uses EntityBuilder to eliminate boilerplate.
 */

import { API_ROUTES, buildQueryString } from '../../dashboard.routes.js';
import { apiRequest, escapeHtml, formatDate, getTemplate, closeModal } from '../../utils.js';
import { createEntityModule } from '../../components/EntityBuilder.js';

async function fetchFeedbackList(limit = 20, offset = 0, query = '') {
  try {
    const url =
      API_ROUTES.FEEDBACK.LIST +
      buildQueryString({ limit, offset, ...(query ? { search: query } : {}) });
    const res = await apiRequest(url);
    if (!res.success) throw new Error(res.message || 'Failed to fetch feedback');
    return res.data?.items || (Array.isArray(res.data) ? res.data : []);
  } catch (err) {
    console.error('[Feedback] Fetch failed', err);
    return [];
  }
}

async function fetchFeedbackItem(id) {
  try {
    const res = await apiRequest(API_ROUTES.FEEDBACK.GET(id));
    if (!res.success) throw new Error(res.message || 'Failed to fetch feedback item');
    return res.data;
  } catch (err) {
    throw err;
  }
}

async function fetchUsersForDropdown() {
  try {
    const res = await apiRequest('/api/v1/users?limit=200');
    return res.success ? res.data.items || res.data || [] : [];
  } catch (err) {
    return [];
  }
}

async function fetchProductsForDropdown() {
  try {
    const res = await apiRequest(API_ROUTES.PRODUCTS.LIST + '?limit=200');
    return res.success ? res.data || [] : [];
  } catch (err) {
    return [];
  }
}

// ─── Row Renderer ─────────────────────────────────────────────────────────────

function renderRow(f) {
  const rating = f.rating ?? 0;
  const stars = '⭐'.repeat(rating);
  const comment = f.comment
    ? f.comment.length > 40
      ? f.comment.substring(0, 37) + '...'
      : f.comment
    : '—';
  const verifiedBadge = f.is_verified_purchase
    ? `<span class="badge badge-active" style="font-size:10px;">Verified</span>`
    : `<span class="badge badge-inactive" style="font-size:10px;">Public</span>`;

  return `<tr class="group hover:bg-gray-50/50 transition-colors">
        <td class="px-6 py-4 text-[10px] font-bold text-gray-300 font-mono whitespace-nowrap">#${escapeHtml(String(f.id))}</td>
        <td class="px-6 py-4">
            <div class="text-amber-500 font-bold" style="font-size:13px;letter-spacing:-1px;">${stars}</div>
            <div class="text-[10px] text-slate-400 uppercase font-black">${rating}/5</div>
        </td>
        <td class="px-6 py-4"><div class="text-slate-700 italic" style="font-size:12px;">"${escapeHtml(comment)}"</div></td>
        <td class="px-6 py-4">
            <div class="font-semibold text-black" style="font-size:12px;">${escapeHtml(f.user_name || 'System')}</div>
            <div class="text-slate-500" style="font-size:10px;">${escapeHtml(f.user_email || '')}</div>
        </td>
        <td class="px-6 py-4"><div class="text-black font-medium truncate max-w-[140px]" style="font-size:12px;">${escapeHtml(f.product_name || 'N/A')}</div></td>
        <td class="px-6 py-4">${verifiedBadge}</td>
        <td class="px-6 py-4 text-slate-500 font-mono" style="font-size:11px;">${formatDate(f.created_at)}</td>
        <td class="px-6 py-4 text-right">
            <div class="flex items-center justify-end gap-2">
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-view" data-id="${f.id}"><span class="text-[10px]">👁️</span></button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-edit" data-id="${f.id}"><span class="text-[10px]">✏️</span></button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-red-500 hover:bg-red-500 hover:text-white transition-all js-delete" data-id="${f.id}"><span class="text-[10px]">🗑️</span></button>
            </div>
        </td>
    </tr>`;
}

// ─── View Modal ───────────────────────────────────────────────────────────────

function renderViewModal(f) {
  const isActive = f.is_active !== false && f.is_active !== 'f';
  return `
        <div class="flex flex-col" style="gap:24px; padding:8px;">
            <div class="flex items-center justify-between" style="padding-bottom:16px;border-bottom:1px solid var(--slate-100);">
                <div class="flex items-center" style="gap:16px;">
                    <div class="flex flex-col items-center justify-center bg-amber-50 border border-amber-200 rounded-2xl p-4 shadow-sm">
                        <div class="text-3xl">⭐</div>
                        <div class="font-black text-black" style="font-size:24px;">${f.rating}/5</div>
                    </div>
                    <div>
                         <h3 class="font-bold text-black" style="font-size:20px;letter-spacing:-0.02em;">Feedback #${f.id}</h3>
                         <div class="flex items-center" style="gap:8px;margin-top:6px;">
                            ${f.is_verified_purchase ? `<span class="badge badge-active" style="font-size:10px;">Verified Purchase</span>` : `<span class="badge badge-inactive" style="font-size:10px;">Public Review</span>`}
                            ${isActive ? `<span class="badge badge-active" style="font-size:10px;">Visible</span>` : `<span class="badge badge-inactive" style="font-size:10px;">Hidden</span>`}
                         </div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-slate-400 font-bold uppercase" style="font-size:9px;">Submitted</div>
                    <div class="font-bold text-black" style="font-size:14px;">${formatDate(f.created_at)}</div>
                </div>
            </div>

            <div class="google-card" style="padding:24px;background:var(--slate-50);border-left:4px solid var(--primary);">
                 <div class="text-slate-400 font-bold uppercase" style="font-size:9px;letter-spacing:0.1em;margin-bottom:12px;">Customer Comment</div>
                 <p class="text-black italic" style="font-size:16px;line-height:1.6;">
                    "${escapeHtml(f.comment || 'No comment provided.')}"
                 </p>
            </div>

            <div class="grid grid-cols-2 gap-20">
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <h4 class="text-slate-400 font-bold uppercase" style="font-size:10px;letter-spacing:0.1em;border-bottom:1px solid var(--slate-100);padding-bottom:4px;">Customer</h4>
                    <div class="flex flex-col" style="gap:8px;">
                        <div class="flex justify-between" style="font-size:13px;"><span class="text-slate-500">Name</span><span class="font-bold text-black">${escapeHtml(f.user_name || '-')}</span></div>
                        <div class="flex justify-between" style="font-size:13px;"><span class="text-slate-500">Email</span><span class="text-indigo-600 font-medium underline">${escapeHtml(f.user_email || '-')}</span></div>
                        <div class="flex justify-between" style="font-size:13px;"><span class="text-slate-500">User ID</span><span class="font-mono">#${f.user_id}</span></div>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <h4 class="text-slate-400 font-bold uppercase" style="font-size:10px;letter-spacing:0.1em;border-bottom:1px solid var(--slate-100);padding-bottom:4px;">Product</h4>
                    <div class="flex flex-col" style="gap:8px;">
                        <div class="flex justify-between" style="font-size:13px;"><span class="text-slate-500">Name</span><span class="font-bold text-black">${escapeHtml(f.product_name || '-')}</span></div>
                        <div class="flex justify-between" style="font-size:13px;"><span class="text-slate-500">Slug</span><span class="font-mono text-slate-500">${escapeHtml(f.product_slug || '-')}</span></div>
                        <div class="flex justify-between" style="font-size:13px;"><span class="text-slate-500">ID</span><span class="font-mono">#${f.product_id}</span></div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end" style="padding-top:12px;border-top:1px solid var(--slate-100);gap:8px;">
                <button class="btn btn-primary js-edit" data-id="${f.id}" style="padding:0 32px;">✏️ Edit Feedback</button>
            </div>
        </div>`;
}

// ─── Form Builder ─────────────────────────────────────────────────────────────

async function renderFormModal(feedbackId = null) {
  const isEdit = feedbackId !== null;
  let f = {},
    users = [],
    products = [];
  if (isEdit) {
    [f, users, products] = await Promise.all([
      fetchFeedbackItem(feedbackId),
      fetchUsersForDropdown(),
      fetchProductsForDropdown(),
    ]);
  } else {
    [users, products] = await Promise.all([fetchUsersForDropdown(), fetchProductsForDropdown()]);
  }

  const frag = getTemplate('tpl-feedback-form', {
    id: f.id || '',
    comment: escapeHtml(f.comment || ''),
    verified_checked: f.is_verified_purchase ? 'checked' : '',
    active_checked: f.is_active !== false ? 'checked' : '',
    submit_text: isEdit ? 'Save Changes' : 'Create Feedback',
  });

  const uSelect = frag.querySelector('#fdb-user-select');
  const pSelect = frag.querySelector('#fdb-product-select');
  if (uSelect)
    uSelect.innerHTML =
      '<option value="">-- Select User --</option>' +
      users
        .map(
          (u) =>
            `<option value="${u.id}" ${parseInt(f.user_id) === u.id ? 'selected' : ''}>${escapeHtml(u.name || u.username)} (${escapeHtml(u.email || 'N/A')})</option>`
        )
        .join('');
  if (pSelect)
    pSelect.innerHTML =
      '<option value="">-- Select Product --</option>' +
      products
        .map(
          (p) =>
            `<option value="${p.id}" ${parseInt(f.product_id) === p.id ? 'selected' : ''}>${escapeHtml(p.name)} (${p.sku || 'N/A'})</option>`
        )
        .join('');

  if (isEdit) {
    const rSelect = frag.querySelector('select[name="rating"]');
    if (rSelect) rSelect.value = f.rating || 5;
    const footer = frag.querySelector('.fdb-form-footer');
    if (footer) {
      const del = document.createElement('button');
      del.type = 'button';
      del.className = 'btn btn-outline text-danger mr-auto js-delete-btn';
      del.innerHTML = '🗑️ Delete Feedback';
      footer.prepend(del);
    }
  }
  return frag;
}

// ─── Custom Form Handlers ─────────────────────────────────────────────────────

function initFormHandlersOverride(modalRoot, id, onSuccess, closeModalFn, showFormErrorFn) {
  const isEdit = id !== null;
  const form = modalRoot.querySelector('#fdb-form');
  if (!form) return;

  const cancel = modalRoot.querySelector('#fdb-cancel');
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
            delBtn.innerHTML = '🗑️ Delete Feedback';
            delBtn.classList.remove('btn-warning');
          }
        }, 3000);
        return;
      }
      delBtn.disabled = true;
      delBtn.innerHTML = 'Deleting…';
      try {
        await apiRequest(API_ROUTES.FEEDBACK.DELETE(id), { method: 'DELETE' });
        closeModalFn();
        onSuccess?.(null, 'deleted');
      } catch (err) {
        showFormErrorFn(form, err.message);
        delBtn.disabled = false;
        delBtn.innerHTML = '🗑️ Delete Feedback';
      }
    });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submit = form.querySelector('[type="submit"]');
    const orig = submit.innerHTML;
    submit.disabled = true;
    submit.innerHTML = isEdit ? 'Saving…' : 'Adding…';
    try {
      const formData = new FormData(form);
      const payload = {
        user_id: parseInt(formData.get('user_id')),
        product_id: parseInt(formData.get('product_id')),
        rating: parseInt(formData.get('rating')),
        comment: formData.get('comment') || null,
        is_verified_purchase: formData.get('is_verified_purchase') !== null,
        is_active: formData.get('is_active') !== null,
      };
      const url = isEdit ? API_ROUTES.FEEDBACK.UPDATE(id) : API_ROUTES.FEEDBACK.CREATE;
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

const { Render: Feedback, Init: initFeedback } = createEntityModule({
  entityName: 'Customer Feedback',
  entitySubtitle: 'View and manage customer reviews and ratings',
  apiRoutes: {
    list: API_ROUTES.FEEDBACK.LIST,
    detail: (id) => API_ROUTES.FEEDBACK.GET(id),
    create: API_ROUTES.FEEDBACK.CREATE,
    update: (id) => API_ROUTES.FEEDBACK.UPDATE(id),
    delete: (id) => API_ROUTES.FEEDBACK.DELETE(id),
  },
  fetchList: fetchFeedbackList,
  fetchSingle: fetchFeedbackItem,
  tableHeaderHtml: `<tr class="tr">
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">ID</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Score</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Comment</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Customer</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Product</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Context</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Date</th>
        <th class="px-8 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Actions</th>
    </tr>`,
  renderRow,
  renderViewModal,
  renderFormModal,
  initFormHandlersOverride,
  searchPlaceholder: 'Search by comment, user, or product…',
  createBtnText: '➕ New Feedback',
});

export { Feedback, initFeedback };
