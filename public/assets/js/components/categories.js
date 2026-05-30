/**
 * Categories Component JavaScript
 * Extracted from components/categories.php
 * API CALLS DISABLED FOR UI TESTING
 */

// import api from '../api-mock.js';  // DISABLED - No API calls for now

/**
 * Fetch all categories
 * DISABLED - No API calls for now
 */
const fetchCategories = async () => {
  // try {
  //     const response = await api.get('categories');
  //     return response.success ? response.data : [];
  // } catch (error) {
  //     console.warn("Failed to load categories");
  //     return [];
  // }
  return [];
};

/**
 * Fetch single category
 * DISABLED - No API calls for now
 */
const fetchCategory = async (id) => {
  return null;
};

/**
 * Render category card
 */
const renderCard = (cat) => {
  const hasImage = cat.image_url && cat.image_url.trim() && !cat.image_url.includes('null');
  const imageHtml = hasImage
    ? `<img src="${cat.image_url}" alt="${cat.name}" class="category-image" loading="lazy">`
    : '';

  const badge =
    cat.is_active !== false
      ? '<span class="category-badge active">Active</span>'
      : '<span class="category-badge inactive">Inactive</span>';

  return `
        <div class="category-card">
            <div class="category-image-container">
                ${imageHtml}
                ${badge}
            </div>
            <div class="category-info">
                <h3 class="category-name">${cat.name}</h3>
                <p class="category-description">${cat.description || 'No description available'}</p>
                <div class="category-actions">
                    <a href="category.php?id=${cat.id}" target="_blank" id="browse">
                        Browse Available Products
                    </a>
                    <button class="btn-details btn-details-category" data-id="${cat.id}">
                        View Details
                    </button>
                </div>
            </div>
        </div>
    `;
};

/**
 * Render modal detail
 */
const renderDetail = (cat) => {
  if (!cat) {
    return `<div style="text-align:center;padding:80px;color:#e74c3c;font-size:1.5rem;">Failed to load category</div>`;
  }

  const hasImage = cat.image_url && cat.image_url.trim() && !cat.image_url.includes('null');
  const imageHtml = hasImage
    ? `<img src="${cat.image_url}" alt="${cat.name}" class="detail-image">`
    : '<div class="no-image-placeholder">No Image Available</div>';

  const status =
    cat.is_active !== false
      ? { text: 'Active', color: '#4CAF50' }
      : { text: 'Inactive', color: '#e74c3c' };

  const created = cat.created_at ? new Date(cat.created_at).toLocaleDateString() : '—';
  const updated = cat.updated_at ? new Date(cat.updated_at).toLocaleDateString() : '—';

  return `
        <div class="detail-wrapper">
            <button class="close-modal">×</button>
            <div class="detail-image-box">${imageHtml}</div>
            <div class="detail-content">
                <h1 class="detail-title">${cat.name}</h1>
                <div class="detail-meta">
                    <span>ID: #${cat.id}</span>
                    <span style="color:${status.color};font-weight:600;">${status.text}</span>
                </div>
                <h3>Description</h3>
                <p>${cat.description || 'No description provided.'}</p>
                <h3>Info</h3>
                <p><strong>Created:</strong> ${created}</p>
                <p><strong>Updated:</strong> ${updated}</p>
            </div>
        </div>
    `;
};

/**
 * Initialize categories
 */
const init = async () => {
  const container = document.querySelector('.categories-container');
  const categories = await fetchCategories();

  if (categories.length === 0) {
    container.innerHTML = '<div class="empty-state">No categories available at the moment.</div>';
    return;
  }

  container.innerHTML = categories.map(renderCard).join('');
};

/**
 * Event listeners
 */
document.addEventListener('click', async (e) => {
  const modal = document.getElementById('detail-modal-category');
  const body = document.getElementById('detail-modal-body-category');

  // Open modal
  if (e.target.closest('.btn-details-category')) {
    const id = e.target.closest('.btn-details-category').dataset.id;
    modal.classList.add('active');
    body.innerHTML = '<div style="text-align:center;padding:60px;">Loading...</div>';
    const cat = await fetchCategory(id);
    body.innerHTML = renderDetail(cat);
  }

  // Close modal
  if (e.target.closest('.close-modal') || e.target === modal) {
    modal.classList.remove('active');
  }
});

// Close on Escape
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.getElementById('detail-modal-category')?.classList.remove('active');
  }
});

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
