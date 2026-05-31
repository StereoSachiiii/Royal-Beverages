/**
 * FlavourProfiles.js — Modernized Flavor Profiles domain module.
 * Uses EntityBuilder to eliminate boilerplate.
 */

import { API_ROUTES, buildQueryString } from '../../dashboard.routes.js';
import { getImageUrl, apiRequest, escapeHtml, getTemplate, closeModal } from '../../utils.js';
import { createEntityModule } from '../../components/EntityBuilder.js';

async function fetchFlavorProfiles(limit = 20, offset = 0, query = '') {
  try {
    const url =
      API_ROUTES.FLAVOR_PROFILES.LIST +
      buildQueryString({ limit, offset, ...(query ? { search: query } : {}) });
    const res = await apiRequest(url);
    if (!res.success) throw new Error(res.message || 'Failed to fetch profiles');
    return res.data?.items || (Array.isArray(res.data) ? res.data : []);
  } catch (err) {
    console.error('[FlavorProfiles] Fetch failed', err);
    return [];
  }
}

async function fetchFlavorProfile(id) {
  try {
    const res = await apiRequest(API_ROUTES.FLAVOR_PROFILES.GET(id));
    if (!res.success) throw new Error(res.message || 'Failed to fetch profile');
    return res.data;
  } catch (err) {
    throw err;
  }
}

async function fetchProductsForDropdown() {
  try {
    const res = await apiRequest(API_ROUTES.PRODUCTS.LIST + '?limit=100');
    return res.success ? res.data || [] : [];
  } catch (err) {
    return [];
  }
}

// ─── Row Renderer ─────────────────────────────────────────────────────────────

function renderBarMini(val, color) {
  const pct = (val / 10) * 100;
  return `<div style="width:40px;height:4px;background:var(--slate-100);border-radius:2px;overflow:hidden;border:1px solid var(--slate-200);">
        <div style="width:${pct}%;height:100%;background:${color};"></div>
    </div>`;
}

function renderRow(p) {
  return `<tr class="group hover:bg-gray-50/50 transition-colors">
        <td class="px-6 py-4 text-[10px] font-bold text-gray-300 font-mono whitespace-nowrap">#${escapeHtml(String(p.product_id))}</td>
        <td class="px-6 py-4">
            <div class="flex items-center" style="gap:10px;">
                ${p.product_image_url ? `<img src="${escapeHtml(getImageUrl(p.product_image_url))}" class="w-8 h-8 object-cover border rounded-sm">` : `<div class="w-8 h-8 bg-slate-100 flex items-center justify-center rounded-sm border text-sm">🍶</div>`}
                <div>
                    <div class="font-bold text-black" style="font-size:13px;">${escapeHtml(p.product_name || 'Individual Profile')}</div>
                    <div class="text-slate-400 font-mono" style="font-size:10px;">${escapeHtml(p.product_slug)}</div>
                </div>
            </div>
        </td>
        <td class="px-6 py-4"><div class="flex flex-col items-center gap-1">${renderBarMini(p.sweetness || 0, '#f59e0b')}<span class="font-mono text-[9px] text-slate-400">${p.sweetness || 0}/10</span></div></td>
        <td class="px-6 py-4"><div class="flex flex-col items-center gap-1">${renderBarMini(p.bitterness || 0, '#8b5cf6')}<span class="font-mono text-[9px] text-slate-400">${p.bitterness || 0}/10</span></div></td>
        <td class="px-6 py-4"><div class="flex flex-col items-center gap-1">${renderBarMini(p.strength || 0, '#ef4444')}<span class="font-mono text-[9px] text-slate-400">${p.strength || 0}/10</span></div></td>
        <td class="px-6 py-4"><div class="flex flex-col items-center gap-1">${renderBarMini(p.fruitiness || 0, '#10b981')}<span class="font-mono text-[9px] text-slate-400">${p.fruitiness || 0}/10</span></div></td>
        <td class="px-6 py-4"><div class="flex flex-col items-center gap-1">${renderBarMini(p.spiciness || 0, '#f43f5e')}<span class="font-mono text-[9px] text-slate-400">${p.spiciness || 0}/10</span></div></td>
        <td class="px-6 py-4 text-right">
            <div class="flex items-center justify-end gap-2">
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-view" data-id="${p.product_id}"><span class="text-[10px]">👁️</span></button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-edit" data-id="${p.product_id}"><span class="text-[10px]">✏️</span></button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-red-500 hover:bg-red-500 hover:text-white transition-all js-delete" data-id="${p.product_id}"><span class="text-[10px]">🗑️</span></button>
            </div>
        </td>
    </tr>`;
}

