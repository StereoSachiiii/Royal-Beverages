/**
 * Users.js — Modernized Users domain module.
 * Uses EntityBuilder to eliminate boilerplate.
 */

import { API_ROUTES } from '../../dashboard.routes.js';
import { getImageUrl, escapeHtml, formatDate, getTemplate, apiRequest } from '../../utils.js';
import { createEntityModule } from '../../components/EntityBuilder.js';

async function fetchUser(id) {
  const res = await apiRequest(API_ROUTES.ADMIN_VIEWS.DETAIL('users', id));
  if (!res.success) throw new Error(res.message || 'Failed to fetch user');
  return res.data;
}

// ─── Row Renderer ─────────────────────────────────────────────────────────────
function renderRow(u) {
  const isActive = u.is_active !== false && u.is_active !== 'f';
  const statusBadge = isActive
    ? `<span class="badge badge-active">Active</span>`
    : `<span class="badge badge-inactive">Inactive</span>`;
  const roleBadge = u.is_admin
    ? `<span class="badge badge-warning">Admin</span>`
    : `<span class="badge badge-info">User</span>`;
  const avatar = u.profile_image_url
    ? `<img src="${escapeHtml(getImageUrl(u.profile_image_url))}" class="thumb-md rounded-full border shadow-sm" alt="${escapeHtml(u.name || '')}" style="flex-shrink:0;">`
    : `<div class="thumb-md rounded-full bg-slate-100 border flex items-center justify-center font-bold text-slate-400" style="flex-shrink:0;font-size:14px;">${escapeHtml((u.name || 'U')[0].toUpperCase())}</div>`;

  return `<tr class="tr group hover:bg-gray-50/50 transition-colors">
        <td class="td font-mono text-slate-400" style="font-size:11px;">#${escapeHtml(String(u.id))}</td>
        <td class="td">
            <div class="flex items-center" style="gap:10px;">
                ${avatar}
                <div>
                    <div class="font-semibold text-black" style="font-size:13px;">${escapeHtml(u.name || 'N/A')}</div>
                    <div class="text-slate-500" style="font-size:11px;">${escapeHtml(u.email || '')}</div>
                </div>
            </div>
        </td>
        <td class="td text-slate-500" style="font-size:12px;">${escapeHtml(u.phone || '—')}</td>
        <td class="td">${statusBadge}</td>
        <td class="td">${roleBadge}</td>
        <td class="td text-slate-500" style="font-size:11px;white-space:nowrap;">${u.created_at ? formatDate(u.created_at) : '—'}</td>
        <td class="td text-slate-500" style="font-size:11px;white-space:nowrap;">${u.last_login_at ? formatDate(u.last_login_at) : 'Never'}</td>
        <td class="td" style="white-space:nowrap;">
            <div class="flex items-center" style="gap:6px;">
                <button class="btn btn-outline btn-sm js-view" data-id="${u.id}" title="View">👁 View</button>
                <button class="btn btn-primary btn-sm js-edit" data-id="${u.id}" title="Edit">✏️ Edit</button>
                <button class="btn btn-outline btn-sm js-delete" data-id="${u.id}" title="Delete" style="color:var(--danger);border-color:var(--danger);">🗑</button>
            </div>
        </td>
    </tr>`;
}

