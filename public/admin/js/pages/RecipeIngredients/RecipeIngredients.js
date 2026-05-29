/**
 * RecipeIngredients.js — Recipe Ingredients domain module.
 * Uses EntityBuilder to eliminate boilerplate.
 */

import { API_ROUTES, buildQueryString } from '../../dashboard.routes.js';
import { apiRequest, escapeHtml, formatDate, getTemplate, closeModal } from '../../utils.js';
import { createEntityModule } from '../../components/EntityBuilder.js';

async function fetchIngredients(limit = 20, offset = 0, query = '') {
    try {
        const url = API_ROUTES.RECIPE_INGREDIENTS.LIST + buildQueryString({ limit, offset, ...(query ? { search: query } : {}) });
        const res = await apiRequest(url);
        if (!res.success) throw new Error(res.message || 'Failed to fetch ingredients');
        return res.data?.items || (Array.isArray(res.data) ? res.data : []);
    } catch (err) { console.error('[RecipeIngredients] Fetch failed', err); return []; }
}

async function fetchIngredient(id) {
    try {
        const res = await apiRequest(API_ROUTES.ADMIN_VIEWS.DETAIL('recipe_ingredients', id));
        if (!res.success) throw new Error(res.message || 'Failed to fetch ingredient');
        return res.data;
    } catch (err) { throw err; }
}

async function fetchProducts() {
    try { const res = await apiRequest(API_ROUTES.PRODUCTS.LIST + '?limit=200'); return res.success ? (res.data || []) : []; }
    catch (err) { return []; }
}

async function fetchRecipesList() {
    try {
        const res = await apiRequest(API_ROUTES.COCKTAIL_RECIPES.LIST + '?limit=200');
        if (!res.success) return [];
        return Array.isArray(res.data) ? res.data : (res.data?.items || []);
    } catch (err) { return []; }
}

// ─── Row Renderer ─────────────────────────────────────────────────────────────

function renderRow(i) {
    const typeBadge = i.is_optional
        ? `<span class="badge badge-warning" style="font-size:10px;">Optional</span>`
        : `<span class="badge badge-active" style="font-size:10px;">Required</span>`;

    return `<tr class="group hover:bg-gray-50/50 transition-colors">
        <td class="px-6 py-4 text-[10px] font-bold text-gray-300 font-mono whitespace-nowrap">#${escapeHtml(String(i.id))}</td>
        <td class="px-6 py-4">
            <div class="font-bold text-black" style="font-size:13px;">${escapeHtml(i.recipe_name || 'N/A')}</div>
            <div class="text-slate-500 font-mono" style="font-size:10px;">Recipe #${i.recipe_id}</div>
        </td>
        <td class="px-6 py-4">
            <div class="font-semibold text-black" style="font-size:12px;">${escapeHtml(i.product_name || 'N/A')}</div>
            <div class="text-slate-400 font-mono" style="font-size:10px;">PID: #${i.product_id}</div>
        </td>
        <td class="px-6 py-4">
            <div class="font-bold text-black" style="font-size:13px;">${i.quantity} <span class="text-slate-400 font-normal uppercase" style="font-size:9px;">${escapeHtml(i.unit || '')}</span></div>
        </td>
        <td class="px-6 py-4">${typeBadge}</td>
        <td class="px-6 py-4 text-right">
            <div class="flex items-center justify-end gap-2">
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-view" data-id="${i.id}"><span class="text-[10px]">👁️</span></button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-edit" data-id="${i.id}"><span class="text-[10px]">✏️</span></button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-red-500 hover:bg-red-500 hover:text-white transition-all js-delete" data-id="${i.id}"><span class="text-[10px]">🗑️</span></button>
            </div>
        </td>
    </tr>`;
}

// ─── View Modal ───────────────────────────────────────────────────────────────

