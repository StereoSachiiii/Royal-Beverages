/**
 * Centralized API route definitions for admin dashboard
 * All requests go through /api/v1/* which is rewritten to /admin/api/index.php via .htaccess
 */

// Base URL - all API requests use the dynamically injected API_BASE_URL or fallback
const BASE_URL = window.ADMIN_CONFIG?.API_BASE_URL
  ? window.ADMIN_CONFIG.API_BASE_URL.replace(/\/$/, '')
  : '/api/v1';

const API_ROUTES = {
  PRODUCTS: {
    LIST: `${BASE_URL}/products`,
    ENRICHED_ALL: `${BASE_URL}/products/enriched/all`,
    GET: (id) => `${BASE_URL}/products/${id}`,
    CREATE: `${BASE_URL}/products`,
    UPDATE: (id) => `${BASE_URL}/products/${id}`,
    DELETE: (id) => `${BASE_URL}/products/${id}`,
    SEARCH: `${BASE_URL}/products/search`,
  },

  USERS: {
    LIST: `${BASE_URL}/users`,
    GET: (id) => `${BASE_URL}/users/${id}`,
    CREATE: `${BASE_URL}/users/register`,
    UPDATE: (id) => `${BASE_URL}/users/${id}`,
    DELETE: (id) => `${BASE_URL}/users/${id}`,
    SEARCH: `${BASE_URL}/users/search`,
  },

  CATEGORIES: {
    LIST: `${BASE_URL}/categories`,
    GET: (id) => `${BASE_URL}/categories/${id}`,
    CREATE: `${BASE_URL}/categories`,
    UPDATE: (id) => `${BASE_URL}/categories/${id}`,
    DELETE: (id) => `${BASE_URL}/categories/${id}`,
  },

  SUPPLIERS: {
    LIST: `${BASE_URL}/suppliers`,
    GET: (id) => `${BASE_URL}/suppliers/${id}`,
    CREATE: `${BASE_URL}/suppliers`,
    UPDATE: (id) => `${BASE_URL}/suppliers/${id}`,
    DELETE: (id) => `${BASE_URL}/suppliers/${id}`,
  },

  ORDERS: {
    LIST: `${BASE_URL}/orders`,
    GET: (id) => `${BASE_URL}/orders/${id}`,
    CREATE: `${BASE_URL}/orders`,
    UPDATE: (id) => `${BASE_URL}/orders/${id}`, // fixed: was missing id interpolation
    DELETE: (id) => `${BASE_URL}/orders/${id}`,
  },

  WAREHOUSES: {
    LIST: `${BASE_URL}/warehouses`,
    GET: (id) => `${BASE_URL}/warehouses/${id}`,
    CREATE: `${BASE_URL}/warehouses`,
    UPDATE: (id) => `${BASE_URL}/warehouses/${id}`,
    DELETE: (id) => `${BASE_URL}/warehouses/${id}`,
  },

  STOCK: {
    LIST: `${BASE_URL}/stock`,
    GET: (id) => `${BASE_URL}/stock/${id}`,
    CREATE: `${BASE_URL}/stock`,
    UPDATE: (id) => `${BASE_URL}/stock/${id}`,
    DELETE: (id) => `${BASE_URL}/stock/${id}`,
  },

  CARTS: {
    LIST: `${BASE_URL}/carts`,
    GET: (id) => `${BASE_URL}/carts/${id}`,
    CREATE: `${BASE_URL}/carts`,
    UPDATE: (id) => `${BASE_URL}/carts/${id}`,
    DELETE: (id) => `${BASE_URL}/carts/${id}`,
  },

  CART_ITEMS: {
    LIST: `${BASE_URL}/cart-items`,
    GET: (id) => `${BASE_URL}/cart-items/${id}`,
    CREATE: `${BASE_URL}/cart-items`,
    UPDATE: (id) => `${BASE_URL}/cart-items/${id}`,
    DELETE: (id) => `${BASE_URL}/cart-items/${id}`,
  },

  ORDER_ITEMS: {
    LIST: `${BASE_URL}/order-items`,
    GET: (id) => `${BASE_URL}/order-items/${id}`,
    CREATE: `${BASE_URL}/order-items`,
    UPDATE: (id) => `${BASE_URL}/order-items/${id}`,
    DELETE: (id) => `${BASE_URL}/order-items/${id}`,
  },

  USER_PREFERENCES: {
    LIST: `${BASE_URL}/user-preferences`,
    GET: (id) => `${BASE_URL}/user-preferences/${id}`,
    CREATE: `${BASE_URL}/user-preferences`,
    UPDATE: (id) => `${BASE_URL}/user-preferences/${id}`,
    DELETE: (id) => `${BASE_URL}/user-preferences/${id}`,
  },

  USER_ADDRESSES: {
    LIST: `${BASE_URL}/addresses`,
    GET: (id) => `${BASE_URL}/addresses/${id}`,
    CREATE: `${BASE_URL}/addresses`,
    UPDATE: (id) => `${BASE_URL}/addresses/${id}`,
    DELETE: (id) => `${BASE_URL}/addresses/${id}`,
    BY_USER: (userId) => `${BASE_URL}/addresses/user/${userId}`,
  },

  FEEDBACK: {
    LIST: `${BASE_URL}/feedback`,
    GET: (id) => `${BASE_URL}/feedback/${id}`,
    CREATE: `${BASE_URL}/feedback`,
    UPDATE: (id) => `${BASE_URL}/feedback/${id}`,
    DELETE: (id) => `${BASE_URL}/feedback/${id}`,
  },

  FLAVOR_PROFILES: {
    LIST: `${BASE_URL}/flavor-profiles`,
    GET: (productId) => `${BASE_URL}/flavor-profiles/product/${productId}`,
    CREATE: `${BASE_URL}/flavor-profiles`,
    UPDATE: (productId) => `${BASE_URL}/flavor-profiles/product/${productId}`,
    DELETE: (productId) => `${BASE_URL}/flavor-profiles/product/${productId}`,
  },

  RECIPE_INGREDIENTS: {
    LIST: `${BASE_URL}/recipe-ingredients`,
    SEARCH: `${BASE_URL}/recipe-ingredients/search`,
    GET: (id) => `${BASE_URL}/recipe-ingredients/${id}`,
    CREATE: `${BASE_URL}/recipe-ingredients`,
    UPDATE: (id) => `${BASE_URL}/recipe-ingredients/${id}`,
    DELETE: (id) => `${BASE_URL}/recipe-ingredients/${id}`,
    BY_RECIPE: (recipeId) => `${BASE_URL}/recipe-ingredients/recipe/${recipeId}`,
  },

  COCKTAIL_RECIPES: {
    LIST: `${BASE_URL}/cocktail-recipes`,
    SEARCH: `${BASE_URL}/cocktail-recipes/search`,
    GET: (id) => `${BASE_URL}/cocktail-recipes?id=${id}`,
    CREATE: `${BASE_URL}/cocktail-recipes`,
    UPDATE: (id) => `${BASE_URL}/cocktail-recipes/${id}`,
    DELETE: (id) => `${BASE_URL}/cocktail-recipes/${id}`,
  },

  PAYMENTS: {
    LIST: `${BASE_URL}/payments`,
    SEARCH: `${BASE_URL}/payments/search`,
    GET: (id) => `${BASE_URL}/payments/${id}`,
    CREATE: `${BASE_URL}/payments`,
    UPDATE: (id) => `${BASE_URL}/payments/${id}`,
    DELETE: (id) => `${BASE_URL}/payments/${id}`,
  },

  // Special endpoints (direct file access, not through router)
  IMAGES: {
    UPLOAD: `${BASE_URL}/images/upload`,
  },

  ADMIN_VIEWS: {
    GET: `${BASE_URL}/admin/views`,
    DASHBOARD: `${BASE_URL}/admin/views/dashboard`,
    LIST: (entity) => `${BASE_URL}/admin/views/${entity}`,
    DETAIL: (entity, id) => `${BASE_URL}/admin/views/${entity}/${id}`,
  },
};

