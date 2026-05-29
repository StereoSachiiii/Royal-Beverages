/**
 * UserPreferences.js — User Preferences domain module.
 * Uses EntityBuilder to eliminate boilerplate.
 */

import { API_ROUTES, buildQueryString } from '../../dashboard.routes.js';
import { apiRequest, escapeHtml, formatDate, getTemplate, closeModal } from '../../utils.js';
import { createEntityModule } from '../../components/EntityBuilder.js';

async function fetchPreferences(limit = 20, offset = 0, query = '') {
    try {
        const url = API_ROUTES.USER_PREFERENCES.LIST + buildQueryString({ limit, offset, ...(query ? { search: query } : {}) });
        const res = await apiRequest(url);
        if (!res.success) throw new Error(res.message || 'Failed to fetch preferences');
        return res.data?.items || (Array.isArray(res.data) ? res.data : []);
    } catch (err) { console.error('[UserPreferences] Fetch failed', err); return []; }
}

async function fetchPreference(id) {
    try {
        const res = await apiRequest(API_ROUTES.ADMIN_VIEWS.DETAIL('user_preferences', id));
        if (!res.success) throw new Error(res.message || 'Failed to fetch preference');
        return res.data;
    } catch (err) { throw err; }
}

async function fetchCategories() {
    try { const res = await apiRequest(API_ROUTES.CATEGORIES.LIST + '?limit=100'); return res.success ? (res.data || []) : []; }
    catch (err) { return []; }
}

function parsePgArray(val) {
    if (!val) return [];
    if (Array.isArray(val)) return val;
    if (typeof val === 'string') return val.replace(/[{}]/g, '').split(',').filter(s => s).map(Number);
    return [];
}

// ─── Row Renderer ─────────────────────────────────────────────────────────────

function renderRow(p) {
    const sw = p.preferred_sweetness ?? 0;
    const bi = p.preferred_bitterness ?? 0;
    const st = p.preferred_strength ?? 0;
    const sm = p.preferred_smokiness ?? 0;
    const favsCount = parsePgArray(p.favorite_categories).length;

    return `<tr class="group hover:bg-gray-50/50 transition-colors">
        <td class="px-6 py-4 text-[10px] font-bold text-gray-300 font-mono whitespace-nowrap">#${escapeHtml(String(p.id))}</td>
        <td class="px-6 py-4 font-bold text-black" style="font-size:13px;">${escapeHtml(p.user_name || 'Individual Profile')}</td>
        <td class="px-6 py-4">
            <div class="flex items-center" style="gap:8px;">
                <div class="flex flex-col items-center"><div style="width:12px;height:12px;border-radius:3px;background:#f59e0b;"></div><span style="font-size:9px;" class="font-mono text-slate-400">${sw}</span></div>
                <div class="flex flex-col items-center"><div style="width:12px;height:12px;border-radius:3px;background:#8b5cf6;"></div><span style="font-size:9px;" class="font-mono text-slate-400">${bi}</span></div>
                <div class="flex flex-col items-center"><div style="width:12px;height:12px;border-radius:3px;background:#ef4444;"></div><span style="font-size:9px;" class="font-mono text-slate-400">${st}</span></div>
                <div class="flex flex-col items-center"><div style="width:12px;height:12px;border-radius:3px;background:#64748b;"></div><span style="font-size:9px;" class="font-mono text-slate-400">${sm}</span></div>
            </div>
        </td>
        <td class="px-6 py-4"><span class="badge badge-secondary" style="font-size:10px;">${favsCount} Favorites</span></td>
        <td class="px-6 py-4 text-slate-500 font-mono" style="font-size:11px;">${formatDate(p.created_at)}</td>
        <td class="px-6 py-4 text-right">
            <div class="flex items-center justify-end gap-2">
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-view" data-id="${p.id}"><span class="text-[10px]">👁️</span></button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-edit" data-id="${p.id}"><span class="text-[10px]">✏️</span></button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-red-500 hover:bg-red-500 hover:text-white transition-all js-delete" data-id="${p.id}"><span class="text-[10px]">🗑️</span></button>
            </div>
        </td>
    </tr>`;
}

// ─── View Modal ───────────────────────────────────────────────────────────────

