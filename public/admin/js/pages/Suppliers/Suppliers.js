/**
 * Suppliers.js — Modernized Suppliers domain module.
 * Uses EntityBuilder to eliminate boilerplate.
 */

import { API_ROUTES, buildQueryString } from '../../dashboard.routes.js';
import { apiRequest, escapeHtml, formatDate, getTemplate, closeModal } from '../../utils.js';
import { createEntityModule } from '../../components/EntityBuilder.js';

async function fetchSuppliers(limit = 20, offset = 0, query = '') {
    try {
        const url = API_ROUTES.SUPPLIERS.LIST + buildQueryString({ limit, offset, ...(query ? { search: query } : {}) });
        const res = await apiRequest(url);
        if (!res.success) throw new Error(res.message || 'Failed to fetch suppliers');
        return res.data?.items || (Array.isArray(res.data) ? res.data : []);
    } catch (err) {
        console.error('[Suppliers]', err);
        return [];
    }
}

async function fetchSupplier(id) {
    try {
        const res = await apiRequest(API_ROUTES.ADMIN_VIEWS.DETAIL('suppliers', id));
        if (!res.success) throw new Error(res.message || 'Not found');
        return res.data;
    } catch (err) { throw err; }
}

// ─── Row Renderer ─────────────────────────────────────────────────────────────

function renderRow(sup) {
    const isActive    = sup.is_active !== false && sup.is_active !== 'f';
    const statusBadge = isActive
        ? `<span class="badge badge-active">Active</span>`
        : `<span class="badge badge-inactive">Inactive</span>`;
    const created = sup.created_at ? formatDate(sup.created_at) : '—';

    return `<tr class="tr group hover:bg-gray-50/50 transition-colors">
        <td class="px-6 py-4 text-[10px] font-bold text-gray-300 font-mono whitespace-nowrap">#${escapeHtml(String(sup.id))}</td>
        <td class="px-6 py-4">
            <div style="font-weight:600;color:#0f172a;font-size:13px;">${escapeHtml(sup.name || '—')}</div>
        </td>
        <td class="px-6 py-4" style="font-size:12px;color:#475569;">${escapeHtml(sup.email || '—')}</td>
        <td class="px-6 py-4" style="font-size:12px;color:#475569;white-space:nowrap;">${escapeHtml(sup.phone || '—')}</td>
        <td class="px-6 py-4" style="font-size:11px;color:#64748b;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escapeHtml(sup.address || '—')}</td>
        <td class="px-6 py-4">${statusBadge}</td>
        <td class="px-6 py-4" style="font-size:11px;color:#64748b;white-space:nowrap;">${created}</td>
        <td class="px-6 py-4 text-right">
            <div class="flex items-center justify-end gap-2">
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-view" data-id="${sup.id}" title="View details">
                    <span class="text-[10px]">👁️</span>
                </button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-edit" data-id="${sup.id}" title="Edit supplier">
                    <span class="text-[10px]">✏️</span>
                </button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-red-500 hover:bg-red-500 hover:text-white hover:border-red-500 transition-all js-delete" data-id="${sup.id}" title="Delete supplier">
                    <span class="text-[10px]">🗑️</span>
                </button>
            </div>
        </td>
    </tr>`;
}

// ─── View Modal ───────────────────────────────────────────────────────────────