// Helper to construct query strings
export function buildQueryString(params) {
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== null && value !== undefined) {
      query.append(key, String(value));
    }
  });
  return query.toString() ? `?${query.toString()}` : '';
}

// Common query builders
export const queryBuilders = {
  list: (limit = 50, offset = 0) => buildQueryString({ limit, offset }),
  search: (query, limit = 50, offset = 0) => buildQueryString({ search: query, limit, offset }),
  withId: (id) => buildQueryString({ id }),
};

// Build AdminAPI here to avoid circular module imports between admin-api.js and this file
import { apiRequest } from './utils.js';

const AdminAPI = {};

function _getMethod(action) {
  if (action === 'CREATE') return 'POST';
  if (action === 'UPDATE') return 'PUT';
  if (action === 'DELETE') return 'DELETE';
  return 'GET';
}

Object.entries(API_ROUTES).forEach(([modelKey, endpoints]) => {
  const model = modelKey.toLowerCase().replace(/_([a-z])/g, (g) => g[1].toUpperCase());
  AdminAPI[model] = {};

  Object.entries(endpoints).forEach(([actionKey, pathOrFn]) => {
    const action = actionKey.toLowerCase().replace(/_([a-z])/g, (g) => g[1].toUpperCase());
    const method = _getMethod(actionKey);

    AdminAPI[model][action] = async (...args) => {
      let url;
      let payload = null;
      if (typeof pathOrFn === 'function') {
        const urlArg = args.shift();
        url = pathOrFn(urlArg);
      } else {
        url = pathOrFn;
      }

      if (args.length > 0) payload = args.shift();

      const options = { method };

      if (payload) {
        if (method === 'GET' || method === 'DELETE') {
          if (typeof payload === 'object' && payload !== null && !(payload instanceof FormData)) {
            url += buildQueryString(payload);
          }
        } else {
          options.body = payload;
        }
      }

      return await apiRequest(url, options);
    };
  });
});

// Expose globally for non-module inline scripts
window.AdminAPI = AdminAPI;

export { API_ROUTES, AdminAPI };