// ─── View Modal ───────────────────────────────────────────────────────────────
function renderViewModal(u) {
  const ltv = (parseFloat(u.lifetime_value_cents || 0) / 100).toFixed(2);
  const aov = (parseFloat(u.avg_order_value_cents || 0) / 100).toFixed(2);
  const isActive = u.is_active !== false && u.is_active !== 'f';

  const getStatusBadge = (s) => {
    const m = {
      completed: 'badge-active',
      paid: 'badge-active',
      delivered: 'badge-active',
      cancelled: 'badge-inactive',
      returned: 'badge-inactive',
    };
    return m[s?.toLowerCase()] || 'badge-info';
  };

  const ordersRows =
    Array.isArray(u.recent_orders) && u.recent_orders.length
      ? u.recent_orders
          .map(
            (o) =>
              `<tr class="tr">
                <td class="td font-mono" style="font-size:11px;font-weight:600;">${escapeHtml(o.order_number || `#${o.id}`)}</td>
                <td class="td"><span class="badge ${getStatusBadge(o.status)}" style="font-size:10px;">${escapeHtml(o.status || '—')}</span></td>
                <td class="td text-right font-bold font-mono" style="font-size:12px;">Rs ${(parseFloat(o.total_cents || 0) / 100).toFixed(2)}</td>
            </tr>`
          )
          .join('')
      : `<tr class="tr"><td colspan="3" class="td text-center text-slate-400" style="padding:20px;font-style:italic;">No recent orders</td></tr>`;

  const avatar = u.profile_image_url
    ? `<img src="${escapeHtml(getImageUrl(u.profile_image_url))}" class="thumb-xl rounded-full border shadow-md" alt="${escapeHtml(u.name || '')}">`
    : `<div class="thumb-xl rounded-full bg-slate-100 border flex items-center justify-center font-bold text-slate-400" style="font-size:32px;">${escapeHtml((u.name || 'U')[0].toUpperCase())}</div>`;

  return `
        <div class="flex flex-col" style="gap:20px;">
            <div class="flex items-center justify-between" style="padding-bottom:16px;border-bottom:1px solid var(--slate-100);">
                <div class="flex items-center" style="gap:14px;">
                    ${avatar}
                    <div>
                        <div class="font-bold text-black" style="font-size:20px;letter-spacing:-0.02em;">${escapeHtml(u.name || 'N/A')}</div>
                        <div class="text-slate-500" style="font-size:13px;">${escapeHtml(u.email || '')}</div>
                        <div class="flex items-center" style="gap:6px;margin-top:6px;">
                            ${isActive ? `<span class="badge badge-active">Active</span>` : `<span class="badge badge-inactive">Inactive</span>`}
                            ${u.is_admin ? `<span class="badge badge-warning">Admin</span>` : `<span class="badge badge-info">User</span>`}
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex" style="gap:12px;">
                <div class="google-card flex-1 text-center" style="padding:14px;">
                    <div class="uppercase text-slate-500 font-semibold" style="font-size:10px;letter-spacing:0.05em;">LTV</div>
                    <div class="font-bold text-black font-mono" style="font-size:20px;">Rs ${ltv}</div>
                </div>
                <div class="google-card flex-1 text-center" style="padding:14px;">
                    <div class="uppercase text-slate-500 font-semibold" style="font-size:10px;letter-spacing:0.05em;">Orders</div>
                    <div class="font-bold text-black" style="font-size:20px;">${u.total_orders || 0}</div>
                </div>
                <div class="google-card flex-1 text-center" style="padding:14px;">
                    <div class="uppercase text-slate-500 font-semibold" style="font-size:10px;letter-spacing:0.05em;">Avg Order</div>
                    <div class="font-bold text-black font-mono" style="font-size:20px;">Rs ${aov}</div>
                </div>
                <div class="google-card flex-1 text-center" style="padding:14px;">
                    <div class="uppercase text-slate-500 font-semibold" style="font-size:10px;letter-spacing:0.05em;">Addresses</div>
                    <div class="font-bold text-black" style="font-size:20px;">${u.address_count || 0}</div>
                </div>
            </div>

            <div class="flex" style="gap:16px;">
                <div style="flex:1;">
                    <div class="uppercase text-slate-400 font-semibold" style="font-size:11px;letter-spacing:0.05em;margin-bottom:10px;">Account Details</div>
                    <div class="google-card" style="padding:14px;">
                        ${[
                          ['ID', `#${u.id}`],
                          ['Phone', u.phone || '—'],
                          ['Joined', u.created_at ? formatDate(u.created_at) : '—'],
                          ['Last Login', u.last_login_at ? formatDate(u.last_login_at) : 'Never'],
                          ['Completed', u.completed_orders || 0],
                          ['Pending', u.pending_orders || 0],
                          ['Cancelled', u.cancelled_orders || 0],
                        ]
                          .map(
                            ([l, v]) => `
                            <div class="flex justify-between" style="padding:5px 0;border-bottom:1px solid var(--slate-50);">
                                <span class="text-slate-500" style="font-size:12px;">${l}</span>
                                <span class="font-semibold text-black" style="font-size:12px;">${v}</span>
                            </div>`
                          )
                          .join('')}
                    </div>
                </div>
                <div style="flex:1.5;">
                    <div class="uppercase text-slate-400 font-semibold" style="font-size:11px;letter-spacing:0.05em;margin-bottom:10px;">Recent Orders</div>
                    <div class="table-container" style="border-radius:10px;">
                        <table class="table">
                            <thead>
                                <tr class="tr"><th class="th">Order #</th><th class="th">Status</th><th class="th text-right">Total</th></tr>
                            </thead>
                            <tbody>${ordersRows}</tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="flex justify-end" style="padding-top:12px;border-top:1px solid var(--slate-100);gap:8px;">
                <button class="btn btn-primary js-edit" data-id="${u.id}" style="padding:0 28px;">✏️ Edit User</button>
            </div>
        </div>`;
}

// ─── Form Builder ─────────────────────────────────────────────────────────────
async function renderFormModal(userId) {
  const isEdit = userId !== null;
  let u = {};
  if (isEdit) u = await fetchUser(userId);

  const frag = getTemplate('tpl-user-form', {
    name: escapeHtml(u.name || ''),
    email: escapeHtml(u.email || ''),
    phone: escapeHtml(u.phone || ''),
    image_url: escapeHtml(u.profile_image_url || ''),
    image_display: u.profile_image_url ? 'block' : 'none',
    admin_checked: u.is_admin ? 'checked' : '',
    active_checked: u.is_active !== false ? 'checked' : '',
    submit_text: isEdit ? 'Save Changes' : 'Create User',
    password_required: isEdit ? '' : 'required',
    password_required_class: isEdit ? '' : 'field-required',
    password_placeholder: isEdit ? 'Leave blank to keep' : 'Min 8 characters',
    info_display: isEdit ? 'block' : 'none',
    created_at: u.created_at ? formatDate(u.created_at) : '—',
    last_login: u.last_login_at ? formatDate(u.last_login_at) : 'Never',
  });

  if (isEdit) {
    const footer = frag.querySelector('.flex.justify-end.gap-3.pt-6');
    if (footer) {
      const del = document.createElement('button');
      del.type = 'button';
      del.className = 'btn btn-outline text-danger js-delete-btn';
      del.style.marginRight = 'auto';
      del.innerHTML = '🗑️ Delete User';
      footer.prepend(del);
    }
  }
  return frag;
}

// ─── Entity Builder ───────────────────────────────────────────────────────────
const { Render: Users, Init: initUsers } = createEntityModule({
  entityName: 'Users',
  apiRoutes: API_ROUTES.USERS,
  fetchSingle: fetchUser,
  tableHeaderHtml: `<tr class="tr">
        <th class="th" style="width:50px;">ID</th>
        <th class="th" style="min-width:200px;">User</th>
        <th class="th" style="width:120px;">Phone</th>
        <th class="th" style="width:80px;">Status</th>
        <th class="th" style="width:70px;">Role</th>
        <th class="th" style="width:130px;">Joined</th>
        <th class="th" style="width:130px;">Last Login</th>
        <th class="th" style="width:180px;">Actions</th>
    </tr>`,
  renderRow,
  renderViewModal,
  renderFormModal,
  searchPlaceholder: 'Search name, email, phone…',
  transformPayload: (payload) => {
    if (!payload.password) delete payload.password;
    if (payload.usr_image_hidden) {
      payload.profile_image_url = payload.usr_image_hidden;
      delete payload.usr_image_hidden;
    }
    return payload;
  },
});

export { Users, initUsers };
