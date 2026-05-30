/**
 * render.js — Data-driven SPA renderer.
 *
 * Every route is described in ROUTE_MAP. Adding a new page =
 * adding one entry here. No more switch cases.
 *
 * Each domain module must export:
 *   export async function [View](container)  → returns HTML string
 *   export function init[View](container)    → returns { cleanup() }
 */

// ─── Route Registry ──────────────────────────────────────────────────────────
export const ROUTE_MAP = {
  overview: {
    module: () => import('./pages/overview/Overview.js'),
    view: 'Overview',
  },
  products: {
    module: () => import('./pages/Products/Products.js'),
    view: 'Products',
    init: 'initProducts',
  },
  categories: {
    module: () => import('./pages/Categories/Categories.js'),
    view: 'Categories',
    init: 'initCategories',
  },
  users: {
    module: () => import('./pages/Users/Users.js'),
    view: 'Users',
    init: 'initUsers',
  },
  suppliers: {
    module: () => import('./pages/Suppliers/Suppliers.js'),
    view: 'Suppliers',
    init: 'initSuppliers',
  },
  orders: {
    module: () => import('./pages/Orders/Orders.js'),
    view: 'Orders',
    init: 'initOrders',
  },
  'order-items': {
    module: () => import('./pages/OrderItems/OrderItems.js'),
    view: 'OrderItems',
    init: 'initOrderItems',
  },
  stock: {
    module: () => import('./pages/Stock/Stocks.js'),
    view: 'Stock',
    init: 'initStock',
  },
  warehouses: {
    module: () => import('./pages/Warehouses/Warehouses.js'),
    view: 'Warehouses',
    init: 'initWarehouses',
  },
  carts: {
    module: () => import('./pages/Carts/Carts.js'),
    view: 'Carts',
    init: 'initCarts',
  },
  'cart-items': {
    module: () => import('./pages/CartItems/CartItems.js'),
    view: 'CartItems',
    init: 'initCartItems',
  },
  feedback: {
    module: () => import('./pages/Feedback/Feedback.js'),
    view: 'Feedback',
    init: 'initFeedback',
  },
  payments: {
    module: () => import('./pages/Payments/Payments.js'),
    view: 'Payments',
    init: 'initPayments',
  },
  'recipe-ingredients': {
    module: () => import('./pages/RecipeIngredients/RecipeIngredients.js'),
    view: 'RecipeIngredients',
    init: 'initRecipeIngredients',
  },
  'cocktail-recipes': {
    module: () => import('./pages/CocktailRecipes/CocktailRecipes.js'),
    view: 'CocktailRecipes',
    init: 'initCocktailRecipes',
  },
  'flavour-profiles': {
    module: () => import('./pages/FlavourProfiles/FlavourProfiles.js'),
    view: 'FlavourProfiles',
    init: 'initFlavourProfiles',
  },
  'user-addresses': {
    module: () => import('./pages/UserAddresses/UserAddresses.js'),
    view: 'UserAddresses',
    init: 'initUserAddresses',
  },
  'user-preferences': {
    module: () => import('./pages/UserPreferences/UserPreferences.js'),
    view: 'UserPreferences',
    init: 'initUserPreferences',
  },
};

// ─── Active Handler Cleanup ───────────────────────────────────────────────────
let _activeHandler = null;
let _lastRenderId = 0;

function cleanupActiveHandler() {
  if (_activeHandler && typeof _activeHandler.cleanup === 'function') {
    try {
      _activeHandler.cleanup();
    } catch (err) {
      console.warn('[render] Error during handler cleanup:', err);
    }
  }
  _activeHandler = null;
}

// ─── Renderer ─────────────────────────────────────────────────────────────────
export const render = async (path, mainElement) => {
  if (!mainElement) return;

  const page = path || 'overview';
  const renderId = ++_lastRenderId;

  // Cleanup previous page listeners immediately
  cleanupActiveHandler();

  const route = ROUTE_MAP[page] ?? ROUTE_MAP['overview'];

  try {
    // Show skeleton while loading
    mainElement.innerHTML = `
            <div class="flex items-center justify-center h-64 text-slate-400">
                <svg class="animate-spin h-6 w-6 mr-3" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                Loading...
            </div>`;

    const mod = await route.module();

    // If a new render started while we were importing, stop here
    if (renderId !== _lastRenderId) return;

    const viewFn = mod[route.view];
    if (typeof viewFn !== 'function') {
      throw new Error(`Module for '${page}' does not export '${route.view}'`);
    }

    mainElement.innerHTML = await viewFn(mainElement);

    // Re-check after await viewFn
    if (renderId !== _lastRenderId) return;

    // Register listeners.
    if (route.init) {
      const initFn = mod[route.init];
      if (typeof initFn === 'function') {
        const handler = await initFn(mainElement);
        // Final check before assigning global handler
        if (renderId === _lastRenderId) {
          _activeHandler = handler;
        } else {
          // This render was superseded; clean up immediately if it returned one
          if (handler && typeof handler.cleanup === 'function') handler.cleanup();
        }
      }
    } else {
      // Fallback: look for any export ending in 'Listeners'
      const listenerKey = Object.keys(mod).find((k) => k.endsWith('Listeners'));
      if (listenerKey && typeof mod[listenerKey] === 'function') {
        const handler = await mod[listenerKey](mainElement);
        if (renderId === _lastRenderId) {
          _activeHandler = handler;
        } else {
          if (handler && typeof handler.cleanup === 'function') handler.cleanup();
        }
      }
    }
  } catch (err) {
    if (renderId !== _lastRenderId) return;
    console.error(`[render] Failed to load page '${page}':`, err);
    mainElement.innerHTML = `
            <div class="flex flex-col items-center justify-center h-64 gap-4 text-center">
                <div class="text-4xl">⚠️</div>
                <div class="font-bold text-slate-800">Failed to load <code>${page}</code></div>
                <div class="text-sm text-slate-500">${err && err.message ? err.message : 'Check the browser console for details.'}</div>
            </div>`;
  }
};

// ─── Browser Back/Forward ─────────────────────────────────────────────────────
window.addEventListener('popstate', async () => {
  const mainElement = document.querySelector('#content');
  await render(window.location.hash.replace('#', '') || 'overview', mainElement);
});