function renderViewModal(i) {
    const cost = ((i.product_price_cents || 0) / 100).toFixed(2);
    return `
        <div class="flex flex-col" style="gap:24px; padding:8px;">
            <div class="flex items-center justify-between" style="padding-bottom:16px;border-bottom:1px solid var(--slate-100);">
                <div class="flex items-center" style="gap:16px;">
                    <div class="thumb-lg rounded-2xl bg-slate-50 border flex items-center justify-center text-2xl shadow-inner">🧪</div>
                    <div>
                         <h3 class="font-bold text-black" style="font-size:20px;letter-spacing:-0.02em;">Ingredient Mapping #${i.id}</h3>
                         <p class="text-sm text-slate-500 font-mono">Added ${formatDate(i.created_at)}</p>
                    </div>
                </div>
                <span class="badge ${i.is_optional ? 'badge-warning' : 'badge-active'} uppercase" style="font-size:11px;padding:4px 12px;">${i.is_optional ? 'OPTIONAL' : 'REQUIRED'}</span>
            </div>

            <div class="flex" style="gap:24px;">
                <div style="flex:1.5;display:flex;flex-direction:column;gap:16px;">
                    <div class="google-card" style="padding:20px;background:var(--slate-50);">
                         <h4 class="text-slate-400 font-bold uppercase" style="font-size:9px;letter-spacing:0.1em;margin-bottom:16px;">Allocation</h4>
                         <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="google-card" style="padding:20px;background:white;">
                                <div class="text-slate-400 font-bold uppercase" style="font-size:9px;">Quantity</div>
                                <div class="font-black text-black" style="font-size:26px;font-family:monospace;">${i.quantity} <span class="text-slate-300 font-normal uppercase" style="font-size:12px;">${escapeHtml(i.unit)}</span></div>
                                <div class="text-[10px] text-slate-400 uppercase tracking-widest mt-1">Per Single Yield</div>
                            </div>
                            <div class="google-card" style="padding:16px;background:white;">
                                <div class="text-slate-400 font-bold uppercase" style="font-size:9px;">Unit Cost Ref</div>
                                <div class="font-bold text-black" style="font-size:18px;">Rs ${cost}</div>
                            </div>
                         </div>
                    </div>

                    <div class="google-card" style="padding:24px;">
                        <h4 class="text-slate-400 font-bold uppercase" style="font-size:10px;letter-spacing:0.1em;margin-bottom:12px;border-bottom:1px solid var(--slate-100);padding-bottom:6px;">Mapping Details</h4>
                        <div class="grid grid-cols-2 gap-12">
                             <div>
                                <div class="text-slate-400 font-bold uppercase" style="font-size:9px;">Recipe</div>
                                <div class="font-bold text-black" style="font-size:14px;">${escapeHtml(i.recipe_name)}</div>
                                <div class="text-xs text-slate-500 font-mono">RID: #${i.recipe_id}</div>
                             </div>
                             <div>
                                <div class="text-slate-400 font-bold uppercase" style="font-size:9px;">Product</div>
                                <div class="font-bold text-black" style="font-size:14px;">${escapeHtml(i.product_name)}</div>
                                <div class="text-xs text-slate-500 font-mono">PID: #${i.product_id}</div>
                             </div>
                        </div>
                    </div>
                </div>

                <div style="flex:1;display:flex;flex-direction:column;gap:16px;">
                    <div class="bg-indigo-50/50 p-5 rounded-2xl border border-indigo-100">
                        <h4 class="text-indigo-400 font-bold uppercase" style="font-size:10px;letter-spacing:0.1em;margin-bottom:8px;">Note</h4>
                        <p class="text-[12px] text-indigo-900 leading-relaxed">
                            Quantity adjustments directly impact inventory projections and production costs.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end" style="padding-top:12px;border-top:1px solid var(--slate-100);gap:8px;">
                <button class="btn btn-primary js-edit" data-id="${i.id}" style="padding:0 32px;">✏️ Edit Mapping</button>
            </div>
        </div>`;
}

// ─── Form Builder ─────────────────────────────────────────────────────────────

async function renderFormModal(id = null) {
    const isEdit = id !== null;
    let i = {}, recipes = [], products = [];
    if (isEdit) {
        [i, products] = await Promise.all([fetchIngredient(id), fetchProducts()]);
    } else {
        [recipes, products] = await Promise.all([fetchRecipesList(), fetchProducts()]);
    }

    const frag = getTemplate('tpl-recipe-ingredient-form', {
        recipe_name:           escapeHtml(i.recipe_name || ''),
        context_display:       isEdit ? 'block' : 'none',
        recipe_select_display: isEdit ? 'hidden' : 'block',
        quantity:              i.quantity || 1,
        optional_checked:      i.is_optional ? 'checked' : '',
        delete_display:        isEdit ? 'block' : 'none',
        submit_text:           isEdit ? 'Save Changes' : 'Add Ingredient'
    });

    const pSel = frag.querySelector('#ring-product-select');
    if (pSel) pSel.innerHTML = '<option value="" disabled>Select Product...</option>' + products.map(p => `<option value="${p.id}" ${p.id == i.product_id ? 'selected' : ''}>${escapeHtml(p.name)} (${p.sku || 'No SKU'})</option>`).join('');

    if (!isEdit) {
        const rSel = frag.querySelector('#ring-recipe-select');
        if (rSel) rSel.innerHTML = '<option value="" disabled selected>Select Recipe...</option>' + recipes.map(r => `<option value="${r.id}">${escapeHtml(r.name)}</option>`).join('');
    }

    const uSel = frag.querySelector('#ring-unit-select');
    if (uSel && i.unit) uSel.value = i.unit;

    if (isEdit) {
        const footer = frag.querySelector('.flex.justify-end.gap-3.pt-6');
        if (footer) {
            const del = document.createElement('button');
            del.type = 'button'; del.className = 'btn btn-outline text-danger mr-auto js-delete-btn';
            del.innerHTML = '🗑️ Remove Ingredient';
            footer.prepend(del);
        }
    }
    return frag;
}

