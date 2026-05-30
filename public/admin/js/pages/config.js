// Base URL - all API requests use the dynamically injected API_BASE_URL or fallback
const BASE_URL = window.ADMIN_CONFIG?.API_BASE_URL
  ? window.ADMIN_CONFIG.API_BASE_URL.replace(/\/$/, '')
  : '/api/v1';

// These are now legacy - pointing to router routes instead of direct PHP files
export const API_URL = BASE_URL + '/users'; // Legacy fallback
export const API_URL_PRODUCTS = BASE_URL + '/products';
export const API_URL_CATEGORIES = BASE_URL + '/categories';
export const API_URL_ORDERS = BASE_URL + '/orders';
export const API_URL_SUPPLIERS = BASE_URL + '/suppliers';
export const API_URL_WAREHOUSES = BASE_URL + '/warehouses';
export const API_URL_STOCK = BASE_URL + '/stock';
export const API_URL_PAYMENTS = BASE_URL + '/payments';
export const API_URL_USER_PREFERENCES = BASE_URL + '/user-preferences';
export const API_URL_RECIPE_INGREDIENTS = BASE_URL + '/recipe-ingredients';
export const API_URL_CARTS = BASE_URL + '/carts';
export const API_URL_FEEDBACK = BASE_URL + '/feedback';
export const API_URL_FLAVOUR_PROFILES = BASE_URL + '/flavour-profiles';
export const API_URL_CART_ITEMS = BASE_URL + '/cart-items';
export const API_URL_ORDER_ITEMS = BASE_URL + '/order-items';
export const API_URL_USER_ADDRESSES = BASE_URL + '/addresses';
export const API_URL_USERS = BASE_URL + '/users';

export const DETAIL_VIEW_API_URL = BASE_URL + '/admin/views';
