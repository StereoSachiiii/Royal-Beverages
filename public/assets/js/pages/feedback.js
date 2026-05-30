/**
 * Premium Feedback Page JavaScript
 * Handles interactive star ratings, searchable product selection, and AJAX submission.
 */

document.addEventListener('DOMContentLoaded', () => {
  // State
  let selectedProductId = null;
  let selectedRating = 0;
  let products = [];
  const API_BASE_URL = window.ROYAL_CONFIG.API_BASE_URL;

  // DOM Elements
  const feedbackForm = document.getElementById('feedbackForm');
  const productSearchInput = document.getElementById('productSearch');
  const productResults = document.getElementById('productResults');
  const selectedProductDisplay = document.getElementById('selectedProductDisplay');
  const starContainer = document.getElementById('starContainer');
  const commentInput = document.getElementById('comment');
  const submitBtn = document.getElementById('submitFeedback');
  const toastContainer = document.getElementById('toastContainer');
  const feedbackList = document.getElementById('feedbackList');

  // Initialize Star Rating
  const initStars = () => {
    const stars = starContainer.querySelectorAll('.star-btn');
    stars.forEach((star) => {
      star.addEventListener('mouseenter', () => highlightStars(star.dataset.rating));
      star.addEventListener('mouseleave', () => highlightStars(selectedRating));
      star.addEventListener('click', () => {
        selectedRating = parseInt(star.dataset.rating);
        highlightStars(selectedRating);
      });
    });
  };

  const highlightStars = (rating) => {
    const stars = starContainer.querySelectorAll('.star-btn');
    stars.forEach((star) => {
      const starIcon = star.querySelector('svg');
      if (parseInt(star.dataset.rating) <= rating) {
        starIcon.classList.add('text-black');
        starIcon.classList.remove('text-gray-200');
        starIcon.style.fill = 'currentColor';
      } else {
        starIcon.classList.remove('text-black');
        starIcon.classList.add('text-gray-200');
        starIcon.style.fill = 'none';
      }
    });
  };

  // Product Search Logic
  let searchTimeout;
  productSearchInput.addEventListener('input', (e) => {
    const query = e.target.value.trim();
    clearTimeout(searchTimeout);

    if (query.length < 2) {
      productResults.classList.add('hidden');
      return;
    }

    searchTimeout = setTimeout(async () => {
      try {
        const response = await fetch(
          `${API_BASE_URL}/products?search=${encodeURIComponent(query)}&limit=5`
        );
        const result = await response.json();

        if (result.success && result.data) {
          // Handle both direct array and paginated object structures
          const items = Array.isArray(result.data) ? result.data : result.data.items || [];
          renderSearchResults(items);
        } else {
          productResults.innerHTML =
            '<div class="p-4 text-xs text-gray-400 italic">No products found</div>';
          productResults.classList.remove('hidden');
        }
      } catch (error) {
        console.error('Search error:', error);
      }
    }, 300);
  });

  const renderSearchResults = (items) => {
    if (items.length === 0) {
      productResults.innerHTML =
        '<div class="p-4 text-xs text-gray-400 italic">No products found</div>';
    } else {
      productResults.innerHTML = items
        .map(
          (product) => `
                <div class="product-result-item p-4 hover:bg-gray-50 cursor-pointer transition-colors border-b border-gray-50 last:border-0 flex items-center gap-4" data-id="${product.id}" data-name="${product.name}">
                    <img src="${product.image_url || '/placeholder.webp'}" class="w-10 h-10 object-cover border border-gray-100" />
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black uppercase tracking-widest text-black">${product.name}</span>
                        <span class="text-[8px] uppercase tracking-widest text-gray-400">${product.category_name || 'Premium Spirits'}</span>
                    </div>
                </div>
            `
        )
        .join('');

      // Attach listeners to results
      productResults.querySelectorAll('.product-result-item').forEach((item) => {
        item.addEventListener('click', () => {
          selectedProductId = item.dataset.id;
          selectedProductDisplay.innerHTML = `
                        <div class="flex items-center gap-3 p-3 bg-gray-50 border border-black/5 rounded-none animate-premium-fade">
                            <span class="text-[10px] font-black uppercase tracking-widest">${item.dataset.name}</span>
                            <button type="button" id="removeSelectedProduct" class="ml-auto text-gray-400 hover:text-black transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    `;
          productSearchInput.value = '';
          productResults.classList.add('hidden');

          document.getElementById('removeSelectedProduct').onclick = () => {
            selectedProductId = null;
            selectedProductDisplay.innerHTML = '';
          };
        });
      });
    }
    productResults.classList.remove('hidden');
  };

  // Close results when clicking outside
  document.addEventListener('click', (e) => {
    if (!productSearchInput.contains(e.target) && !productResults.contains(e.target)) {
      productResults.classList.add('hidden');
    }
  });

  // Form Submission
  submitBtn.addEventListener('click', async () => {
    const comment = commentInput.value.trim();

    // Basic Validation
    if (!selectedProductId) {
      showToast('Please select a product to review', 'error');
      return;
    }
    if (selectedRating === 0) {
      showToast('Please provide a rating', 'error');
      return;
    }
    if (!comment) {
      showToast('Please enter your thoughts', 'error');
      return;
    }

    // Check login
    const userId = document.body.dataset.userId;
    if (!userId || userId === 'null') {
      showToast('Please log in to submit reviews', 'error');
      return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="animate-pulse">Sending...</span>';

    try {
      const response = await fetch(`${API_BASE_URL}/feedback`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({
          product_id: parseInt(selectedProductId),
          user_id: parseInt(userId),
          rating: selectedRating,
          comment: comment,
        }),
      });

      const result = await response.json();

      if (result.success) {
        showToast('Review submitted successfully.', 'success');
        resetForm();
      } else {
        showToast(result.message || 'Failed to submit review', 'error');
      }
    } catch (error) {
      console.error('Submission error:', error);
      showToast('An error occurred. Please try again later.', 'error');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'Submit Review';
    }
  });

  const resetForm = () => {
    selectedProductId = null;
    selectedRating = 0;
    selectedProductDisplay.innerHTML = '';
    commentInput.value = '';
    productSearchInput.value = '';
    highlightStars(0);
  };

  // Toast Notification System
  const showToast = (message, type = 'success') => {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-10 right-10 p-6 bg-white border border-black shadow-2xl z-[2000] animate-premium-slide-in flex items-center gap-4`;

    const bgColor = type === 'success' ? 'bg-black' : 'bg-red-600';
    toast.innerHTML = `
            <div class="w-2 h-2 rounded-full ${bgColor}"></div>
            <span class="text-[10px] font-black uppercase tracking-[0.2em]">${message}</span>
        `;

    toastContainer.appendChild(toast);

    setTimeout(() => {
      toast.classList.add('animate-premium-fade-out');
      setTimeout(() => toast.remove(), 500);
    }, 4000);
  };

  // Load Initial Feedback (recent list)
  const loadRecentFeedback = async () => {
    try {
      const response = await fetch(`${API_BASE_URL}/feedback?limit=12&isActive=true&details=true`);
      const result = await response.json();

      if (result.success && result.data) {
        renderFeedbackList(result.data);
      }
    } catch (error) {
      console.error('Load feedback error:', error);
    }
  };

  const renderFeedbackList = (items) => {
    if (!feedbackList) return;

    feedbackList.innerHTML = items
      .map(
        (item) => `
            <div class="p-10 bg-gray-50/50 border border-gray-100 group hover:bg-white hover:shadow-3xl transition-all duration-700">
                <div class="flex justify-between items-start mb-8">
                    <div class="flex gap-1">
                        ${Array(5)
                          .fill(0)
                          .map(
                            (_, i) => `
                            <span class="text-[10px] ${i < item.rating ? 'text-black' : 'text-gray-200'}">★</span>
                        `
                          )
                          .join('')}
                    </div>
                    <span class="text-[8px] font-black uppercase tracking-widest text-gray-300">
                        ${new Date(item.created_at).toLocaleDateString()}
                    </span>
                </div>
                
                <p class="text-sm text-black italic font-light leading-relaxed mb-8 line-clamp-4">"${item.comment}"</p>
                
                <div class="flex items-center gap-4 mt-auto pt-6 border-t border-gray-50">
                    <div class="w-8 h-8 bg-black text-white flex items-center justify-center font-bold text-[8px] uppercase">
                        ${item.user_name ? item.user_name.substring(0, 2).toUpperCase() : 'G'}
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[9px] font-black uppercase tracking-widest text-black">${item.user_name || 'Verified Guest'}</span>
                        <span class="text-[8px] text-gray-400 font-bold tracking-widest uppercase truncate max-w-[150px]">${item.product_name}</span>
                    </div>
                </div>
            </div>
        `
      )
      .join('');
  };

  // Initializations
  initStars();
  loadRecentFeedback();

  // Auto-scroll to reviews right as the hero section transitions to the left (1000ms after load)
  setTimeout(() => {
    const reviewsColumn = document.getElementById('reviewsColumn');
    if (reviewsColumn) {
      reviewsColumn.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }, 1000);
});