// ─── Custom Form Handlers ─────────────────────────────────────────────────────

function initFormHandlersOverride(modalRoot, id, onSuccess, closeModalFn, showFormErrorFn) {
    const isEdit = id !== null;
    const form = modalRoot.querySelector('#ring-form');
    if (!form) return;

    const cancel = modalRoot.querySelector('#ring-cancel');
    if (cancel) cancel.addEventListener('click', closeModalFn);

    const delBtn = modalRoot.querySelector('.js-delete-btn');
    if (delBtn) {
        delBtn.addEventListener('click', async () => {
            if (!delBtn.dataset.confirmed) {
                delBtn.dataset.confirmed = '1'; delBtn.innerHTML = '⚠️ Confirm Remove?';
                delBtn.classList.add('btn-warning');
                setTimeout(() => { if (delBtn.isConnected) { delete delBtn.dataset.confirmed; delBtn.innerHTML = '🗑️ Remove Ingredient'; delBtn.classList.remove('btn-warning'); }}, 3000);
                return;
            }
            delBtn.disabled = true; delBtn.innerHTML = 'Removing…';
            try {
                await apiRequest(API_ROUTES.RECIPE_INGREDIENTS.DELETE(id), { method: 'DELETE' });
                closeModalFn(); onSuccess?.(null, 'deleted');
            } catch (err) { showFormErrorFn(form, err.message); delBtn.disabled = false; delBtn.innerHTML = '🗑️ Remove Ingredient'; }
        });
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submit = form.querySelector('button[type="submit"]');
        const orig = submit.innerHTML;
        submit.disabled = true; submit.innerHTML = isEdit ? 'Saving…' : 'Adding…';
        try {
            const formData = new FormData(form);
            const payload = {
                product_id:  parseInt(formData.get('product_id')),
                quantity:    parseFloat(formData.get('quantity')),
                unit:        formData.get('unit'),
                is_optional: formData.get('is_optional') !== null
            };
            if (!isEdit) payload.recipe_id = parseInt(formData.get('recipe_id'));
            const url = isEdit ? API_ROUTES.RECIPE_INGREDIENTS.UPDATE(id) : API_ROUTES.RECIPE_INGREDIENTS.CREATE;
            const res = await apiRequest(url, { method: isEdit ? 'PUT' : 'POST', body: payload });
            closeModalFn(); onSuccess?.(res.data, isEdit ? 'updated' : 'created');
        } catch (err) {
            showFormErrorFn(form, err.message);
            submit.disabled = false; submit.innerHTML = orig;
        }
    });
}

// ─── Entity Builder ───────────────────────────────────────────────────────────

const { Render: RecipeIngredients, Init: initRecipeIngredients } = createEntityModule({
    entityName: 'Recipe Ingredients',
    entitySubtitle: 'Link products to cocktail recipes and manage quantities',
    apiRoutes: {
        list: API_ROUTES.RECIPE_INGREDIENTS.LIST,
        detail: (id) => API_ROUTES.ADMIN_VIEWS.DETAIL('recipe_ingredients', id),
        create: API_ROUTES.RECIPE_INGREDIENTS.CREATE,
        update: (id) => API_ROUTES.RECIPE_INGREDIENTS.UPDATE(id),
        delete: (id) => API_ROUTES.RECIPE_INGREDIENTS.DELETE(id)
    },
    fetchList: fetchIngredients,
    fetchSingle: fetchIngredient,
    tableHeaderHtml: `<tr class="tr">
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">ID</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Recipe</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Product</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Quantity</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Type</th>
        <th class="px-8 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Actions</th>
    </tr>`,
    renderRow,
    renderViewModal,
    renderFormModal,
    initFormHandlersOverride,
    searchPlaceholder: 'Search by product or recipe name…',
    createBtnText: '➕ Add Ingredient'
});

export { RecipeIngredients, initRecipeIngredients };