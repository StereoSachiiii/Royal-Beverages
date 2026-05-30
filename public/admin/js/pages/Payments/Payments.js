/**
 * Payments.js — Modernized Payments domain module.
 * Uses dashboard-tailwind.css classes throughout.
 * Uses EntityBuilder to eliminate boilerplate.
 */

import { API_ROUTES, buildQueryString } from '../../dashboard.routes.js';
import { apiRequest, escapeHtml, formatDate, getTemplate, closeModal } from '../../utils.js';
import { createEntityModule } from '../../components/EntityBuilder.js';

async function fetchPayments(limit = 20, offset = 0, query = '') {
  try {
    const url =
      API_ROUTES.PAYMENTS.LIST +
      buildQueryString({
        limit,
        offset,
        ...(query ? { search: query } : {}),
      });
    const res = await apiRequest(url);
    if (!res.success) throw new Error(res.message || 'Failed to fetch payments');
    return res.data?.items || (Array.isArray(res.data) ? res.data : []);
  } catch (err) {
    console.error('[Payments] Fetch failed', err);
    return [];
  }
}

async function fetchPayment(id) {
  try {
    const url = API_ROUTES.ADMIN_VIEWS.DETAIL('payments', id);
    const res = await apiRequest(url);
    if (!res.success) throw new Error(res.message || 'Failed to fetch payment details');
    return res.data;
  } catch (err) {
    throw err;
  }
}

async function fetchDependencies() {
  try {
    const res = await apiRequest(API_ROUTES.ORDERS.LIST + '?limit=100');
    return { orders: res.data?.items || res.data || [] };
  } catch (err) {
    return { orders: [] };
  }
}

// ─── Utils ───────────────────────────────────────────────────────────────────

function getStatusClass(status) {
  const s = (status || 'pending').toLowerCase();
  switch (s) {
    case 'captured':
    case 'success':
    case 'paid':
      return 'badge-active';
    case 'pending':
      return 'badge-warning';
    case 'failed':
      return 'badge-inactive';
    case 'refunded':
      return 'badge-info';
    case 'voided':
      return 'badge-secondary';
    default:
      return 'badge-secondary';
  }
}

function formatCurrency(cents) {
  return (cents / 100).toFixed(2);
}

// ─── Row Renderer ─────────────────────────────────────────────────────────────

function renderRow(p) {
  const statusBadge = `<span class="badge ${getStatusClass(p.status)}">${escapeHtml(p.status || 'pending')}</span>`;
  const created = p.created_at ? formatDate(p.created_at) : '—';
  const amount = formatCurrency(p.amount_cents || 0);

  return `<tr class="tr group hover:bg-gray-50/50 transition-colors">
        <td class="px-6 py-4 text-[10px] font-bold text-gray-300 font-mono whitespace-nowrap">#${escapeHtml(String(p.id))}</td>
        <td class="px-6 py-4">
            <div class="font-bold text-black" style="font-size:13px;">Order #${escapeHtml(p.order_number || (p.order_id ? p.order_id.toString() : 'ORPHANED'))}</div>
            <div class="text-slate-500 font-mono" style="font-size:10px;">Internal ID: ${p.order_id}</div>
        </td>
        <td class="px-6 py-4">
            <div class="font-semibold text-black" style="font-size:12px;">${escapeHtml(p.gateway || 'manual')}</div>
            <div class="text-slate-400 font-mono truncate max-w-[120px]" style="font-size:10px;">Ref: ${escapeHtml(p.transaction_id || 'N/A')}</div>
        </td>
        <td class="px-6 py-4">${statusBadge}</td>
        <td class="px-6 py-4 font-bold font-mono text-black" style="font-size:13px;">Rs ${amount}</td>
        <td class="px-6 py-4 text-slate-500 font-mono" style="font-size:11px;">${created}</td>
        <td class="px-6 py-4 text-right">
            <div class="flex items-center justify-end gap-2">
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-view" data-id="${p.id}" title="View Details">
                    <span class="text-[10px]">👁️</span>
                </button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-black hover:bg-black hover:text-white transition-all js-edit" data-id="${p.id}" title="Edit Payment">
                    <span class="text-[10px]">✏️</span>
                </button>
                <button class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 text-red-500 hover:bg-red-500 hover:text-white hover:border-red-500 transition-all js-delete" data-id="${p.id}" title="Void Payment">
                    <span class="text-[10px]">🗑️</span>
                </button>
            </div>
        </td>
    </tr>`;
}

// ─── View Modal ───────────────────────────────────────────────────────────────

