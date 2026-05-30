/**
 * Categories.js — Consolidated Categories domain module.
 * Uses EntityBuilder to eliminate boilerplate.
 */

import { API_ROUTES, buildQueryString } from '../../dashboard.routes.js';
import { apiRequest, escapeHtml, formatDate, getTemplate, closeModal } from '../../utils.js';
import { createEntityModule } from '../../components/EntityBuilder.js';
import { uploadImage } from '../../FormHelpers.js';

async function fetchCategories(limit = 20, offset = 0, query = '') {
  const url =
    API_ROUTES.CATEGORIES.LIST +
    buildQueryString({
      limit,
      offset,
      enriched: 'true',
      ...(query ? { search: query } : {}),
    });
  const res = await apiRequest(url);
  if (!res.success) throw new Error(res.message || 'Failed to fetch categories');
  return res.data?.items || (Array.isArray(res.data) ? res.data : []);
}

async function fetchCategory(id) {
  const url = API_ROUTES.CATEGORIES.GET(id) + buildQueryString({ enriched: 'true' });
  const res = await apiRequest(url);
  if (!res.success) throw new Error(res.message || 'Failed to fetch category');
  return res.data;
}

// ─── Row Renderer ─────────────────────────────────────────────────────────────
function renderRow(cat) {
  const isActive = cat.is_active !== false && cat.is_active !== 'f';
  const statusBadge = isActive
    ? `<span class="inline-flex items-center px-2 py-1 text-[9px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700 rounded-none">Active</span>`
    : `<span class="inline-flex items-center px-2 py-1 text-[9px] font-black uppercase tracking-wider bg-gray-100 text-gray-400 rounded-none">Inactive</span>`;

  const imgHtml = cat.image_url
    ? `<img src="${escapeHtml(cat.image_url)}" class="w-10 h-10 object-cover border border-gray-100" alt="${escapeHtml(cat.name)}">`
    : `<div class="w-10 h-10 bg-gray-50 border border-gray-100 flex items-center justify-center text-xs">🏷️</div>`;

  return `
        <tr class="group hover:bg-gray-50/50 transition-colors">
            <td class="px-8 py-5 text-[10px] font-bold text-gray-300 font-mono whitespace-nowrap">#${escapeHtml(String(cat.id))}</td>
            <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                    ${imgHtml}
                    <div>
                        <div class="text-sm font-black text-black tracking-tight">${escapeHtml(cat.name || '—')}</div>
                        ${cat.description ? `<div class="text-[10px] text-gray-400 font-medium mt-0.5 max-w-[200px] truncate">${escapeHtml(cat.description)}</div>` : ''}
                    </div>
                </div>
            </td>
            <td class="px-8 py-5">
                <div class="text-sm font-medium text-gray-600 tabular-nums">${cat.product_count ?? 0} items</div>
            </td>
            <td class="px-8 py-5">${statusBadge}</td>
            <td class="px-8 py-5 text-right">
                <div class="flex items-center justify-end gap-2">
                    <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-view" data-id="${cat.id}" title="View details">
                        <span class="text-[10px]">👁️</span>
                    </button>
                    <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-edit" data-id="${cat.id}" title="Edit category">
                        <span class="text-[10px]">✏️</span>
                    </button>
                    <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-red-600 hover:bg-red-600 hover:text-white transition-all js-delete" data-id="${cat.id}" title="Delete category">
                        <span class="text-[10px]">🗑️</span>
                    </button>
                </div>
            </td>
        </tr>`;
}