function renderViewModal(p) {
    const renderBar = (label, value, color) => {
        const pct = (value / 10) * 100;
        return `
            <div style="margin-bottom:12px;">
                <div class="flex justify-between items-baseline" style="margin-bottom:4px;">
                    <span class="text-slate-500 font-bold uppercase" style="font-size:9px;">${escapeHtml(label)}</span>
                    <span class="font-black" style="color:${color};font-size:14px;font-family:monospace;">${value}/10</span>
                </div>
                <div style="height:6px;background:var(--slate-100);border-radius:3px;overflow:hidden;border:1px solid var(--slate-200);">
                    <div style="width:${pct}%;height:100%;background:${color};"></div>
                </div>
            </div>`;
    };

    const favs = parsePgArray(p.favorite_categories);
    return `
        <div class="flex flex-col" style="gap:24px; padding:8px;">
            <div class="flex items-center justify-between" style="padding-bottom:16px;border-bottom:1px solid var(--slate-100);">
                <div class="flex items-center" style="gap:16px;">
                    <div class="thumb-lg rounded-2xl bg-slate-50 border flex items-center justify-center text-2xl shadow-inner">🧬</div>
                    <div>
                         <h3 class="font-bold text-black" style="font-size:22px;letter-spacing:-0.02em;">Taste Profile #${p.id}</h3>
                         <p class="text-sm text-slate-500">User: <span class="font-bold text-black">${escapeHtml(p.user_name)}</span> (#${p.user_id})</p>
                    </div>
                </div>
                <span class="text-slate-500 font-mono" style="font-size:11px;">${formatDate(p.created_at)}</span>
            </div>

            <div class="flex" style="gap:24px;">
                <div style="flex:1.4;">
                    <div class="google-card" style="padding:24px;background:var(--slate-50);">
                         <h4 class="text-slate-400 font-bold uppercase" style="font-size:10px;letter-spacing:0.1em;margin-bottom:20px;border-bottom:1px solid var(--slate-200);padding-bottom:4px;">Taste Preferences</h4>
                         <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 32px;">
                            ${renderBar('Sweetness', p.preferred_sweetness ?? 5, '#f59e0b')}
                            ${renderBar('Bitterness', p.preferred_bitterness ?? 5, '#8b5cf6')}
                            ${renderBar('Strength', p.preferred_strength ?? 5, '#ef4444')}
                            ${renderBar('Smokiness', p.preferred_smokiness ?? 5, '#64748b')}
                            ${renderBar('Fruitiness', p.preferred_fruitiness ?? 5, '#10b981')}
                            ${renderBar('Spiciness', p.preferred_spiciness ?? 5, '#f43f5e')}
                         </div>
                    </div>
                </div>

                <div style="flex:1;display:flex;flex-direction:column;gap:16px;">
                    <div class="google-card" style="padding:20px;">
                        <h4 class="text-slate-400 font-bold uppercase" style="font-size:10px;letter-spacing:0.1em;margin-bottom:12px;border-bottom:1px solid var(--slate-100);padding-bottom:4px;">Favourite Categories</h4>
                        <div class="flex flex-wrap" style="gap:6px;">
                            ${favs.length ? favs.map(c => `<span class="badge" style="background:var(--slate-50);border:1px solid var(--slate-200);color:var(--slate-600);font-size:10px;">Category #${c}</span>`).join('') : '<span class="text-xs text-slate-400 italic">None set.</span>'}
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end" style="padding-top:12px;border-top:1px solid var(--slate-100);gap:8px;">
                <button class="btn btn-primary js-edit" data-id="${p.id}" style="padding:0 32px;">✏️ Edit Preferences</button>
            </div>
        </div>`;
}

// ─── Form Builder (edit only — preferences are auto-created per user) ─────────

async function renderFormModal(id) {
    if (!id) throw new Error('UserPreferences requires a valid record ID.');
    const [p, cats] = await Promise.all([fetchPreference(id), fetchCategories()]);
    const selectedCats = parsePgArray(p.favorite_categories);

    const frag = getTemplate('tpl-user-preference-form', {
        user_name: escapeHtml(p.user_name || 'Profile')
    });

    const flavorMappings = {
        'sweet': 'sweetness', 'bitter': 'bitterness', 'strength': 'strength',
        'smoke': 'smokiness', 'fruit': 'fruitiness', 'spice': 'spiciness'
    };
    Object.keys(flavorMappings).forEach(k => {
        const sel = frag.querySelector(`#upref-${k}`);
        if (sel) {
            sel.innerHTML = Array.from({ length: 11 }, (_, i) => `<option value="${i}">${i}</option>`).join('');
            sel.value = p[`preferred_${flavorMappings[k]}`] ?? 5;
        }
    });

    const area = frag.querySelector('#upref-categories-area');
    if (area) {
        area.innerHTML = cats.map(c => `
            <label class="flex items-center gap-2 bg-slate-50 px-3 py-2 rounded-xl border border-slate-100 text-xs cursor-pointer hover:bg-slate-100 transition-colors">
                <input type="checkbox" name="favorite_categories" value="${c.id}" ${selectedCats.includes(c.id) ? 'checked' : ''} class="w-3.5 h-3.5">
                <span class="font-bold text-slate-700">${escapeHtml(c.name)}</span>
            </label>
        `).join('');
    }

    const footer = frag.querySelector('.flex.justify-end.gap-3.pt-6');
    if (footer) {
        const del = document.createElement('button');
        del.type = 'button'; del.className = 'btn btn-outline text-danger mr-auto js-delete-btn';
        del.innerHTML = '🗑️ Reset Preferences';
        footer.prepend(del);
    }
    return frag;
}

