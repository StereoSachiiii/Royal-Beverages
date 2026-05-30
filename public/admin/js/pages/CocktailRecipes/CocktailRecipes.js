/**
 * CocktailRecipes.js — Cocktail Recipes domain module.
 * Uses EntityBuilder to eliminate boilerplate.
 */

import { API_ROUTES, buildQueryString } from '../../dashboard.routes.js';
import { apiRequest, escapeHtml, formatDate, getTemplate, closeModal } from '../../utils.js';
import { createEntityModule } from '../../components/EntityBuilder.js';
import { initImageUpload } from '../../FormHelpers.js';

async function fetchRecipes(limit = 20, offset = 0, query = '') {
  try {
    const url =
      API_ROUTES.COCKTAIL_RECIPES.LIST +
      buildQueryString({ limit, offset, ...(query ? { search: query } : {}) });
    const res = await apiRequest(url);
    if (!res.success) throw new Error(res.message || 'Failed to fetch recipes');
    return res.data?.items || (Array.isArray(res.data) ? res.data : []);
  } catch (err) {
    console.error('[CocktailRecipes] Fetch failed', err);
    return [];
  }
}

async function fetchRecipe(id) {
  try {
    const res = await apiRequest(API_ROUTES.ADMIN_VIEWS.DETAIL('cocktail_recipes', id));
    if (!res.success) throw new Error(res.message || 'Failed to fetch recipe');
    return res.data;
  } catch (err) {
    throw err;
  }
}

// ─── Row Renderer ─────────────────────────────────────────────────────────────

function renderRow(r) {
  const diffClass =
    r.difficulty === 'hard'
      ? 'badge-danger'
      : r.difficulty === 'medium'
        ? 'badge-warning'
        : 'badge-active';
  const statusBadge = `<span class="badge ${r.is_active ? 'badge-active' : 'badge-inactive'} js-toggle-status cursor-pointer hover:opacity-80 transition-all" data-id="${r.id}" data-active="${r.is_active}">${r.is_active ? 'Active' : 'Draft'}</span>`;

  return `<tr class="group hover:bg-gray-50/50 transition-colors">
        <td class="px-6 py-4 text-[10px] font-bold text-gray-300 font-mono whitespace-nowrap">#${escapeHtml(String(r.id))}</td>
        <td class="px-6 py-4">
            <div class="flex items-center" style="gap:10px;">
                ${r.image_url ? `<img src="${r.image_url}" class="w-8 h-8 object-cover border rounded-sm">` : `<div class="w-8 h-8 bg-slate-100 flex items-center justify-center rounded-sm border text-sm">🍸</div>`}
                <div class="font-bold text-black" style="font-size:13px;">${escapeHtml(r.name || 'Untitled')}</div>
            </div>
        </td>
        <td class="px-6 py-4">
            <div class="flex items-center" style="gap:6px;">
                <span class="badge ${diffClass}" style="font-size:10px;padding:2px 8px;">${escapeHtml(r.difficulty || 'easy')}</span>
                <span class="text-slate-500 font-mono" style="font-size:11px;">${r.preparation_time || 0}m</span>
            </div>
        </td>
        <td class="px-6 py-4 text-slate-700 font-medium" style="font-size:12px;">Serves ${r.serves || 1}</td>
        <td class="px-6 py-4">${statusBadge}</td>
        <td class="px-6 py-4 text-center"><span class="font-bold text-indigo-600 font-mono" style="font-size:13px;">${r.ingredient_count || 0}</span></td>
        <td class="px-6 py-4 text-right">
            <div class="flex items-center justify-end gap-2">
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-view" data-id="${r.id}"><span class="text-[10px]">👁️</span></button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-edit" data-id="${r.id}"><span class="text-[10px]">✏️</span></button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-red-500 hover:bg-red-500 hover:text-white transition-all js-delete" data-id="${r.id}"><span class="text-[10px]">🗑️</span></button>
            </div>
        </td>
    </tr>`;
}

// ─── View Modal ───────────────────────────────────────────────────────────────