// ─── View Modal ───────────────────────────────────────────────────────────────

function renderViewModal(p) {
  const renderBar = (label, value, color) => {
    const pct = (value / 10) * 100;
    return `
            <div style="margin-bottom:16px;">
                <div class="flex justify-between items-baseline" style="margin-bottom:6px;">
                    <span class="text-slate-500 font-bold uppercase" style="font-size:10px;">${escapeHtml(label)}</span>
                    <span class="font-black" style="color:${color};font-size:16px;font-family:monospace;">${value}/10</span>
                </div>
                <div style="height:8px;background:var(--slate-100);border-radius:4px;overflow:hidden;border:1px solid var(--slate-200);">
                    <div style="width:${pct}%;height:100%;background:${color};border-radius:4px;"></div>
                </div>
            </div>`;
  };

  const tags = Array.isArray(p.tags) ? p.tags : [];
  const tagsHtml = tags
    .map(
      (t) =>
        `<span class="badge" style="background:var(--slate-50);border:1px solid var(--slate-200);color:var(--slate-600);font-size:10px;padding:4px 10px;">#${escapeHtml(t)}</span>`
    )
    .join('');

  return `
        <div class="flex flex-col" style="gap:24px; padding:8px;">
            <div class="flex items-center" style="gap:20px;padding-bottom:20px;border-bottom:1px solid var(--slate-100);">
                ${p.product_image_url ? `<img src="${escapeHtml(getImageUrl(p.product_image_url))}" class="thumb-xl rounded-2xl border shadow-sm" style="width:100px;height:100px;object-fit:cover;">` : `<div class="thumb-xl rounded-2xl bg-slate-50 flex items-center justify-center text-4xl border" style="width:100px;height:100px;">🍶</div>`}
                <div style="flex:1;">
                    <h3 class="font-bold text-black" style="font-size:26px;letter-spacing:-0.03em;">${escapeHtml(p.product_name)}</h3>
                    <div class="flex items-center" style="gap:10px;margin-top:4px;">
                        <span class="font-mono text-slate-500" style="font-size:12px;">ID: #${p.product_id}</span>
                        <span class="text-slate-300">•</span>
                        <span class="font-mono text-indigo-500" style="font-size:12px;">${escapeHtml(p.product_slug)}</span>
                    </div>
                </div>
            </div>

            <div class="flex" style="gap:24px;">
                <div style="flex:1.5;">
                    <div class="google-card" style="padding:24px;background:var(--slate-50);">
                         <h4 class="text-slate-400 font-bold uppercase" style="font-size:10px;letter-spacing:0.1em;margin-bottom:20px;border-bottom:1px solid var(--slate-200);padding-bottom:4px;">Flavour Matrix</h4>
                         <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 40px;">
                            ${renderBar('Strength', p.strength || 0, '#ef4444')}
                            ${renderBar('Sweetness', p.sweetness || 0, '#f59e0b')}
                            ${renderBar('Bitterness', p.bitterness || 0, '#8b5cf6')}
                            ${renderBar('Smokiness', p.smokiness || 0, '#64748b')}
                            ${renderBar('Fruitiness', p.fruitiness || 0, '#10b981')}
                            ${renderBar('Spiciness', p.spiciness || 0, '#f43f5e')}
                         </div>
                    </div>
                </div>
                <div style="flex:1;display:flex;flex-direction:column;gap:16px;">
                    <div class="google-card" style="padding:20px;">
                         <h4 class="text-slate-400 font-bold uppercase" style="font-size:10px;letter-spacing:0.1em;margin-bottom:12px;border-bottom:1px solid var(--slate-100);padding-bottom:4px;">Tags</h4>
                         <div class="flex flex-wrap" style="gap:6px;">
                            ${tagsHtml || '<span class="text-xs text-slate-400 italic">No tags assigned.</span>'}
                         </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end" style="padding-top:12px;border-top:1px solid var(--slate-100);gap:8px;">
                <button class="btn btn-primary js-edit" data-id="${p.product_id}" style="padding:0 32px;">✏️ Edit Matrix</button>
            </div>
        </div>`;
}

