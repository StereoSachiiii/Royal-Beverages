/**
 * EntityBuilder.js
 *
 * Abstract factory for creating standardized Admin CRUD data tables.
 * Eliminates 90% of boilerplate code across the 17 entity pages.
 */

import {
  apiRequest,
  debounce,
  saveState,
  getState,
  openStandardModal,
  closeModal,
  getTemplate,
  getFormData,
  escapeHtml,
  UndoManager,
} from '../utils.js';
import { initImageUpload } from '../FormHelpers.js';

export function createEntityModule(config) {
  const {
    entityName,
    entitySubtitle = '',
    apiRoutes, // { list, detail, create, update, delete } or custom fetch functions
    renderRow,
    renderViewModal,
    renderFormModal,
    tableHeaderHtml,
    defaultLimit = 20,
    emptyMessage = 'No records found.',
    searchPlaceholder = 'Search...',
    sortOptions = [],
    hasCreate = true,
    hideCreateBtn = false, // alias: explicitly hide create btn
    createBtnText = '', // override create button label
    extraContainerHandlers = null, // fn(container, reload) for entity-specific events
  } = config;

  // Resolve actual create visibility
  const _showCreate = hasCreate && !hideCreateBtn;

  const stateKeyQuery = `admin:${entityName.toLowerCase()}:query`;
  let _offset = 0;
  let _query = getState(stateKeyQuery, '');
  let _lastResults = [];

  // --- API Wrappers ---
  async function fetchList(limit, offset, query) {
    if (config.fetchList) return await config.fetchList(limit, offset, query);
    try {
      const qs = `?limit=${limit}&offset=${offset}${query ? `&search=${encodeURIComponent(query)}` : ''}`;
      const res = await apiRequest(apiRoutes.list + qs);
      if (!res.success) throw new Error(res.message || `Failed to fetch ${entityName}`);
      return res.data?.items || (Array.isArray(res.data) ? res.data : []);
    } catch (err) {
      console.error(`[${entityName}] Fetch failed`, err);
      return [];
    }
  }

  async function fetchSingle(id) {
    if (config.fetchSingle) return await config.fetchSingle(id);
    try {
      const res = await apiRequest(
        typeof apiRoutes.detail === 'function'
          ? apiRoutes.detail(id)
          : apiRoutes.detail.replace(':id', id)
      );
      if (!res.success) throw new Error(res.message || `Failed to fetch ${entityName}`);
      return res.data;
    } catch (err) {
      throw err;
    }
  }

  // --- UI Helpers ---
  function emptyRow(msg) {
    return `<tr class="tr"><td colspan="20" class="td text-center text-slate-500" style="padding:48px;">${escapeHtml(msg)}</td></tr>`;
  }

  function showFormError(form, msg) {
    let el = form.querySelector('.form-error-banner');
    if (!el) {
      el = Object.assign(document.createElement('div'), { className: 'form-error-banner' });
      form.prepend(el);
    }
    el.textContent = `Error: ${msg}`;
    el.style.display = 'block';
  }

  function redrawTable(container, list) {
    container.querySelector('#entity-tbody').innerHTML = list.length
      ? list.map(renderRow).join('')
      : emptyRow(emptyMessage);
    const lmc = container.querySelector('#entity-load-more-container');
    if (list.length === defaultLimit) {
      lmc.style.display = 'flex';
      lmc.innerHTML = `<button id="entity-load-more-btn" class="btn btn-outline" style="padding:0 48px;">Load More</button>`;
    } else {
      lmc.style.display = 'none';
      lmc.innerHTML = '';
    }
  }

  // --- Form Handlers ---
  function initFormHandlers(modalRoot, id, onSuccess) {
    if (config.initFormHandlersOverride) {
      return config.initFormHandlersOverride(modalRoot, id, onSuccess, closeModal, showFormError);
    }

    const isEdit = id !== null;
    const form = modalRoot.querySelector('form');
    if (!form) return;

    // Initialize UndoManager for form state
    new UndoManager(form);

    const cancel = modalRoot.querySelector('.js-cancel');
    if (cancel) cancel.addEventListener('click', () => closeModal());

    // Standard image upload if elements exist
    const imgInput = form.querySelector('input[type="file"]');
    if (imgInput && imgInput.id) {
      const hiddenInput = modalRoot.querySelector(`#${imgInput.id.replace('-file', '-hidden')}`);
      const preview = modalRoot.querySelector(`#${imgInput.id.replace('-file', '-preview')}`);
      initImageUpload(modalRoot, entityName.toLowerCase(), imgInput.id, (url) => {
        if (hiddenInput) hiddenInput.value = url;
        if (preview) {
          preview.src = url;
          preview.style.display = 'block';
        }
        const label = modalRoot.querySelector(`label[for="${imgInput.id}"]`);
        if (label) label.textContent = '✅ Uploaded';
      });
    }

    // Custom delete button in footer
    const delBtn = modalRoot.querySelector('.js-delete-btn');
    if (delBtn && apiRoutes?.delete) {
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
          await apiRequest(
            typeof apiRoutes.delete === 'function'
              ? apiRoutes.delete(id)
              : apiRoutes.delete.replace(':id', id),
            { method: 'DELETE' }
          );
          closeModal();
          onSuccess?.(null, 'deleted');
        } catch (err) {
          showFormError(form, err.message);
          delBtn.disabled = false;
          delBtn.innerHTML = '🗑️ Delete';
          delete delBtn.dataset.confirmed;
        }
      });
    }

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (config.onFormSubmit) {
        return config.onFormSubmit(e, form, id, isEdit, onSuccess, closeModal, showFormError);
      }

      const submit = form.querySelector('button[type="submit"]');
      const orig = submit.innerHTML;
      submit.disabled = true;
      submit.innerHTML = isEdit ? 'Saving…' : 'Creating…';
      try {
        const data = getFormData(form);
        let payload = { ...data };

        // Boolean fixes for common standard forms
        if (form.querySelector('input[name="is_active"]'))
          payload.is_active = data.is_active !== undefined;
        if (form.querySelector('input[name="is_admin"]'))
          payload.is_admin = data.is_admin !== undefined;
        if (config.transformPayload) payload = config.transformPayload(payload, isEdit);

        const url = isEdit
          ? typeof apiRoutes.update === 'function'
            ? apiRoutes.update(id)
            : apiRoutes.update.replace(':id', id)
          : apiRoutes.create;

        const res = await apiRequest(url, { method: isEdit ? 'PUT' : 'POST', body: payload });
        if (!res.success) throw new Error(res.message);
        closeModal();
        onSuccess?.(res.data, isEdit ? 'updated' : 'created');
      } catch (err) {
        showFormError(form, err.message);
        submit.disabled = false;
        submit.innerHTML = orig;
      }
    });
  }

  // --- Main Exported Component ---
  async function Render() {
    _offset = 0;
    const data = await fetchList(defaultLimit, 0, _query);
    _lastResults = Array.isArray(data) ? data : [];
    const rows = _lastResults.length
      ? _lastResults.map(renderRow).join('')
      : emptyRow(emptyMessage);

    const subtitle =
      entitySubtitle || `${_lastResults.length} item${_lastResults.length === 1 ? '' : 's'} found`;
    const frag = getTemplate('tpl-admin-entity', {
      'entity-title': entityName,
      'entity-subtitle': subtitle,
    });

    frag.querySelector('#entity-search').placeholder = searchPlaceholder;
    frag.querySelector('#entity-search').value = _query;

    const sortEl = frag.querySelector('#entity-sort');
    if (sortOptions.length > 0) {
      sortEl.innerHTML = sortOptions
        .map((o) => `<option value="${o.value}">${o.label}</option>`)
        .join('');
      sortEl.style.display = 'block';
    } else {
      sortEl.style.display = 'none';
    }

    const createBtn = frag.querySelector('#entity-create-btn');
    if (_showCreate) {
      createBtn.innerHTML = createBtnText || `➕ New ${entityName.replace(/s$/, '')}`;
    } else {
      createBtn.style.display = 'none';
    }

    frag.querySelector('#entity-thead').innerHTML = tableHeaderHtml;
    frag.querySelector('#entity-tbody').innerHTML = rows;

    const lmc = frag.querySelector('#entity-load-more-container');
    if (_lastResults.length === defaultLimit) {
      lmc.style.display = 'flex';
      lmc.innerHTML = `<button id="entity-load-more-btn" class="btn btn-outline" style="padding:0 48px;">Load More</button>`;
    }

    return frag.firstElementChild.outerHTML;
  }

  // --- Event Initializer ---
  function Init(container) {
    if (!container) return null;
    const ac = new AbortController();
    const signal = ac.signal;

    async function reload() {
      const html = await Render();
      container.innerHTML = html;
      Init(container);
    }

    const performSearch = debounce(async (q) => {
      _query = q;
      saveState(stateKeyQuery, _query);
      _offset = 0;
      const data = await fetchList(defaultLimit, 0, _query);
      _lastResults = Array.isArray(data) ? data : [];
      redrawTable(container, _lastResults);
      const subtitle = container.querySelector('.entity-subtitle');
      if (subtitle)
        subtitle.textContent = `${_lastResults.length} item${_lastResults.length === 1 ? '' : 's'} found`;
    }, 300);

    container.addEventListener(
      'input',
      (e) => {
        if (e.target.id === 'entity-search') performSearch(e.target.value.trim());
      },
      { signal }
    );

    // View
    if (renderViewModal) {
      container.addEventListener(
        'click',
        async (e) => {
          const btn = e.target.closest('.js-view');
          if (!btn || e.target.closest('.modal-overlay')) return;
          try {
            const item = await fetchSingle(btn.dataset.id);
            openStandardModal({
              title: `${entityName.replace(/s$/, '')} Details`,
              bodyHtml: await renderViewModal(item),
              size: 'xl',
            });

            const overlay = document.querySelector('.modal-overlay:last-child');
            overlay?.addEventListener('click', async (me) => {
              const editBtn = me.target.closest('.js-edit');
              if (editBtn && renderFormModal) {
                closeModal();
                setTimeout(async () => {
                  const f = await renderFormModal(editBtn.dataset.id);
                  openStandardModal({
                    title: `Edit ${entityName.replace(/s$/, '')}`,
                    bodyHtml:
                      typeof f === 'string' ? f : f.firstElementChild?.outerHTML || f.outerHTML,
                    size: 'xl',
                  });
                  initFormHandlers(
                    document.querySelector('.modal-overlay:last-child'),
                    editBtn.dataset.id,
                    reload
                  );
                }, 200);
              }
            });
          } catch (err) {
            openStandardModal({
              title: 'Error',
              bodyHtml: `<p class="text-danger" style="padding:12px;">${escapeHtml(err.message)}</p>`,
            });
          }
        },
        { signal }
      );
    }

    // Edit
    if (renderFormModal) {
      container.addEventListener(
        'click',
        async (e) => {
          const btn = e.target.closest('.js-edit');
          if (!btn || e.target.closest('.modal-overlay')) return;
          try {
            const f = await renderFormModal(btn.dataset.id);
            openStandardModal({
              title: `Edit ${entityName.replace(/s$/, '')}`,
              bodyHtml: typeof f === 'string' ? f : f.firstElementChild?.outerHTML || f.outerHTML,
              size: 'xl',
            });
            initFormHandlers(
              document.querySelector('.modal-overlay:last-child'),
              btn.dataset.id,
              reload
            );
          } catch (err) {
            openStandardModal({
              title: 'Error',
              bodyHtml: `<p class="text-danger" style="padding:12px;">${escapeHtml(err.message)}</p>`,
            });
          }
        },
        { signal }
      );
    }

    // Delete inline
    if (apiRoutes?.delete) {
      container.addEventListener(
        'click',
        async (e) => {
          const btn = e.target.closest('.js-delete');
          if (!btn) return;
          const id = btn.dataset.id;
          if (!btn.dataset.confirmed) {
            btn.dataset.confirmed = '1';
            btn.innerHTML = '⚠️';
            btn.style.background = '#fef9c3';
            setTimeout(() => {
              if (btn.isConnected) {
                delete btn.dataset.confirmed;
                btn.innerHTML = '🗑';
                btn.style.background = '';
              }
            }, 3000);
            return;
          }
          btn.disabled = true;
          btn.innerHTML = '…';
          try {
            await apiRequest(
              typeof apiRoutes.delete === 'function'
                ? apiRoutes.delete(id)
                : apiRoutes.delete.replace(':id', id),
              { method: 'DELETE' }
            );
            reload();
          } catch (err) {
            btn.disabled = false;
            btn.innerHTML = '🗑';
            alert('Delete failed: ' + err.message);
          }
        },
        { signal }
      );
    }

    // Create
    if (_showCreate && renderFormModal) {
      container.addEventListener(
        'click',
        async (e) => {
          if (!e.target.closest('#entity-create-btn')) return;
          try {
            const f = await renderFormModal(null);
            openStandardModal({
              title: `Create ${entityName.replace(/s$/, '')}`,
              bodyHtml: typeof f === 'string' ? f : f.firstElementChild?.outerHTML || f.outerHTML,
              size: 'xl',
            });
            initFormHandlers(document.querySelector('.modal-overlay:last-child'), null, reload);
          } catch (err) {
            openStandardModal({
              title: 'Error',
              bodyHtml: `<p class="text-danger" style="padding:12px;">${escapeHtml(err.message)}</p>`,
            });
          }
        },
        { signal }
      );
    }

    // Extra entity-specific handlers (e.g. status toggle)
    if (typeof extraContainerHandlers === 'function') {
      container.addEventListener('click', extraContainerHandlers(container, reload), { signal });
    }

    // Load More
    container.addEventListener(
      'click',
      async (e) => {
        if (e.target.id !== 'entity-load-more-btn') return;
        const btn = e.target;
        btn.disabled = true;
        btn.textContent = 'Loading…';
        _offset += defaultLimit;
        const data = await fetchList(defaultLimit, _offset, _query);
        const list = Array.isArray(data) ? data : [];
        if (!list.length) {
          btn.closest('#entity-load-more-container').style.display = 'none';
          return;
        }
        _lastResults = [..._lastResults, ...list];
        container
          .querySelector('#entity-tbody')
          .insertAdjacentHTML('beforeend', list.map(renderRow).join(''));
        if (list.length < defaultLimit) {
          btn.closest('#entity-load-more-container').style.display = 'none';
        } else {
          btn.disabled = false;
          btn.textContent = 'Load More';
        }
      },
      { signal }
    );

    // Refresh
    container.addEventListener(
      'click',
      async (e) => {
        if (e.target.id !== 'entity-refresh-btn') return;
        e.target.innerHTML = '⌛';
        e.target.disabled = true;
        await reload();
      },
      { signal }
    );

    return { cleanup: () => ac.abort() };
  }

  return { Render, Init };
}