function renderViewModal(p) {
  const payload = p.payload || {};
  const amount = formatCurrency(p.amount_cents || 0);

  return `
        <div class="flex flex-col" style="gap:20px; padding: 8px;">
            <!-- Header Section -->
            <div class="flex items-center justify-between" style="padding-bottom:16px;border-bottom:1px solid var(--slate-100);">
                <div>
                     <h3 class="font-bold text-black" style="font-size:22px;letter-spacing:-0.02em;">Financial Settlement Analysis #${p.id}</h3>
                     <p class="text-sm text-slate-500">Gateway: <span class="font-bold text-black uppercase">${escapeHtml(p.gateway)}</span> • Reference: <span class="font-mono text-xs">${escapeHtml(p.transaction_id || 'N/A')}</span></p>
                </div>
                <div class="flex flex-col items-end" style="gap:6px;">
                    <span class="badge ${getStatusClass(p.status)} uppercase" style="font-size:11px;padding:4px 12px;">${p.status || 'PENDING'}</span>
                    <span class="text-slate-400 font-mono" style="font-size:10px;">${formatDate(p.created_at)}</span>
                </div>
            </div>

            <div class="flex" style="gap:20px;">
                <!-- Left: Settlement Identity -->
                <div style="flex:1.5;display:flex;flex-direction:column;gap:16px;">
                    <div class="google-card" style="padding:20px;background:var(--slate-50);">
                         <h4 class="text-slate-400 font-bold uppercase" style="font-size:9px;letter-spacing:0.1em;margin-bottom:16px;">Verified Displacement</h4>
                         <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="google-card" style="padding:16px;background:white;">
                                <div class="text-slate-400 font-bold uppercase" style="font-size:9px;">Displaced Value</div>
                                <div class="font-black text-black" style="font-size:24px;font-family:monospace;">Rs ${amount}</div>
                                <div class="text-[10px] text-slate-400 uppercase tracking-widest">${p.currency || 'LKR'} SETTLEMENT</div>
                            </div>
                            <div class="google-card" style="padding:16px;background:white;">
                                <div class="text-slate-400 font-bold uppercase" style="font-size:9px;">Parent Order Mapping</div>
                                <div class="font-bold text-black" style="font-size:18px;">Order #${escapeHtml(p.order_number)}</div>
                                <div class="text-[10px] text-slate-400 font-mono mt-1">INTERNAL-REF-${p.order_id}</div>
                            </div>
                         </div>
                    </div>

                    <div class="google-card" style="padding:24px;background:var(--slate-900);color:white;box-shadow:0 10px 30px -10px rgba(0,0,0,0.4);">
                         <h4 class="text-slate-500 font-bold uppercase" style="font-size:9px;letter-spacing:0.1em;margin-bottom:12px;border-bottom:1px solid #334155;padding-bottom:8px;">Protocol Payload Snapshot</h4>
                         <div style="background:#0f172a;padding:16px;border-radius:12px;border:1px solid #1e293b;">
                            <pre style="font-family:monospace;font-size:10px;color:#818cf8;overflow-x:auto;max-height:220px;">${escapeHtml(JSON.stringify(payload, null, 2))}</pre>
                         </div>
                    </div>
                </div>

                <!-- Right: Meta Trace -->
                <div style="flex:1;display:flex;flex-direction:column;gap:16px;">
                    <div class="google-card" style="padding:16px;">
                         <h4 class="text-slate-400 font-bold uppercase" style="font-size:9px;letter-spacing:0.1em;margin-bottom:12px;border-bottom:1px solid var(--slate-100);padding-bottom:4px;">Transaction Actor Profile</h4>
                         <div class="flex items-center" style="gap:12px;">
                             <div class="thumb-md rounded-full bg-slate-50 border flex items-center justify-center text-xl shadow-inner">💳</div>
                             <div>
                                <div class="font-bold text-black" style="font-size:14px;">${escapeHtml(p.user_name || 'Guest Payer')}</div>
                                <div class="text-xs text-slate-500 truncate max-w-[150px] underline">${escapeHtml(p.user_email || 'no-email@payment.trace')}</div>
                             </div>
                         </div>
                    </div>

                    <div class="google-card" style="padding:16px;">
                        <h4 class="text-slate-400 font-bold uppercase" style="font-size:9px;letter-spacing:0.1em;margin-bottom:10px;">Post-Event Audit</h4>
                        <p class="text-[11px] text-slate-600 leading-relaxed italic">Verified by gateway protocol. Any settlement disputes should be cross-referenced with the provider dashboard before manual modification.</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end" style="padding-top:12px;border-top:1px solid var(--slate-100);gap:8px;">
                <button class="btn btn-primary js-edit" data-id="${p.id}" style="padding:0 32px;">✏️ Modify Settlement</button>
            </div>
        </div>`;
}

// ─── Form Builder ─────────────────────────────────────────────────────────────