function renderViewModal(r) {
  const ingredients = r.ingredients || [];
  const cost = (r.estimated_cost_cents || 0) / 100;
  const diffClass =
    r.difficulty === 'hard'
      ? 'badge-danger'
      : r.difficulty === 'medium'
        ? 'badge-warning'
        : 'badge-active';

  const ingredientsHtml = ingredients
    .map(
      (ing) => `
        <div class="flex items-center justify-between p-3 bg-white border border-slate-100 rounded-xl shadow-xs">
            <div style="display:flex;flex-direction:column;">
                <span class="text-sm font-bold text-black">${escapeHtml(ing.product_name)}</span>
                ${ing.is_optional ? '<span style="font-size:9px;" class="text-orange-500 font-bold uppercase tracking-widest">Optional</span>' : ''}
            </div>
            <span class="bg-slate-50 px-3 py-1 rounded-lg text-xs font-mono font-bold text-slate-600 border border-slate-100">${ing.quantity} ${escapeHtml(ing.unit)}</span>
        </div>
    `
    )
    .join('');

  return `
        <div class="flex flex-col" style="gap:24px; padding:8px;">
            <div class="flex items-start justify-between" style="padding-bottom:20px;border-bottom:1px solid var(--slate-100);">
                <div class="flex" style="gap:20px;">
                    ${r.image_url ? `<img src="${r.image_url}" class="thumb-xl rounded-2xl border shadow-sm" style="width:120px;height:120px;object-fit:cover;">` : `<div class="thumb-xl rounded-2xl bg-slate-50 flex items-center justify-center text-5xl border border-dashed" style="width:120px;height:120px;">🍸</div>`}
                    <div>
                        <h3 class="font-bold text-black" style="font-size:28px;letter-spacing:-0.03em;line-height:1.1;">${escapeHtml(r.name)}</h3>
                        <div class="flex items-center" style="gap:10px;margin-top:8px;">
                            <span class="badge ${diffClass} uppercase" style="font-size:11px;padding:4px 12px;">${r.difficulty || 'easy'}</span>
                            <span class="text-slate-500" style="font-size:14px;">• ${r.preparation_time || 0} min • Serves ${r.serves || 1}</span>
                        </div>
                        <p class="text-slate-600" style="font-size:14px;margin-top:12px;line-height:1.5;max-width:500px;">${escapeHtml(r.description || 'No description.')}</p>
                    </div>
                </div>
                <div class="flex flex-col items-end" style="gap:12px;">
                     <span class="badge ${r.is_active ? 'badge-active' : 'badge-inactive'}" style="padding:6px 16px;font-size:11px;">${r.is_active ? 'PUBLISHED' : 'DRAFT'}</span>
                     <div class="text-right">
                        <div class="font-black text-black" style="font-size:24px;font-family:monospace;">Rs ${cost.toFixed(2)}</div>
                        <div class="text-[10px] text-slate-400 uppercase font-black tracking-widest">Est. Cost</div>
                     </div>
                </div>
            </div>

            <div class="flex" style="gap:24px;">
                <div style="flex:1.4;display:flex;flex-direction:column;gap:16px;">
                    <h4 class="text-slate-400 font-bold uppercase" style="font-size:10px;letter-spacing:0.1em;border-bottom:1px solid var(--slate-100);padding-bottom:4px;">Instructions</h4>
                    <div class="google-card" style="padding:24px;background:var(--slate-50);font-size:14px;line-height:1.7;color:var(--slate-800);white-space:pre-wrap;">${escapeHtml(r.instructions || 'No instructions added yet.')}</div>
                </div>

                <div style="flex:1;display:flex;flex-direction:column;gap:16px;">
                    <h4 class="text-slate-400 font-bold uppercase" style="font-size:10px;letter-spacing:0.1em;border-bottom:1px solid var(--slate-100);padding-bottom:4px;">Ingredients (${ingredients.length})</h4>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        ${ingredientsHtml || '<div class="text-center py-12 text-slate-400 italic text-sm">No ingredients mapped.</div>'}
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center" style="padding-top:16px;border-top:1px solid var(--slate-100);">
                <div class="text-[10px] text-slate-400 font-mono italic">ID: #${r.id} • ${formatDate(r.created_at)}</div>
                <button class="btn btn-primary js-edit" data-id="${r.id}" style="padding:0 32px;">✏️ Edit Recipe</button>
            </div>
        </div>`;
}

// ─── Form Builder ─────────────────────────────────────────────────────────────

async function renderFormModal(id = null) {
  const isEdit = id !== null;
  let r = {};
  if (isEdit) r = await fetchRecipe(id);

  const frag = getTemplate('tpl-cocktail-recipe-form', {
    id: isEdit ? id : '',
    name: escapeHtml(r.name || ''),
    description: escapeHtml(r.description || ''),
    instructions: escapeHtml(r.instructions || ''),
    preparation_time: r.preparation_time || 5,
    serves: r.serves || 1,
    active_checked: r.is_active !== false ? 'checked' : '',
    image_url: r.image_url || '',
    preview_url: r.image_url || '',
    preview_display: r.image_url ? 'block' : 'hidden',
    delete_display: isEdit ? 'block' : 'none',
    submit_text: isEdit ? 'Save Changes' : 'Create Recipe',
  });

  const diffSel = frag.querySelector('#crec-difficulty');
  if (diffSel && isEdit) diffSel.value = r.difficulty || 'easy';

  if (isEdit) {
    const footer = frag.querySelector('.flex.justify-end.gap-3.pt-6');
    if (footer) {
      const del = document.createElement('button');
      del.type = 'button';
      del.className = 'btn btn-outline text-danger mr-auto js-delete-btn';
      del.innerHTML = '🗑️ Delete Recipe';
      footer.prepend(del);
    }
  }
  return frag;
}

