/**
 * Wishlist Storage — localStorage with background server sync
 */

import { fetchProduct } from './products.js';
import API from './api-helper.js';
import toast from './toast.js';

const KEY = 'wishlist';
const EXPIRY = 6 * 30 * 24 * 60 * 60 * 1000; // 6 months

export function getWishlist() {
  try {
    const raw = localStorage.getItem(KEY);
    if (!raw) return [];
    const data = JSON.parse(raw);
    if (data.expiresAt && Date.now() > data.expiresAt) {
      localStorage.removeItem(KEY);
      return [];
    }
    return data.items || [];
  } catch {
    return [];
  }
}

function saveWishlist(items) {
  try {
    localStorage.setItem(KEY, JSON.stringify({ items, expiresAt: Date.now() + EXPIRY }));
  } catch (e) {
    console.error('Wishlist save failed:', e);
  }
}

export function isInWishlist(productId) {
  const numId = Number(productId);
  return getWishlist().some((item) => Number(item.product_id || item.id) === numId);
}

export function removeItemFromWishlist(productId) {
  try {
    const numId = Number(productId);
    saveWishlist(getWishlist().filter((item) => Number(item.product_id || item.id) !== numId));
    toast.info('Removed from Wishlist');
    API.wishlist.remove(numId).catch(() => {});
    return true;
  } catch (e) {
    console.error('Wishlist remove failed:', e);
    return false;
  }
}

async function addItemToWishlist(productId) {
  const numId = Number(productId);
  const wishlist = getWishlist();
  if (wishlist.some((item) => Number(item.product_id || item.id) === numId)) return true;

  // Optimistic insert — makes isInWishlist() true immediately
  wishlist.push({ id: numId, product_id: numId });
  saveWishlist(wishlist);
  toast.gold('♡ Added to Wishlist');

  // Hydrate placeholder with full product data in background
  fetchProduct(numId)
    .then((product) => {
      if (!product) return;
      const current = getWishlist();
      const idx = current.findIndex((item) => Number(item.product_id || item.id) === numId);
      if (idx !== -1) {
        current[idx] = product;
        saveWishlist(current);
      }
    })
    .catch(() => {});

  API.wishlist.add({ product_id: numId }).catch(() => {});
  return true;
}

export async function toggleWishlistItem(productId) {
  if (isInWishlist(productId)) {
    removeItemFromWishlist(productId);
    return false;
  }
  await addItemToWishlist(productId);
  return true;
}

// Sync with server on load (fails silently for guests)
export async function initWishlistSync() {
  try {
    const ids = getWishlist().map((item) => Number(item.product_id || item.id));
    const response = await API.wishlist.sync(ids);
    if (response?.success && response.data) saveWishlist(response.data);
  } catch {
    /* guest or offline */
  }
}

initWishlistSync();
