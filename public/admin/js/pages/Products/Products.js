/**
 * Products.js — Consolidated Products domain module.
 * Uses EntityBuilder to eliminate boilerplate.
 */

import { API_ROUTES } from '../../dashboard.routes.js';
import { AdminAPI } from '../../admin-api.js';
import { escapeHtml, formatCurrency, formatNumber, formatDate, getTemplate } from '../../utils.js';
import { createEntityModule } from '../../components/EntityBuilder.js';

async function fetchProducts(limit = 20, offset = 0, query = '') {
  const res = await AdminAPI.products.enrichedAll({
    limit,
    offset,
    ...(query ? { search: query } : {}),
  });
  if (!res.success) throw new Error(res.message || 'Failed to fetch products');
  return res.data?.items || (Array.isArray(res.data) ? res.data : []);
}

async function fetchProduct(id) {
  const res = await AdminAPI.adminViews.detail('products', id);
  if (!res.success) throw new Error(res.message || 'Failed to fetch product');
  return res.data;
}

async function fetchCategories() {
  try {
    const res = await AdminAPI.categories.list({ limit: 100 });
    return res.success ? res.data || [] : [];
  } catch {
    return [];
  }
}

async function fetchSuppliers() {
  try {
    const res = await AdminAPI.suppliers.list({ limit: 100 });
    return res.success ? res.data || [] : [];
  } catch {
    return [];
  }
}

// ─── Row Renderer ─────────────────────────────────────────────────────────────
function renderRow(p) {
  const price = formatCurrency(p.price_cents || 0);
  const inStock = (p.available_stock ?? p.stock_quantity ?? 0) > 0;
  const stockQty = p.available_stock ?? p.stock_quantity ?? 0;
  const statusBadge = inStock
    ? `<span class="inline-flex items-center px-2 py-1 text-[9px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700 rounded-none">In Stock</span>`
    : `<span class="inline-flex items-center px-2 py-1 text-[9px] font-black uppercase tracking-wider bg-red-100 text-red-700 rounded-none">Out of Stock</span>`;

  const imgHtml = p.image_url
    ? `<img src="${escapeHtml(p.image_url)}" class="w-10 h-10 object-cover border border-gray-100" alt="${escapeHtml(p.name)}">`
    : `<div class="w-10 h-10 bg-gray-50 border border-gray-100 flex items-center justify-center text-xs">🍾</div>`;

  return `
        <tr class="group hover:bg-gray-50/50 transition-colors">
            <td class="px-8 py-5 text-[10px] font-bold text-gray-300 font-mono whitespace-nowrap">#${escapeHtml(String(p.id))}</td>
            <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                    ${imgHtml}
                    <div>
                        <div class="text-sm font-black text-black tracking-tight">${escapeHtml(p.name || '—')}</div>
                        <div class="text-[10px] text-gray-400 font-medium mt-0.5">${escapeHtml(p.category_name || 'Uncategorized')}</div>
                    </div>
                </div>
            </td>
            <td class="px-8 py-5">
                <div class="text-sm font-bold text-black tabular-nums">${price}</div>
            </td>
            <td class="px-8 py-5 text-center">
                <div class="text-sm font-bold text-black tabular-nums">${formatNumber(stockQty)}</div>
            </td>
            <td class="px-8 py-5 text-center">${statusBadge}</td>
            <td class="px-8 py-5 text-right">
                <div class="flex items-center justify-end gap-2">
                    <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-view" data-id="${p.id}" title="View details">
                        <span class="text-[10px]">👁️</span>
                    </button>
                    <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-edit" data-id="${p.id}" title="Edit product">
                        <span class="text-[10px]">✏️</span>
                    </button>
                    <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-red-500 hover:bg-red-500 hover:text-white hover:border-red-500 transition-all js-delete" data-id="${p.id}" title="Delete product">
                        <span class="text-[10px]">🗑️</span>
                    </button>
                </div>
            </td>
        </tr>`;
}