// ─── Custom Form Handlers ─────────────────────────────────────────────────────

function initFormHandlersOverride(modalRoot, id, onSuccess, closeModalFn, showFormErrorFn) {
  const isEdit = id !== null;
  const form = modalRoot.querySelector('#crec-form');
  if (!form) return;

  const cancel = modalRoot.querySelector('#crec-cancel');
  if (cancel) cancel.addEventListener('click', closeModalFn);

  initImageUpload(modalRoot, 'cocktail-recipes', 'crec-file-input', (url) => {
    modalRoot.querySelector('#crec-image-url').value = url;
    const preview = modalRoot.querySelector('#crec-preview');
    preview.src = url;
    preview.classList.remove('hidden');
  });

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
            delBtn.innerHTML = '🗑️ Delete Recipe';
            delBtn.classList.remove('btn-warning');
          }
        }, 3000);
        return;
      }
      delBtn.disabled = true;
      delBtn.innerHTML = 'Deleting…';
      try {
        await apiRequest(API_ROUTES.COCKTAIL_RECIPES.DELETE(id), { method: 'DELETE' });
        closeModalFn();
        onSuccess?.(null, 'deleted');
      } catch (err) {
        showFormErrorFn(form, err.message);
        delBtn.disabled = false;
        delBtn.innerHTML = '🗑️ Delete Recipe';
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
      const payload = {
        name: formData.get('name'),
        description: formData.get('description') || null,
        instructions: formData.get('instructions') || null,
        difficulty: formData.get('difficulty'),
        preparation_time: parseInt(formData.get('preparation_time')) || 0,
        serves: parseInt(formData.get('serves')) || 1,
        image_url: formData.get('image_url') || null,
        is_active: formData.get('is_active') !== null,
      };
      const url = isEdit
        ? API_ROUTES.COCKTAIL_RECIPES.UPDATE(id)
        : API_ROUTES.COCKTAIL_RECIPES.CREATE;
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

// ─── Extra Handlers (status toggle) ─────────────────────────────────────────

function extraContainerHandlers(container, reloadFn) {
  return (e) => {
    const badge = e.target.closest('.js-toggle-status');
    if (!badge) return;
    const id = badge.dataset.id;
    const current = badge.dataset.active === 'true' || badge.dataset.active === '1';
    badge.innerHTML = '…';
    badge.style.opacity = '0.5';
    apiRequest(API_ROUTES.COCKTAIL_RECIPES.UPDATE(id), {
      method: 'PUT',
      body: { is_active: !current },
    })
      .then(() => reloadFn())
      .catch(() => reloadFn());
  };
}

// ─── Entity Builder ───────────────────────────────────────────────────────────

const { Render: CocktailRecipes, Init: initCocktailRecipes } = createEntityModule({
  entityName: 'Cocktail Recipes',
  entitySubtitle: 'Manage your signature cocktail library',
  apiRoutes: {
    list: API_ROUTES.COCKTAIL_RECIPES.LIST,
    detail: (id) => API_ROUTES.ADMIN_VIEWS.DETAIL('cocktail_recipes', id),
    create: API_ROUTES.COCKTAIL_RECIPES.CREATE,
    update: (id) => API_ROUTES.COCKTAIL_RECIPES.UPDATE(id),
    delete: (id) => API_ROUTES.COCKTAIL_RECIPES.DELETE(id),
  },
  fetchList: fetchRecipes,
  fetchSingle: fetchRecipe,
  tableHeaderHtml: `<tr class="tr">
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">ID</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Recipe</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Difficulty / Time</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Serves</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Status</th>
        <th class="px-8 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Ingredients</th>
        <th class="px-8 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Actions</th>
    </tr>`,
  renderRow,
  renderViewModal,
  renderFormModal,
  initFormHandlersOverride,
  extraContainerHandlers,
  searchPlaceholder: 'Search by recipe name…',
  createBtnText: '➕ Add Recipe',
});

export { CocktailRecipes, initCocktailRecipes };
