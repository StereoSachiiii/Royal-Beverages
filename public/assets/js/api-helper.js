/**
 * Royal Beverages - Centralized API Helper
 * All public frontend API calls should use this module
 */

// Dynamically resolve API base from injected configuration or fallback to root-relative
// Dynamically resolve API base from injected configuration or fallback to relative path
const getApiBase = () => {
  // Priority 1: Use the centrally defined URL from window.ROYAL_CONFIG (injected via header.php)
  if (window.ROYAL_CONFIG?.API_BASE_URL) {
    let base = window.ROYAL_CONFIG.API_BASE_URL.replace(/\/$/, '');
    // Only fix double slashes in the path part, not the protocol
    if (base.includes('://')) {
      const parts = base.split('://');
      base = parts[0] + '://' + parts[1].replace(/\/+/g, '/');
    } else {
      base = base.replace(/\/+/g, '/');
    }
    return base;
  }

  // Priority 2: In XAMPP/local development or when public is in the path
  const path = window.location.pathname;
  const publicMatch = path.match(/(.*\/public)\//);
  if (publicMatch) {
    return `${publicMatch[1]}/api/v1`;
  }

  // Default Fallback for root-level projects
  return '/api/v1';
};

const API_BASE_URL = getApiBase();

/**
 * Build query string from params object
 */
function buildQuery(params = {}) {
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      query.append(key, value);
    }
  });
  const str = query.toString();
  return str ? '?' + str : '';
}

/**
 * Get CSRF token from meta tag or cookie
 */
function getCsrfToken() {
  // Check meta tag first
  const meta = document.querySelector('meta[name="csrf-token"]');
  if (meta) return meta.getAttribute('content');

  // Check cookie as fallback
  const match = document.cookie.match(/csrf_token=([^;]+)/);
  return match ? match[1] : '';
}

/**
 * Centralized API request function
 * @param {string} endpoint - API endpoint (without base URL)
 * @param {object} options - Fetch options
 * @returns {Promise<object>} API response
 */
export async function apiRequest(endpoint, options = {}) {
  const url = API_BASE_URL + endpoint;

  const headers = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...options.headers,
  };

  // Add CSRF token for mutating requests
  if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(options.method?.toUpperCase())) {
    headers['X-CSRF-Token'] = getCsrfToken();
  }

  const config = {
    method: options.method || 'GET',
    headers,
    credentials: 'include', // Include cookies for session auth
    ...options,
  };

  if (options.body && typeof options.body === 'object') {
    config.body = JSON.stringify(options.body);
  }

  try {
    const response = await fetch(url, config);

    // Check if response is JSON
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      const text = await response.text();
      throw new Error('Server returned non-JSON response');
    }

    const data = await response.json();

    if (!data.success && data.message) {
      throw data;
    }

    return data;
  } catch (error) {
    console.error(`[API] ${options.method || 'GET'} ${endpoint} failed:`, error);
    throw error;
  }
}

/**
 * Centralized API module
 */
