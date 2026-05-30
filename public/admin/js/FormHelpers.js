import { escapeHtml } from './utils.js';
import { API_ROUTES } from './dashboard.routes.js';

/**
 * uploadImage — Transmission utility for binary assets.
 * Used across Products, Categories, and User profiles.
 */
export async function uploadImage(file, entity) {
  const formData = new FormData();
  formData.append('image', file);
  formData.append('entity', entity);

  const headers = {};
  const csrfToken = window.ADMIN_CONFIG?.CSRF_TOKEN;
  if (csrfToken) {
    headers['X-CSRF-Token'] = csrfToken;
  }

  const response = await fetch(API_ROUTES.IMAGES.UPLOAD, {
    method: 'POST',
    credentials: 'same-origin',
    headers,
    body: formData,
  });

  const result = await response.json();
  if (!result.success) throw new Error(result.message || 'Image transmission failed');
  return result.data.url;
}

/**
 * initImageUpload — Interactive asset binder.
 * Sets up a file input to automatically upload and trigger a callback with the resulting URL.
 */
export function initImageUpload(container, entity, inputId, onUploadSuccess) {
  const input = container.querySelector(`#${inputId}`);
  if (!input) return;

  input.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    // Visual feedback
    const originalLabel = input.labels?.[0]?.innerHTML;
    if (input.labels?.[0]) input.labels[0].innerHTML = '⚡ Transmitting...';

    try {
      const url = await uploadImage(file, entity);
      if (onUploadSuccess) onUploadSuccess(url);
    } catch (error) {
      console.error('[FormHelpers] Upload error:', error);
      alert(`Asset Transmission Failed: ${error.message}`);
    } finally {
      if (input.labels?.[0]) input.labels[0].innerHTML = originalLabel || 'Upload Image';
    }
  });
}

/**
 * initSearchableSelect — Interactive search binder.
 * Attaches debounced search behavior to containers with .searchable-select-container.
 */
export function initSearchableSelect(container, options = {}) {
  if (container.classList.contains('searchable-select-container')) {
    _initSingleSearchableSelect(container, options);
  } else {
    container.querySelectorAll('.searchable-select-container').forEach((wrapper) => {
      _initSingleSearchableSelect(wrapper, options);
    });
  }
}

function _initSingleSearchableSelect(wrapper, options) {
  const { searchUrlBuilder, itemRenderer, onSelect } = options;
  if (!searchUrlBuilder) return;

  const searchInput = wrapper.querySelector('.search-input');
  const valueInput = wrapper.querySelector('.value-input');
  const resultsContainer = wrapper.querySelector('.search-results');
  const selectionDisplay = wrapper.querySelector('.selection-display');
  const selectedText = wrapper.querySelector('.selected-text');
  const clearBtn = wrapper.querySelector('.clear-selection');

  if (!searchInput || !valueInput) return;

  let debounceTimer;

  searchInput.addEventListener('input', (e) => {
    const query = e.target.value.trim();
    clearTimeout(debounceTimer);

    if (query.length < 2) {
      resultsContainer.classList.add('hidden');
      resultsContainer.innerHTML = '';
      return;
    }

    debounceTimer = setTimeout(async () => {
      try {
        resultsContainer.innerHTML =
          '<div class="p-2 text-slate-400 text-xs italic">Scanning directory...</div>';
        resultsContainer.classList.remove('hidden');

        const response = await fetch(searchUrlBuilder(query));
        const data = await response.json();
        const items = data.data || (Array.isArray(data) ? data : []);

        if (items.length === 0) {
          resultsContainer.innerHTML =
            '<div class="p-2 text-slate-400 text-xs text-center">No trace discovered</div>';
          return;
        }

        resultsContainer.innerHTML = '';
        items.forEach((item) => {
          const div = document.createElement('div');
          div.className =
            'p-2 hover:bg-slate-50 cursor-pointer border-b last:border-b-0 border-slate-100 transition-colors';
          div.innerHTML = itemRenderer ? itemRenderer(item) : item.name || item.id;
          div.addEventListener('click', () => {
            valueInput.value = item.id;
            selectedText.textContent = itemRenderer
              ? itemRenderer(item).replace(/<[^>]*>/g, '')
              : item.name || item.id;
            selectionDisplay.classList.remove('hidden');
            searchInput.classList.add('hidden');
            resultsContainer.classList.add('hidden');
            if (onSelect) onSelect(item);
          });
          resultsContainer.appendChild(div);
        });
      } catch (error) {
        console.error('[FormHelpers] Search error:', error);
        resultsContainer.innerHTML = `<div class="p-2 text-danger text-xs">Error: ${escapeHtml(error.message)}</div>`;
      }
    }, 300);
  });

  clearBtn?.addEventListener('click', () => {
    valueInput.value = '';
    selectedText.textContent = '';
    searchInput.value = '';
    selectionDisplay.classList.add('hidden');
    searchInput.classList.remove('hidden');
    searchInput.focus();
  });

  document.addEventListener('click', (e) => {
    if (!wrapper.contains(e.target)) resultsContainer.classList.add('hidden');
  });
}