// ─── View Modal ───────────────────────────────────────────────────────────────
function renderViewModal(cat) {
  const avgPrice = cat.avg_price_cents ? (cat.avg_price_cents / 100).toFixed(2) : '—';
  const minPrice = cat.min_price_cents ? (cat.min_price_cents / 100).toFixed(2) : '—';
  const maxPrice = cat.max_price_cents ? (cat.max_price_cents / 100).toFixed(2) : '—';
  const isActive = cat.is_active !== false && cat.is_active !== 'f';

  const topProducts =
    Array.isArray(cat.top_products) && cat.top_products.length
      ? cat.top_products
          .map(
            (p) => `
            <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #f1f5f9;">
                <span style="font-size:13px;">${escapeHtml(p.name || 'Unknown')}</span>
                <span style="font-size:13px;font-weight:600;font-family:monospace;">Rs ${((p.price_cents || 0) / 100).toFixed(2)}</span>
            </div>`
          )
          .join('')
      : '<p style="font-size:13px;color:#94a3b8;margin:0;">No products in this category</p>';

  const metaRow = (label, val) =>
    `<div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #f8fafc;">
            <span style="color:#64748b;font-size:13px;">${label}</span>
            <span style="font-weight:600;font-size:13px;color:#0f172a;">${val}</span>
        </div>`;

  return `
        <div style="display:flex;flex-direction:column;gap:20px;">
            <div style="display:flex;align-items:center;gap:16px;padding-bottom:16px;border-bottom:1px solid #f1f5f9;">
                ${
                  cat.image_url
                    ? `<img src="${escapeHtml(cat.image_url)}" style="width:80px;height:80px;object-fit:cover;border-radius:12px;border:1px solid #e2e8f0;" alt="${escapeHtml(cat.name)}">`
                    : `<div style="width:80px;height:80px;background:#f1f5f9;border-radius:12px;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;font-size:32px;">🏷️</div>`
                }
                <div>
                    <h3 style="font-size:22px;font-weight:800;color:#0f172a;margin:0 0 4px;letter-spacing:-0.025em;">${escapeHtml(cat.name)}</h3>
                    <code style="font-size:11px;color:#94a3b8;">${escapeHtml(cat.slug || '')}</code>
                    <div style="margin-top:8px;">
                        ${isActive ? `<span class="badge badge-active">Active</span>` : `<span class="badge badge-inactive">Inactive</span>`}
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:10px;font-weight:700;color:#2563eb;text-transform:uppercase;letter-spacing:0.05em;">Products</div>
                    <div style="font-size:24px;font-weight:800;color:#1d4ed8;">${cat.total_products ?? cat.product_count ?? 0}</div>
                </div>
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:10px;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:0.05em;">Active</div>
                    <div style="font-size:24px;font-weight:800;color:#15803d;">${cat.active_products ?? 0}</div>
                </div>
                <div style="background:#fefce8;border:1px solid #fde68a;border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:10px;font-weight:700;color:#ca8a04;text-transform:uppercase;letter-spacing:0.05em;">Total Sales</div>
                    <div style="font-size:24px;font-weight:800;color:#b45309;">${cat.total_sales ?? 0}</div>
                </div>
                <div style="background:#fdf4ff;border:1px solid #e9d5ff;border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:10px;font-weight:700;color:#9333ea;text-transform:uppercase;letter-spacing:0.05em;">Avg Price</div>
                    <div style="font-size:18px;font-weight:800;color:#7c3aed;font-family:monospace;">Rs ${avgPrice}</div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <h4 style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 10px;">📋 Details</h4>
                    ${metaRow('ID', `#${cat.id}`)}
                    ${metaRow('Price Range', `Rs ${minPrice} – Rs ${maxPrice}`)}
                    ${metaRow('Description', cat.description || '—')}
                    ${metaRow('Created', cat.created_at ? formatDate(cat.created_at) : '—')}
                </div>
                <div>
                    <h4 style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 10px;">⭐ Top Products</h4>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;">
                        ${topProducts}
                    </div>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;padding-top:12px;border-top:1px solid #f1f5f9;gap:8px;">
                <button class="btn btn-primary js-edit" data-id="${cat.id}" style="padding:0 28px;">✏️ Edit Category</button>
            </div>
        </div>`;
}

// ─── Form Builder ─────────────────────────────────────────────────────────────
async function renderFormModal(categoryId) {
  const isEdit = categoryId !== null;
  let cat = {};
  if (isEdit) cat = await fetchCategory(categoryId);

  const frag = getTemplate('tpl-category-form', {
    name: escapeHtml(cat.name || ''),
    slug: escapeHtml(cat.slug || ''),
    description: escapeHtml(cat.description || ''),
    image_url: escapeHtml(cat.image_url || ''),
    image_display: cat.image_url ? 'block' : 'none',
    is_active_checked: cat.is_active !== false ? 'checked' : '',
    submit_text: isEdit ? 'Save Changes' : 'Create Category',
  });

  if (isEdit) {
    const footer = frag.querySelector('.cat-form-footer');
    if (footer) {
      const del = document.createElement('button');
      del.type = 'button';
      del.className = 'btn btn-outline text-danger mr-auto js-delete-btn';
      del.innerHTML = '🗑️ Delete';
      footer.prepend(del);
    }
  }
  return frag;
}

// ─── Custom Form Handlers ─────────────────────────────────────────────────────
function initFormHandlersOverride(modalRoot, id, onSuccess, closeModalFn, showFormErrorFn) {
  const isEdit = id !== null;
  const form = modalRoot.querySelector('#cat-form');
  if (!form) return;

  const nameInp = form.querySelector('[name="name"]');
  const slugInp = form.querySelector('[name="slug"]');

  // Auto-slug
  if (!isEdit && nameInp && slugInp) {
    nameInp.addEventListener('input', () => {
      if (!slugInp.dataset.manual) {
        slugInp.value = nameInp.value
          .toLowerCase()
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/^-|-$/g, '');
      }
    });
    slugInp.addEventListener('input', () => {
      slugInp.dataset.manual = '1';
    });
  }

  // Cancel
  const cancel = modalRoot.querySelector('.js-cancel');
  if (cancel) cancel.addEventListener('click', closeModalFn);

  // Image Upload
  const imgFile = form.querySelector('input[type="file"]');
  if (imgFile) {
    const imgPrev = modalRoot.querySelector(`#${imgFile.id.replace('-file', '-preview')}`);
    const imgHid = modalRoot.querySelector(`#${imgFile.id.replace('-file', '-hidden')}`);
    imgFile.addEventListener('change', async (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      const label = modalRoot.querySelector(`label[for="${imgFile.id}"]`);
      if (label) label.textContent = 'Uploading…';
      try {
        const url = await uploadImage(file, 'categories');
        if (imgHid) imgHid.value = url;
        if (imgPrev) {
          imgPrev.src = url;
          imgPrev.style.display = 'block';
        }
        if (label) label.textContent = '✅ Change Image';
      } catch (err) {
        if (label) label.textContent = '✗ Upload Failed';
      }
    });
  }

  // Delete
  const delBtn = modalRoot.querySelector('.js-delete-btn');
  if (delBtn) {
    delBtn.addEventListener('click', async () => {
      if (!delBtn.dataset.confirmed) {
        delBtn.dataset.confirmed = '1';
        delBtn.innerHTML = '⚠️ Confirm Delete?';
        delBtn.classList.add('btn-warning');
        setTimeout(() => {
          if (delBtn.isConnected) {
            delete delBtn.dataset.confirmed;
            delBtn.innerHTML = '🗑️ Delete';
            delBtn.classList.remove('btn-warning');
          }
        }, 3000);
        return;
      }
      delBtn.disabled = true;
      delBtn.innerHTML = 'Deleting…';
      try {
        await apiRequest(API_ROUTES.CATEGORIES.DELETE(id), { method: 'DELETE' });
        closeModalFn();
        onSuccess?.(null, 'deleted');
      } catch (err) {
        showFormErrorFn(form, err.message);
        delBtn.disabled = false;
        delBtn.innerHTML = '🗑️ Delete';
        delete delBtn.dataset.confirmed;
      }
    });
  }

  // Custom Submit handling to bypass standard getFormData due to ID mismatches
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submit = form.querySelector('[type="submit"]');
    const orig = submit.innerHTML;
    submit.disabled = true;
    submit.innerHTML = isEdit ? 'Saving…' : 'Creating…';
    try {
      const formData = new FormData(form);
      const imgHid = modalRoot.querySelector('#cat-image-hidden');
      const payload = {
        name: formData.get('name'),
        slug: formData.get('slug') || undefined,
        description: formData.get('description') || '',
        image_url: (imgHid ? imgHid.value : null) || formData.get('image_url') || null,
        is_active: formData.get('is_active') !== null,
      };
      const url = isEdit ? API_ROUTES.CATEGORIES.UPDATE(id) : API_ROUTES.CATEGORIES.CREATE;
      const res = await apiRequest(url, { method: isEdit ? 'PUT' : 'POST', body: payload });
      if (!res.success) throw new Error(res.message || 'Request failed');
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
const { Render: Categories, Init: initCategories } = createEntityModule({
  entityName: 'Categories',
  apiRoutes: {
    list: API_ROUTES.CATEGORIES.LIST,
    detail: (id) => API_ROUTES.CATEGORIES.GET(id),
    create: API_ROUTES.CATEGORIES.CREATE,
    update: (id) => API_ROUTES.CATEGORIES.UPDATE(id),
    delete: (id) => API_ROUTES.CATEGORIES.DELETE(id),
  },
  fetchList: fetchCategories,
  fetchSingle: fetchCategory,
  tableHeaderHtml: `<tr>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">ID</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Category</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Products</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Status</th>
        <th class="px-8 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Actions</th>
    </tr>`,
  renderRow,
  renderViewModal,
  renderFormModal,
  initFormHandlersOverride,
  searchPlaceholder: 'Search by name or description…',
});

export { Categories, initCategories };
