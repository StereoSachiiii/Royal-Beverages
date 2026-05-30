/**
 * Search Module - AJAX Autocomplete
 * Modern Google-style search with real-time suggestions
 * Uses centralized API helper for real backend calls
 */

import { API } from './api-helper.js';

// Cache for search results
let productsCache = null;
let categoriesCache = null;
let selectedIndex = -1;
let debounceTimer = null;

// DOM Elements (initialized on DOMContentLoaded)
let searchInput, searchWrapper, autocomplete, productResults;
let categoryResults, noResults, viewAllResults;
let autocompleteProducts, autocompleteCategories;

/**
 * Initialize search functionality
 */
export const initSearch = async () => {
    // Get DOM elements
    searchInput = document.getElementById('searchInput');
    searchWrapper = document.querySelector('.search-wrapper');
    autocomplete = document.getElementById('searchAutocomplete');
    productResults = document.getElementById('productResults');
    categoryResults = document.getElementById('categoryResults');
    noResults = document.getElementById('noResults');
    viewAllResults = document.getElementById('viewAllResults');
    autocompleteProducts = document.getElementById('autocompleteProducts');
    autocompleteCategories = document.getElementById('autocompleteCategories');

    if (!searchInput) return;

    // Preload data for faster search
    await preloadData();

    // Event listeners
    searchInput.addEventListener('input', handleSearchInput);
    searchInput.addEventListener('keydown', handleKeyNavigation);
    searchInput.addEventListener('focus', handleSearchFocus);

    // Close autocomplete on outside click
    document.addEventListener('click', (e) => {
        if (!searchWrapper?.contains(e.target)) {
            hideAutocomplete();
        }
    });

};

/**
 * Preload products and categories for instant search
 */
const preloadData = async () => {
    try {
        const [productsRes, categoriesRes] = await Promise.all([
            API.request('/products/enriched/all' + API.buildQuery({ limit: 100 })),
            API.categories.list()
        ]);

        productsCache = productsRes.success
            ? (productsRes.data?.items || productsRes.data || [])
            : [];
        categoriesCache = categoriesRes.success
            ? (categoriesRes.data?.items || categoriesRes.data || [])
            : [];
    } catch (error) {
        console.error('[Search] Failed to preload data:', error);
        productsCache = [];
        categoriesCache = [];
    }
};

/**
 * Handle search input with debounce
 */
const handleSearchInput = (e) => {
    const query = e.target.value.trim().toLowerCase();

    // Clear previous timer
    clearTimeout(debounceTimer);

    if (query.length < 2) {
        hideAutocomplete();
        return;
    }

    // Debounce search (150ms)
    debounceTimer = setTimeout(() => {
        performSearch(query);
    }, 150);
};

/**
 * Perform search and display results
 */
const performSearch = (query) => {
    // Search products
    const matchedProducts = productsCache
        .filter(p =>
            p.name?.toLowerCase().includes(query) ||
            p.description?.toLowerCase().includes(query) ||
            p.category_name?.toLowerCase().includes(query)
        )
        .slice(0, 5);

    // Search categories
    const matchedCategories = categoriesCache
        .filter(c => c.name?.toLowerCase().includes(query))
        .slice(0, 3);

    renderResults(matchedProducts, matchedCategories, query);
};

/**
 * Render search results in autocomplete dropdown
 */
const renderResults = (products, categories, query) => {
    selectedIndex = -1;

    // Render products
    if (products.length > 0 && autocompleteProducts) {
        autocompleteProducts.style.display = 'block';
        productResults.innerHTML = products.map((p, i) => `
            <a href="product.php?id=${p.id}" class="autocomplete-item" data-index="${i}">
                <img src="${p.image_url ? (window.ROYAL_CONFIG.ASSET_URL + 'images/products/' + p.image_url.split('/').pop()) : (window.ROYAL_CONFIG.ASSET_URL + 'images/placeholder-product.webp')}" alt="${p.name}" class="autocomplete-img">
                <div class="autocomplete-info">
                    <span class="autocomplete-name">${highlightMatch(p.name, query)}</span>
                    <span class="autocomplete-meta">${p.category_name || 'Spirits'}</span>
                </div>
                <span class="autocomplete-price">Rs. ${(p.price_cents / 100).toFixed(2)}</span>
            </a>
        `).join('');
    } else if (autocompleteProducts) {
        autocompleteProducts.style.display = 'none';
    }

    // Render categories
    if (categories.length > 0 && autocompleteCategories) {
        autocompleteCategories.style.display = 'block';
        categoryResults.innerHTML = categories.map((c, i) => `
            <a href="category.php?id=${c.id}" class="autocomplete-item category-item" data-index="${products.length + i}">
                <div class="category-icon">📁</div>
                <span class="autocomplete-name">${highlightMatch(c.name, query)}</span>
                <span class="autocomplete-count">${c.product_count || ''} products</span>
            </a>
        `).join('');
    } else if (autocompleteCategories) {
        autocompleteCategories.style.display = 'none';
    }

    // Show/hide no results
    if (noResults && viewAllResults) {
        if (products.length === 0 && categories.length === 0) {
            noResults.style.display = 'block';
            viewAllResults.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            viewAllResults.style.display = 'block';
            viewAllResults.href = `search.php?q=${encodeURIComponent(query)}`;
        }
    }

    showAutocomplete();
};

/**
 * Highlight matching text in results
 */
const highlightMatch = (text, query) => {
    if (!text) return '';
    const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
    return text.replace(regex, '<mark>$1</mark>');
};

/**
 * Escape regex special characters
 */
const escapeRegex = (string) => {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
};

/**
 * Handle keyboard navigation
 */
const handleKeyNavigation = (e) => {
    const items = autocomplete?.querySelectorAll('.autocomplete-item');
    if (!items || items.length === 0) return;

    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
            updateSelection(items);
            break;

        case 'ArrowUp':
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            updateSelection(items);
            break;

        case 'Enter':
            e.preventDefault();
            if (selectedIndex >= 0 && items[selectedIndex]) {
                items[selectedIndex].click();
            } else if (searchInput.value.trim()) {
                // Go to search results page
                window.location.href = `search.php?q=${encodeURIComponent(searchInput.value.trim())}`;
            }
            break;

        case 'Escape':
            hideAutocomplete();
            searchInput.blur();
            break;
    }
};

/**
 * Update visual selection state
 */
const updateSelection = (items) => {
    items.forEach((item, i) => {
        item.classList.toggle('selected', i === selectedIndex);
    });

    // Scroll selected item into view
    if (selectedIndex >= 0 && items[selectedIndex]) {
        items[selectedIndex].scrollIntoView({ block: 'nearest' });
    }
};

/**
 * Handle search input focus
 */
const handleSearchFocus = () => {
    if (searchInput.value.length >= 2) {
        performSearch(searchInput.value.trim().toLowerCase());
    }
};

/**
 * Show autocomplete dropdown
 */
const showAutocomplete = () => {
    if (autocomplete) {
        autocomplete.classList.add('active');
    }
};

/**
 * Hide autocomplete dropdown
 */
const hideAutocomplete = () => {
    if (autocomplete) {
        autocomplete.classList.remove('active');
    }
    selectedIndex = -1;
};

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initSearch);