// ─── Form Builder ─────────────────────────────────────────────────────────────

function renderSlider(label, name, value = 5, color) {
  return `
        <div style="background:var(--slate-50);padding:14px;border-radius:12px;border:1px solid var(--slate-100);margin-bottom:12px;">
            <div class="flex justify-between items-center" style="margin-bottom:10px;">
                <label class="text-slate-600 font-bold uppercase" style="font-size:10px;">${escapeHtml(label)}</label>
                <span class="font-black" style="color:${color};font-size:16px;font-family:monospace;">${value}</span>
            </div>
            <input type="range" name="${name}" min="0" max="10" value="${value}" style="width:100%;cursor:pointer;accent-color:${color};height:4px;">
        </div>`;
}

async function renderFormModal(productId = null) {
  const isEdit = productId !== null;
  let p = {},
    products = [];
  if (isEdit) p = await fetchFlavorProfile(productId);
  else products = await fetchProductsForDropdown();

  const tagsValue = Array.isArray(p.tags) ? p.tags.join(', ') : '';
  const frag = getTemplate('tpl-flavor-profile-form', {
    id: p.product_id || '',
    name: escapeHtml(p.product_name || ''),
    slug: escapeHtml(p.product_slug || ''),
    image_url: escapeHtml(p.product_image_url || ''),
    image_display: p.product_image_url ? 'block' : 'none',
    product_section_display: isEdit ? 'none' : 'block',
    product_disabled: isEdit ? 'disabled' : '',
    edit_header_display: isEdit ? 'flex' : 'none',
    tags_value: escapeHtml(tagsValue),
    submit_text: isEdit ? 'Save Changes' : 'Create Profile',
  });

  const sliderContainer = frag.querySelector('#flap-sliders-container');
  if (sliderContainer) {
    sliderContainer.innerHTML = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px;">
                ${renderSlider('Strength', 'strength', p.strength ?? 5, '#ef4444')}
                ${renderSlider('Sweetness', 'sweetness', p.sweetness ?? 5, '#f59e0b')}
                ${renderSlider('Bitterness', 'bitterness', p.bitterness ?? 5, '#8b5cf6')}
                ${renderSlider('Smokiness', 'smokiness', p.smokiness ?? 5, '#64748b')}
                ${renderSlider('Fruitiness', 'fruitiness', p.fruitiness ?? 5, '#10b981')}
                ${renderSlider('Spiciness', 'spiciness', p.spiciness ?? 5, '#f43f5e')}
            </div>`;
  }

  if (!isEdit) {
    const select = frag.querySelector('#flap-product-select');
    if (select)
      select.innerHTML =
        '<option value="">-- Select Product --</option>' +
        products
          .map(
            (pr) =>
              `<option value="${pr.id}">${escapeHtml(pr.name)} (${pr.slug || 'no-slug'})</option>`
          )
          .join('');
  }

  if (isEdit) {
    const footer =
      frag.querySelector('.flap-form-footer') || frag.querySelector('.flex.justify-end.gap-3.pt-6');
    if (footer) {
      const del = document.createElement('button');
      del.type = 'button';
      del.className = 'btn btn-outline text-danger mr-auto js-delete-btn';
      del.innerHTML = '🗑️ Delete Profile';
      footer.prepend(del);
    }
  }
  return frag;
}

// ─── Custom Form Handlers ─────────────────────────────────────────────────────

function initFormHandlersOverride(modalRoot, id, onSuccess, closeModalFn, showFormErrorFn) {
  const isEdit = id !== null;
  const form = modalRoot.querySelector('#flap-form');
  if (!form) return;

  // Live slider value updates
  form.querySelectorAll('input[type="range"]').forEach((range) => {
    range.addEventListener('input', (e) => {
      const valSpan = e.target.parentElement.querySelector('.font-black');
      if (valSpan) valSpan.textContent = e.target.value;
    });
  });

  const cancel = modalRoot.querySelector('#flap-cancel');
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
            delBtn.innerHTML = '🗑️ Delete Profile';
            delBtn.classList.remove('btn-warning');
          }
        }, 3000);
        return;
      }
      delBtn.disabled = true;
      delBtn.innerHTML = 'Deleting…';
      try {
        await apiRequest(API_ROUTES.FLAVOR_PROFILES.DELETE(id), { method: 'DELETE' });
        closeModalFn();
        onSuccess?.(null, 'deleted');
      } catch (err) {
        showFormErrorFn(form, err.message);
        delBtn.disabled = false;
        delBtn.innerHTML = '🗑️ Delete Profile';
      }
    });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submit = form.querySelector('[type="submit"]');
    const orig = submit.innerHTML;
    submit.disabled = true;
    submit.innerHTML = isEdit ? 'Saving…' : 'Creating…';
    try {
      const formData = new FormData(form);
      const targetId = isEdit ? id : formData.get('product_id');
      if (!targetId) throw new Error('Select a product.');
      const tags = (formData.get('tags') || '')
        .split(',')
        .map((t) => t.trim())
        .filter(Boolean);
      const payload = {
        product_id: parseInt(targetId),
        sweetness: parseInt(formData.get('sweetness') ?? 5),
        bitterness: parseInt(formData.get('bitterness') ?? 5),
        strength: parseInt(formData.get('strength') ?? 5),
        smokiness: parseInt(formData.get('smokiness') ?? 5),
        fruitiness: parseInt(formData.get('fruitiness') ?? 5),
        spiciness: parseInt(formData.get('spiciness') ?? 5),
        tags,
      };
      const url = isEdit
        ? API_ROUTES.FLAVOR_PROFILES.UPDATE(targetId)
        : API_ROUTES.FLAVOR_PROFILES.CREATE;
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

const { Render: FlavourProfiles, Init: initFlavourProfiles } = createEntityModule({
  entityName: 'Flavour Profiles',
  entitySubtitle: 'Manage tasting notes and flavour characteristics for products',
  apiRoutes: {
    list: API_ROUTES.FLAVOR_PROFILES.LIST,
    detail: (id) => API_ROUTES.FLAVOR_PROFILES.GET(id),
    create: API_ROUTES.FLAVOR_PROFILES.CREATE,
    update: (id) => API_ROUTES.FLAVOR_PROFILES.UPDATE(id),
    delete: (id) => API_ROUTES.FLAVOR_PROFILES.DELETE(id),
  },
  fetchList: fetchFlavorProfiles,
  fetchSingle: fetchFlavorProfile,
  tableHeaderHtml: `<tr class="tr">
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">ID</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Product</th>
        <th class="px-8 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Sweet</th>
        <th class="px-8 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Bitter</th>
        <th class="px-8 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Intense</th>
        <th class="px-8 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Fruity</th>
        <th class="px-8 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Spicy</th>
        <th class="px-8 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Actions</th>
    </tr>`,
  renderRow,
  renderViewModal,
  renderFormModal,
  initFormHandlersOverride,
  searchPlaceholder: 'Search by product name or slug…',
  createBtnText: '➕ Create Profile',
});

export { FlavourProfiles, initFlavourProfiles };