export const API = {
  baseUrl: API_BASE_URL,
  request: apiRequest,
  buildQuery,

  // ==================== PRODUCTS ====================
  products: {
    list: (params = {}) => apiRequest('/products' + buildQuery(params)),
    search: (query, params = {}) =>
      apiRequest('/products/search' + buildQuery({ search: query, ...params })),
    get: (id) => apiRequest('/products/' + id),
    getByCategory: (categoryId, params = {}) =>
      apiRequest('/products' + buildQuery({ category_id: categoryId, ...params })),
    featured: (params = {}) => apiRequest('/products' + buildQuery({ featured: true, ...params })),
  },

  // ==================== CATEGORIES ====================
  categories: {
    list: (params = {}) => apiRequest('/categories' + buildQuery(params)),
    get: (id) => apiRequest('/categories/' + id),
    getWithProducts: (id) => apiRequest('/categories/' + id + '/products'),
  },

  // ==================== CART ====================
  cart: {
    get: (cartId) => apiRequest('/carts/' + cartId),
    create: (data) => apiRequest('/carts', { method: 'POST', body: data }),
    update: (id, data) => apiRequest('/carts/' + id, { method: 'PUT', body: data }),
    delete: (id) => apiRequest('/carts/' + id, { method: 'DELETE' }),

    // User/Frontend Safe Cart Operations
    getByUser: (userId) => apiRequest('/carts/by-user/' + userId),
    getBySession: (sessionId) => apiRequest('/carts/by-session/' + sessionId),

    // Cart Items
    getItems: (cartId) => apiRequest('/cart-items/cart/' + cartId),
    addItem: (data) => apiRequest('/cart-items', { method: 'POST', body: data }),
    updateItem: (id, data) => apiRequest('/cart-items/' + id, { method: 'PUT', body: data }),
    removeItem: (id) => apiRequest('/cart-items/' + id, { method: 'DELETE' }),
  },

  // ==================== ORDERS ====================
  orders: {
    list: (params = {}) => apiRequest('/orders' + buildQuery(params)),
    get: (id) => apiRequest('/orders/' + id),
    create: (data) => apiRequest('/orders', { method: 'POST', body: data }),
    getByUser: (userId, params = {}) =>
      apiRequest('/orders/by-user/' + userId + buildQuery(params)),

    // Order Items
    getItems: (orderId) => apiRequest('/order-items' + buildQuery({ order_id: orderId })),
  },

  // ==================== PAYMENTS ====================
  payments: {
    create: (data) => apiRequest('/payments', { method: 'POST', body: data }),
    get: (id) => apiRequest('/payments/' + id),
    getByOrder: (orderId) => apiRequest('/payments' + buildQuery({ order_id: orderId })),
    createStripeCheckout: (orderId) => apiRequest('/payments/stripe/checkout-session', { method: 'POST', body: { order_id: orderId } }),
  },

  // ==================== USERS ====================
  users: {
    get: (id) => apiRequest('/users/' + id),
    update: (id, data) => apiRequest('/users/' + id, { method: 'PUT', body: data }),
    getCurrentUser: () => apiRequest('/users/me'),

    // Auth
    login: (credentials) => apiRequest('/users/login', { method: 'POST', body: credentials }),
    register: (data) => apiRequest('/users/register', { method: 'POST', body: data }),
    logout: () => apiRequest('/users/logout', { method: 'POST' }),
  },

  // ==================== ADDRESSES ====================
  addresses: {
    list: (userId) => apiRequest('/addresses/user/' + userId),
    get: (id) => apiRequest('/addresses/' + id),
    create: (data) => apiRequest('/addresses', { method: 'POST', body: data }),
    update: (id, data) => apiRequest('/addresses/' + id, { method: 'PUT', body: data }),
    delete: (id) => apiRequest('/addresses/' + id, { method: 'DELETE' }),
    setDefault: (id) => apiRequest('/addresses/' + id + '/default', { method: 'PUT' }),
  },

  // ==================== WISHLIST ====================
  wishlist: {
    get: () => apiRequest('/wishlists'),
    add: (data) => apiRequest('/wishlists', { method: 'POST', body: data }),
    remove: (id) => apiRequest('/wishlists/' + id, { method: 'DELETE' }),
    sync: (productIds) =>
      apiRequest('/wishlists/sync', { method: 'POST', body: { product_ids: productIds } }),
  },

  // ==================== FEEDBACK ====================
  feedback: {
    submit: (data) => apiRequest('/feedback', { method: 'POST', body: data }),
    getByProduct: (productId) => apiRequest('/feedback' + buildQuery({ product_id: productId })),
  },

  // ==================== RECIPES ====================
  recipes: {
    list: (params = {}) => apiRequest('/cocktail-recipes' + buildQuery(params)),
    search: (query, params = {}) =>
      apiRequest('/cocktail-recipes/search' + buildQuery({ search: query, ...params })),
    get: (id) => apiRequest('/cocktail-recipes/' + id),
    getIngredients: (recipeId) =>
      apiRequest('/recipe-ingredients' + buildQuery({ recipe_id: recipeId })),
  },

  // ==================== FLAVOR PROFILES ====================
  flavorProfiles: {
    list: () => apiRequest('/flavour-profiles'),
    get: (id) => apiRequest('/flavour-profiles/' + id),
  },

  // ==================== USER PREFERENCES ====================
  preferences: {
    get: (userId) => apiRequest('/user-preferences' + buildQuery({ user_id: userId })),
    update: (id, data) => apiRequest('/user-preferences/' + id, { method: 'PUT', body: data }),
    create: (data) => apiRequest('/user-preferences', { method: 'POST', body: data }),
  },

  // ==================== RECOMMENDATIONS ====================
  recommendations: {
    previewMatches: (data) =>
      apiRequest('/recommendations/preview-matches', { method: 'POST', body: data }),
  },

  // ==================== STOCK ====================
  stock: {
    getByProduct: (productId) => apiRequest('/stock' + buildQuery({ product_id: productId })),
    checkAvailability: (productId, quantity) =>
      apiRequest('/stock/check' + buildQuery({ product_id: productId, quantity })),
  },
};

// Export for ES modules
export default API;

// Also attach to window for non-module usage and legacy support
if (typeof window !== 'undefined') {
  window.RoyalAPI = API;
  // Compatibility alias if any code still uses the old name
  window.API = API;
}