// ─── Custom Form Handlers ─────────────────────────────────────────────────────

function initFormHandlersOverride(modalRoot, id, onSuccess, closeModalFn, showFormErrorFn) {
    const form = modalRoot.querySelector('#upref-form');
    if (!form) return;

    const cancel = modalRoot.querySelector('#upref-cancel');
    if (cancel) cancel.addEventListener('click', closeModalFn);

    const delBtn = modalRoot.querySelector('.js-delete-btn');
    if (delBtn) {
        delBtn.addEventListener('click', async () => {
            if (!delBtn.dataset.confirmed) {
                delBtn.dataset.confirmed = '1'; delBtn.innerHTML = '⚠️ Confirm Reset?';
                delBtn.classList.add('btn-warning');
                setTimeout(() => { if (delBtn.isConnected) { delete delBtn.dataset.confirmed; delBtn.innerHTML = '🗑️ Reset Preferences'; delBtn.classList.remove('btn-warning'); }}, 3000);
                return;
            }
            delBtn.disabled = true; delBtn.innerHTML = 'Resetting…';
            try {
                await apiRequest(API_ROUTES.USER_PREFERENCES.DELETE(id), { method: 'DELETE' });
                closeModalFn(); onSuccess?.(null, 'deleted');
            } catch (err) { showFormErrorFn(form, err.message); delBtn.disabled = false; delBtn.innerHTML = '🗑️ Reset Preferences'; }
        });
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submit = form.querySelector('button[type="submit"]');
        const orig = submit.innerHTML;
        submit.disabled = true; submit.innerHTML = 'Saving…';
        try {
            const formData = new FormData(form);
            const cats = Array.from(form.querySelectorAll('input[name="favorite_categories"]:checked')).map(cb => parseInt(cb.value));
            const payload = {
                preferred_sweetness:  parseInt(formData.get('preferred_sweetness')),
                preferred_bitterness: parseInt(formData.get('preferred_bitterness')),
                preferred_strength:   parseInt(formData.get('preferred_strength')),
                preferred_smokiness:  parseInt(formData.get('preferred_smokiness')),
                preferred_fruitiness: parseInt(formData.get('preferred_fruitiness')),
                preferred_spiciness:  parseInt(formData.get('preferred_spiciness')),
                favorite_categories:  cats
            };
            await apiRequest(API_ROUTES.USER_PREFERENCES.UPDATE(id), { method: 'PUT', body: payload });
            closeModalFn(); onSuccess?.(null, 'updated');
        } catch (err) {
            showFormErrorFn(form, err.message);
            submit.disabled = false; submit.innerHTML = orig;
        }
    });
}

// ─── Entity Builder ───────────────────────────────────────────────────────────

const { Render: UserPreferences, Init: initUserPreferences } = createEntityModule({
    entityName: 'Taste Preferences',
    entitySubtitle: 'Manage individual user taste profiles and product interests',
    apiRoutes: {
        list: API_ROUTES.USER_PREFERENCES.LIST,
        detail: (id) => API_ROUTES.ADMIN_VIEWS.DETAIL('user_preferences', id),
        update: (id) => API_ROUTES.USER_PREFERENCES.UPDATE(id),
        delete: (id) => API_ROUTES.USER_PREFERENCES.DELETE(id)
    },
    fetchList: fetchPreferences,
    fetchSingle: fetchPreference,
    hideCreateBtn: true,
    tableHeaderHtml: `<tr class="tr">
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">ID</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">User</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Palate (Sw/Bi/St/Sm)</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Favourites</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Created</th>
        <th class="px-8 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Actions</th>
    </tr>`,
    renderRow,
    renderViewModal,
    renderFormModal,
    initFormHandlersOverride,
    searchPlaceholder: 'Search by user name or email…',
    createBtnText: ''
});

export { UserPreferences, initUserPreferences };