// ─── View Modal ───────────────────────────────────────────────────────────────
function renderViewModal(p) {
  const price = formatCurrency(p.price_cents || 0);
  const inStock = (p.available_stock ?? p.stock_quantity ?? 0) > 0;
  const stockQty = p.available_stock ?? p.stock_quantity ?? 0;

  const statusBadge = inStock
    ? `<span class="inline-flex items-center px-3 py-1 text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700 rounded-full">In Stock</span>`
    : `<span class="inline-flex items-center px-3 py-1 text-[10px] font-black uppercase tracking-wider bg-red-100 text-red-700 rounded-full">Out of Stock</span>`;

  const imgHtml = p.image_url
    ? `<img src="${escapeHtml(p.image_url)}" class="w-32 h-32 object-cover border border-gray-100 shadow-sm" alt="${escapeHtml(p.name)}">`
    : `<div class="w-32 h-32 bg-gray-50 border border-gray-100 flex items-center justify-center text-3xl shadow-sm">🍾</div>`;

  return `
        <div class="flex flex-col gap-6 p-2">
            <div class="flex items-start gap-6 pb-6 border-b border-gray-100">
                ${imgHtml}
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h2 class="text-2xl font-black tracking-tight text-black">${escapeHtml(p.name || '—')}</h2>
                        ${statusBadge}
                        ${!p.is_active ? `<span class="inline-flex items-center px-3 py-1 text-[10px] font-black uppercase tracking-wider bg-gray-100 text-gray-500 rounded-full">Inactive</span>` : ''}
                    </div>
                    <div class="text-sm font-medium text-gray-400 mb-4">${escapeHtml(p.category_name || 'Uncategorized')}</div>
                    <div class="text-3xl font-black tabular-nums tracking-tighter">${price}</div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="bg-gray-50 border border-gray-100 p-4">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Available Stock</div>
                    <div class="text-xl font-black tabular-nums">${formatNumber(stockQty)}</div>
                </div>
                <div class="bg-gray-50 border border-gray-100 p-4">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Supplier</div>
                    <div class="text-xl font-black text-black truncate" title="${escapeHtml(p.supplier_name || 'None')}">${escapeHtml(p.supplier_name || 'None')}</div>
                </div>
                <div class="bg-gray-50 border border-gray-100 p-4">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Added On</div>
                    <div class="text-xl font-black text-black">${p.created_at ? formatDate(p.created_at).split(' ')[0] : '—'}</div>
                </div>
            </div>

            <div class="mt-2">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Description</div>
                <div class="text-sm leading-relaxed text-gray-600 bg-white border border-gray-100 p-6 min-h-[100px]">
                    ${p.description ? escapeHtml(p.description).replace(/\\n/g, '<br>') : '<span class="text-gray-300 italic">No description provided.</span>'}
                </div>
            </div>

            <div class="flex justify-end pt-6 border-t border-gray-100 mt-2 gap-3">
                <button class="px-6 py-2.5 bg-black text-white text-xs font-bold uppercase tracking-wider hover:bg-gray-800 transition-colors js-edit" data-id="${p.id}">
                    Edit Product
                </button>
            </div>
        </div>`;
}

// ─── Form Builder ─────────────────────────────────────────────────────────────
async function renderFormModal(productId) {
  const isEdit = productId !== null;
  let p = {};
  if (isEdit) p = await fetchProduct(productId);

  const [cats, sups] = await Promise.all([fetchCategories(), fetchSuppliers()]);

  const catOpts = cats
    .map(
      (c) =>
        `<option value="${c.id}" ${p.category_id == c.id ? 'selected' : ''}>${escapeHtml(c.name)}</option>`
    )
    .join('');

  const supOpts = sups
    .map(
      (s) =>
        `<option value="${s.id}" ${p.supplier_id == s.id ? 'selected' : ''}>${escapeHtml(s.name)}</option>`
    )
    .join('');

  const frag = getTemplate('tpl-product-form', {
    name: escapeHtml(p.name || ''),
    slug: escapeHtml(p.slug || ''),
    price: p.price_cents ? (p.price_cents / 100).toFixed(2) : '',
    description: escapeHtml(p.description || ''),
    image_url: escapeHtml(p.image_url || ''),
    image_display: p.image_url ? 'block' : 'none',
    active_checked: p.is_active !== false ? 'checked' : '',
    categories: catOpts,
    suppliers: `<option value="">None (In-House)</option>` + supOpts,
    submit_text: isEdit ? 'Save Changes' : 'Create Product',
  });

  if (isEdit) {
    const footer = frag.querySelector('.flex.justify-end.gap-3.pt-6');
    if (footer) {
      const del = document.createElement('button');
      del.type = 'button';
      del.className =
        'px-6 py-2.5 bg-white border border-gray-200 text-red-500 hover:bg-red-50 hover:border-red-200 text-xs font-bold uppercase tracking-wider transition-colors mr-auto js-delete-btn';
      del.innerHTML = '🗑️ Delete Product';
      footer.prepend(del);
    }
  }
  return frag;
}

// ─── Entity Builder ───────────────────────────────────────────────────────────
const { Render: Products, Init: initProducts } = createEntityModule({
  entityName: 'Products',
  apiRoutes: {
    list: API_ROUTES.PRODUCTS.LIST,
    detail: (id) => API_ROUTES.ADMIN_VIEWS.DETAIL('products', id),
    create: API_ROUTES.PRODUCTS.CREATE,
    update: (id) => API_ROUTES.PRODUCTS.UPDATE(id),
    delete: (id) => API_ROUTES.PRODUCTS.DELETE(id),
  },
  fetchList: fetchProducts,
  fetchSingle: fetchProduct,
  tableHeaderHtml: `<tr>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">ID</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Product</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Price</th>
        <th class="px-8 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Stock</th>
        <th class="px-8 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Status</th>
        <th class="px-8 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Actions</th>
    </tr>`,
  renderRow,
  renderViewModal,
  renderFormModal,
  searchPlaceholder: 'Search by name, category, or SKU…',
  transformPayload: (payload) => {
    if (payload.price_cents !== undefined) payload.price_cents = parseInt(payload.price_cents);
    if (payload.category_id) payload.category_id = parseInt(payload.category_id);
    if (payload.supplier_id) payload.supplier_id = parseInt(payload.supplier_id);
    if (payload.prod_image_hidden) {
      payload.image_url = payload.prod_image_hidden;
      delete payload.prod_image_hidden;
    }
    return payload;
  },
});

export { Products, initProducts };