async function renderFormModal(id = null) {
  const isEdit = id !== null;
  const [deps, p] = await Promise.all([
    fetchDependencies(),
    isEdit ? fetchPayment(id) : Promise.resolve({}),
  ]);

  const frag = getTemplate('tpl-payment-form', {
    id: isEdit ? id : '',
    order_key: p.order_id || '',
    order_number: escapeHtml(p.order_number || ''),
    amount_cents: p.amount_cents || '',
    currency: p.currency || 'LKR',
    gateway: escapeHtml(p.gateway || 'manual'),
    gateway_order_id: escapeHtml(p.gateway_order_id || ''),
    transaction_id: escapeHtml(p.transaction_id || ''),
    payload_json: p.payload ? JSON.stringify(p.payload, null, 2) : '',
    create_only: isEdit ? 'hidden' : '',
    edit_only: isEdit ? '' : 'hidden',
    delete_display: isEdit ? '' : 'hidden',
    submit_text: isEdit ? 'Update Protocol' : 'Authorize Settlement',
  });

  const oSel = frag.querySelector('#pay-order-select');
  if (oSel && !isEdit) {
    oSel.innerHTML =
      '<option value="">Select Target Order Cluster...</option>' +
      deps.orders
        .map(
          (o) => `<option value="${o.id}">#${o.order_number} (${formatDate(o.created_at)})</option>`
        )
        .join('');
  }

  if (isEdit) {
    const gatewaySel = frag.querySelector('#pay-gateway-select');
    const statusSel = frag.querySelector('#pay-status-select');
    if (gatewaySel) gatewaySel.value = p.gateway || 'manual';
    if (statusSel) statusSel.value = p.status || 'pending';

    const footer = frag.querySelector('.flex.justify-end.gap-3.pt-6');
    if (footer) {
      const del = document.createElement('button');
      del.type = 'button';
      del.className = 'btn btn-outline text-danger mr-auto js-delete-btn';
      del.innerHTML = '🗑️ Purge Protocol';
      footer.prepend(del);
    }
  }
  return frag;
}

// ─── Custom Form Handlers ─────────────────────────────────────────────────────

function initFormHandlersOverride(modalRoot, id, onSuccess, closeModalFn, showFormErrorFn) {
  const isEdit = id !== null;
  const form = modalRoot.querySelector('#pay-form');
  if (!form) return;

  const cancel = modalRoot.querySelector('#pay-cancel');
  if (cancel) cancel.addEventListener('click', closeModalFn);

  const delBtn = modalRoot.querySelector('.js-delete-btn');
  if (delBtn) {
    delBtn.addEventListener('click', async () => {
      if (!delBtn.dataset.confirmed) {
        delBtn.dataset.confirmed = '1';
        delBtn.innerHTML = '⚠️ Confirm Purge?';
        delBtn.classList.add('btn-warning');
        setTimeout(() => {
          if (delBtn.isConnected) {
            delete delBtn.dataset.confirmed;
            delBtn.innerHTML = '🗑️ Purge Protocol';
            delBtn.classList.remove('btn-warning');
          }
        }, 3000);
        return;
      }
      delBtn.disabled = true;
      delBtn.innerHTML = 'Purging…';
      try {
        await apiRequest(API_ROUTES.PAYMENTS.DELETE(id), { method: 'DELETE' });
        closeModalFn();
        onSuccess?.(null, 'deleted');
      } catch (err) {
        showFormErrorFn(form, err.message);
        delBtn.disabled = false;
        delBtn.innerHTML = '🗑️ Purge Protocol';
      }
    });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submit = form.querySelector('button[type="submit"]');
    const orig = submit.innerHTML;
    submit.disabled = true;
    submit.innerHTML = isEdit ? 'Syncing…' : 'Authorizing…';
    try {
      const formData = new FormData(form);
      let payloadObj = {};
      const payloadStr = formData.get('payload');
      try {
        if (payloadStr && payloadStr.trim()) payloadObj = JSON.parse(payloadStr);
      } catch (je) {
        throw new Error('Malformed JSON Payload detected.', { cause: je });
      }
      const payload = {
        order_id: parseInt(formData.get('order_id')),
        amount_cents: parseInt(formData.get('amount_cents')),
        currency: formData.get('currency'),
        gateway: formData.get('gateway'),
        status: formData.get('status'),
        gateway_order_id: formData.get('gateway_order_id') || null,
        transaction_id: formData.get('transaction_id') || null,
        payload: payloadObj,
      };
      const url = isEdit ? API_ROUTES.PAYMENTS.UPDATE(id) : API_ROUTES.PAYMENTS.CREATE;
      const res = await apiRequest(url, { method: isEdit ? 'PUT' : 'POST', body: payload });
      if (!isEdit && !res.success) throw new Error(res.message);
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

const { Render: Payments, Init: initPayments } = createEntityModule({
  entityName: 'Payments',
  entitySubtitle: 'Track and manage customer payment records',
  apiRoutes: {
    list: API_ROUTES.PAYMENTS.LIST,
    detail: (id) => API_ROUTES.ADMIN_VIEWS.DETAIL('payments', id),
    create: API_ROUTES.PAYMENTS.CREATE,
    update: (id) => API_ROUTES.PAYMENTS.UPDATE(id),
    delete: (id) => API_ROUTES.PAYMENTS.DELETE(id),
  },
  fetchList: fetchPayments,
  fetchSingle: fetchPayment,
  tableHeaderHtml: `<tr class="tr">
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">ID</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Trace Origin / Order</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Gateway Protocol / Ref</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Disposition</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Settled Value</th>
        <th class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Established</th>
        <th class="px-8 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Actions</th>
    </tr>`,
  renderRow,
  renderViewModal,
  renderFormModal,
  initFormHandlersOverride,
  searchPlaceholder: 'Search by Order #, Gateway, or Trace Reference…',
  createBtnText: '➕ Authorize Settlement',
});

export { Payments, initPayments };
