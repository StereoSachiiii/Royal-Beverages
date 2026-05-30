/**
 * router.js — SPA navigation controller.
 *
 * Drives the sidebar menu and URL hash from the same ROUTE_MAP
 * used by render.js. Single source of truth for route definitions.
 */

import { render, ROUTE_MAP } from './render.js';
import { saveState, getState } from './utils.js';

const menuElement = document.querySelector('.sidebar-menu');
const breadcrumbElement = document.querySelector('#breadcrumb');
const mainElement = document.querySelector('#content');

// ─── Human-readable labels (optional override, falls back to route key) ───────
const ROUTE_LABELS = {
  overview: 'Overview',
  products: 'Products',
  categories: 'Categories',
  users: 'Users',
  suppliers: 'Suppliers',
  orders: 'Orders',
  'order-items': 'Order Items',
  stock: 'Stock',
  warehouses: 'Warehouses',
  carts: 'Carts',
  'cart-items': 'Cart Items',
  feedback: 'Feedback',
  payments: 'Payments',
  'recipe-ingredients': 'Recipe Ingredients',
  'cocktail-recipes': 'Cocktail Recipes',
  'flavour-profiles': 'Flavour Profiles',
  'user-addresses': 'User Addresses',
  'user-preferences': 'User Preferences',
};

// ─── State ─────────────────────────────────────────────────────────────────────
let currentPage = '';

// ─── Navigation ───────────────────────────────────────────────────────────────
const navigate = (pagePath, triggerRender = true) => {
  if (!pagePath) return;

  currentPage = pagePath;
  saveState('admin:lastPage', currentPage);

  updateBreadcrumb(currentPage);
  updateActiveLink(currentPage);

  if (triggerRender) {
    render(currentPage, mainElement);
    if (window.location.hash !== `#${currentPage}`) {
      history.pushState({}, '', `#${currentPage}`);
    }
  }
};

// ─── Breadcrumb ───────────────────────────────────────────────────────────────
const updateBreadcrumb = (page) => {
  const label = ROUTE_LABELS[page] || page;
  if (breadcrumbElement) {
    breadcrumbElement.innerHTML = `
            <span class="text-gray-300">ADMIN</span>
            <span class="mx-2 text-gray-200">/</span>
            <span class="text-gray-300">DASHBOARD</span>
            <span class="mx-2 text-gray-200">/</span>
            <span class="text-black uppercase">${label.replace(/-/g, ' ')}</span>
        `;
  }
};

// ─── Active Sidebar Link ──────────────────────────────────────────────────────
const updateActiveLink = (page) => {
  document.querySelectorAll('.sidebar-link').forEach((link) => {
    const linkPage = link.getAttribute('data-page');
    if (linkPage === page) {
      link.classList.add('bg-white/10', 'text-white');
      link.classList.remove('text-gray-400');
      // Show dot indicator
      const dot = link.querySelector('.active-dot');
      if (dot) dot.classList.add('opacity-100');
    } else {
      link.classList.remove('bg-white/10', 'text-white');
      link.classList.add('text-gray-400');
      const dot = link.querySelector('.active-dot');
      if (dot) dot.classList.remove('opacity-100');
    }
  });
};

// ─── Generate Sidebar Menu ────────────────────────────────────────────────────
if (menuElement) {
  menuElement.innerHTML = Object.keys(ROUTE_MAP)
    .map(
      (path) => `
            <li>
                <a href="#${path}" class="sidebar-link flex items-center px-6 py-3 text-[11px] font-black uppercase tracking-widest transition-all hover:bg-white/5 hover:text-white group rounded-none" data-page="${path}">
                    <span class="active-dot w-1.5 h-1.5 rounded-full bg-gold mr-3 opacity-0 transition-opacity"></span>
                    ${ROUTE_LABELS[path] || path}
                </a>
            </li>
        `
    )
    .join('');
}

// ─── Initial Load & Hash Handling ─────────────────────────────────────────────
const syncFromHash = () => {
  const hash = window.location.hash.substring(1);
  const targetPage = hash || getState('admin:lastPage', 'overview');
  navigate(targetPage, true);
};

window.addEventListener('hashchange', syncFromHash);
syncFromHash();

// ─── Sidebar Click Handler ────────────────────────────────────────────────────
// Hashchange listener handles navigation, but we need to close mobile sidebar
if (menuElement) {
  menuElement.addEventListener('click', (event) => {
    const link = event.target.closest('a');
    if (link && window.innerWidth < 768) {
      // Dispatch a custom event or call a global toggle if it was complex,
      // but here simple toggle in index.php handles mobile sidebar on link click.
    }
  });
}