function renderViewModal(sup) {
    const avgPrice = sup.avg_product_price_cents ? (sup.avg_product_price_cents / 100).toFixed(2) : '—';
    const isActive = sup.is_active !== false && sup.is_active !== 'f';

    const productsList = Array.isArray(sup.products) && sup.products.length
        ? sup.products.slice(0, 6).map(p =>
            `<div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #f1f5f9;">
                <span style="font-size:13px;">${escapeHtml(p.name || 'Unknown')}</span>
                <span style="font-size:12px;font-weight:600;font-family:monospace;">Rs ${((p.price_cents || 0) / 100).toFixed(2)}</span>
            </div>`).join('')
        : '<p style="font-size:13px;color:#94a3b8;margin:0;">No products supplied</p>';

    const moreCount = Array.isArray(sup.products) && sup.products.length > 6
        ? `<div style="font-size:11px;color:#94a3b8;margin-top:6px;">+ ${sup.products.length - 6} more products</div>` : '';

    const metaRow = (label, val) =>
        `<div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #f8fafc;">
            <span style="color:#64748b;font-size:13px;">${label}</span>
            <span style="font-weight:600;font-size:13px;color:#0f172a;">${val}</span>
        </div>`;

    return `
        <div style="display:flex;flex-direction:column;gap:20px;">
            <!-- Header -->
            <div style="display:flex;align-items:center;gap:16px;padding-bottom:16px;border-bottom:1px solid #f1f5f9;">
                <div style="width:64px;height:64px;background:#f1f5f9;border-radius:12px;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;font-size:28px;">🏭</div>
                <div>
                    <h3 style="font-size:22px;font-weight:800;color:#0f172a;margin:0 0 4px;letter-spacing:-0.025em;">${escapeHtml(sup.name)}</h3>
                    <div style="margin-top:8px;display:flex;gap:8px;">
                        ${isActive ? `<span class="badge badge-active">Active</span>` : `<span class="badge badge-inactive">Inactive</span>`}
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:10px;font-weight:700;color:#2563eb;text-transform:uppercase;letter-spacing:0.05em;">Products</div>
                    <div style="font-size:24px;font-weight:800;color:#1d4ed8;">${sup.total_products ?? 0}</div>
                </div>
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:10px;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:0.05em;">In Stock</div>
                    <div style="font-size:24px;font-weight:800;color:#15803d;">${sup.total_inventory ?? 0}</div>
                </div>
                <div style="background:#fefce8;border:1px solid #fde68a;border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:10px;font-weight:700;color:#ca8a04;text-transform:uppercase;letter-spacing:0.05em;">Avg Price</div>
                    <div style="font-size:18px;font-weight:800;color:#b45309;font-family:monospace;">Rs ${avgPrice}</div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <!-- Contact -->
                <div>
                    <h4 style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 10px;">📋 Contact Info</h4>
                    ${metaRow('ID', `#${sup.id}`)}
                    ${metaRow('Email', sup.email ? `<a href="mailto:${escapeHtml(sup.email)}" style="color:#2563eb;">${escapeHtml(sup.email)}</a>` : '—')}
                    ${metaRow('Phone', sup.phone || '—')}
                    ${metaRow('Address', sup.address ? `<span style="max-width:160px;text-align:right;display:inline-block;">${escapeHtml(sup.address)}</span>` : '—')}
                    ${metaRow('Created', sup.created_at ? formatDate(sup.created_at) : '—')}
                    ${metaRow('Updated', sup.updated_at ? formatDate(sup.updated_at) : '—')}
                </div>
                <!-- Products -->
                <div>
                    <h4 style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 10px;">📦 Supplied Products</h4>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;">
                        ${productsList}${moreCount}
                    </div>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;padding-top:12px;border-top:1px solid #f1f5f9;gap:8px;">
                <button class="btn btn-primary js-edit" data-id="${sup.id}" style="padding:0 28px;">✏️ Edit Supplier</button>
            </div>
        </div>`;
}

// ─── Form Builder ─────────────────────────────────────────────────────────────

async function renderFormModal(supplierId = null) {
    const isEdit = supplierId !== null;
    let sup = {};
    if (isEdit) sup = await fetchSupplier(supplierId);

    const frag = getTemplate('tpl-supplier-form', {
        name:              escapeHtml(sup.name || ''),
        email:             escapeHtml(sup.email || ''),
        phone:             escapeHtml(sup.phone || ''),
        address:           escapeHtml(sup.address || ''),
        is_active_checked: sup.is_active !== false ? 'checked' : '',
        submit_text:       isEdit ? 'Save Changes' : 'Create Supplier',
        stats_display:     isEdit ? 'block' : 'none',
        product_count:     sup.total_products ?? 0,
        created_at:        formatDate(sup.created_at)
    });

    if (isEdit) {
        const footer = frag.querySelector('.sup-form-footer');
        if (footer) {
            const del = document.createElement('button');
            del.type = 'button';
            del.className = 'btn btn-outline text-danger mr-auto js-delete-btn';
            del.dataset.id = supplierId;
            del.innerHTML = '🗑️ Delete';
            footer.prepend(del);
        }
    }
    return frag;
}

// ─── Custom Form Handlers ─────────────────────────────────────────────────────

function initFormHandlersOverride(modalRoot, id, onSuccess, closeModalFn, showFormErrorFn) {
    const isEdit = id !== null;
    const form = modalRoot.querySelector('#sup-form');
    if (!form) return;

    const cancel = modalRoot.querySelector('#sup-cancel');
    if (cancel) cancel.addEventListener('click', closeModalFn);

    const delBtn = modalRoot.querySelector('.js-delete-btn');
    if (delBtn) {
        delBtn.addEventListener('click', async () => {
            if (!delBtn.dataset.confirmed) {
                delBtn.dataset.confirmed = '1';
                delBtn.innerHTML = '⚠️ Confirm Delete?';
                delBtn.classList.add('btn-warning');
                setTimeout(() => { if (delBtn.isConnected) { delete delBtn.dataset.confirmed; delBtn.innerHTML = '🗑️ Delete'; delBtn.classList.remove('btn-warning'); }}, 3000);
                return;
            }
            delBtn.disabled = true; delBtn.innerHTML = 'Deleting…';
            try {
                await apiRequest(API_ROUTES.SUPPLIERS.DELETE(id), { method: 'DELETE' });
                closeModalFn(); onSuccess?.(null, 'deleted');
            } catch (err) {
                showFormErrorFn(form, err.message);
                delBtn.disabled = false; delBtn.innerHTML = '🗑️ Delete';
            }
        });
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submit = form.querySelector('[type="submit"]');
        const orig = submit.innerHTML;
        submit.disabled = true; submit.innerHTML = isEdit ? 'Saving…' : 'Creating…';
        try {
            const formData = new FormData(form);
            const payload = { 
                name: formData.get('name'), 
                email: formData.get('email') || null, 
                phone: formData.get('phone') || null, 
                address: formData.get('address') || null, 
                is_active: formData.get('is_active') !== null 
            };
            const url = isEdit ? API_ROUTES.SUPPLIERS.UPDATE(id) : API_ROUTES.SUPPLIERS.CREATE;
            const res = await apiRequest(url, { method: isEdit ? 'PUT' : 'POST', body: payload });
            if (!res.success) throw new Error(res.message);
            closeModalFn(); onSuccess?.(res.data, isEdit ? 'updated' : 'created');
        } catch (err) {
            showFormErrorFn(form, err.message);
            submit.disabled = false; submit.innerHTML = orig;
        }
    });
}

// ─── Entity Builder ───────────────────────────────────────────────────────────

const { Render: Suppliers, Init: initSuppliers } = createEntityModule({
    entityName: 'Suppliers',
    entitySubtitle: 'Manage supplier information and contacts',
    apiRoutes: {
        list: API_ROUTES.SUPPLIERS.LIST,
        detail: (id) => API_ROUTES.ADMIN_VIEWS.DETAIL('suppliers', id),
        create: API_ROUTES.SUPPLIERS.CREATE,
        update: (id) => API_ROUTES.SUPPLIERS.UPDATE(id),
        delete: (id) => API_ROUTES.SUPPLIERS.DELETE(id)
    },
    fetchList: fetchSuppliers,
    fetchSingle: fetchSupplier,
    tableHeaderHtml: `<tr class="tr">
        <th class="th" style="width:50px;">ID</th>
        <th class="th" style="min-width:160px;">Supplier</th>
        <th class="th">Email</th>
        <th class="th" style="width:130px;">Phone</th>
        <th class="th" style="min-width:180px;">Address</th>
        <th class="th" style="width:80px;">Status</th>
        <th class="th" style="width:130px;">Created</th>
        <th class="th" style="width:180px;">Actions</th>
    </tr>`,
    renderRow,
    renderViewModal,
    renderFormModal,
    initFormHandlersOverride,
    searchPlaceholder: 'Search name, email…',
    createBtnText: '➕ New Supplier'
});

export { Suppliers, initSuppliers };