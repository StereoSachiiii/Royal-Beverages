/**
 * Cart Service
 * Centralized OOP-style cart management
 * Handles state, local storage, and backend synchronization
 */

import { fetchProduct } from './products.js';
import { showCartSlideIn } from './cart-slide-in.js';
import { API } from './api-helper.js';

class CartService {
    constructor() {
        this.STORAGE_KEY = 'cart';
        this.items = this._load();
    }

    /**
     * Load cart from localStorage
     * @private
     */
    _load() {
        try {
            const data = localStorage.getItem(this.STORAGE_KEY);
            let items = data ? JSON.parse(data) : [];
            
            // Ensure items is always an array
            if (!Array.isArray(items)) {
                console.warn('[CartService] Storage data was not an array, resetting.');
                return [];
            }

            // Sanitize items: ensure price_cents and quantity are numbers
            return items.map(item => {
                const priceCents = item.price_cents || (item.price ? Math.round(parseFloat(item.price) * 100) : 0);
                return {
                    ...item,
                    price_cents: Number(priceCents) || 0,
                    quantity: Number(item.quantity) || 1
                };
            });
        } catch (e) {
            console.error('Cart load error:', e);
            return [];
        }
    }

    /**
     * Save cart to localStorage and notify listeners
     * @private
     */
    _save() {
        try {
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(this.items));
            
            // Trigger UI updates across the app
            window.dispatchEvent(new CustomEvent('cart:updated', { 
                detail: { 
                    items: this.getItems(),
                    total: this.getTotal(),
                    count: this.getCount()
                } 
            }));
        } catch (e) {
            console.error('Cart save error:', e);
        }
    }

    // --- CRUD Operations ---

    /**
     * Add item to cart
     * @param {number|string} productId 
     * @param {number} quantity 
     * @param {boolean} showSlideIn 
     */
    async add(productId, quantity = 1, showSlideIn = true) {
        try {
            const numId = Number(productId);
            const numQty = Number(quantity);

            const existingItem = this.items.find(item => Number(item.id) === numId);
            let productData;

            if (existingItem) {
                existingItem.quantity += numQty;
                productData = existingItem;
            } else {
                productData = await fetchProduct(numId);
                if (!productData) {
                    console.error(`Product ${productId} not found`);
                    return false;
                }

                // Robust price detection
                const priceCents = productData.price_cents || 
                                  (productData.price ? Math.round(parseFloat(productData.price) * 100) : 0) || 
                                  0;

                this.items.push({
                    id: productData.id,
                    name: productData.name,
                    category_name: productData.category_name,
                    image_url: productData.image_url,
                    price_cents: Number(priceCents),
                    quantity: numQty
                });
            }

            this._save();

            if (showSlideIn && productData) {
                showCartSlideIn(productData, numQty, this.getTotal(), this.getCount());
            }
            return true;
        } catch (error) {
            console.error('Error adding to cart:', error);
            return false;
        }
    }

    /**
     * Remove item from cart
     * @param {number|string} productId 
     */
    remove(productId) {
        const numId = Number(productId);
        this.items = this.items.filter(item => Number(item.id) !== numId);
        this._save();
    }

    /**
     * Update item quantity
     * @param {number|string} productId 
     * @param {number} quantity 
     */
    updateQuantity(productId, quantity) {
        const numId = Number(productId);
        const numQty = Math.max(1, Number(quantity) || 1);
        const item = this.items.find(item => Number(item.id) === numId);
        if (item) {
            item.quantity = numQty;
            this._save();
            return true;
        }
        return false;
    }

    /**
     * Clear the entire cart
     */
    clear() {
        this.items = [];
        this._save();
    }

    // --- Accessors ---

    /**
     * Get a copy of the cart items
     */
    getItems() {
        return JSON.parse(JSON.stringify(this.items));
    }

    /**
     * Get total number of items
     */
    getCount() {
        return this.items.reduce((total, item) => total + (Number(item.quantity) || 0), 0);
    }

    /**
     * Get cart total in cents
     * @returns {number}
     */
    getTotal() {
        return this.items.reduce((total, item) => {
            const price = Number(item.price_cents) || 0;
            const qty = Number(item.quantity) || 0;
            return total + (price * qty);
        }, 0);
    }

    /**
     * Check if item is already in cart
     */
    isInCart(productId) {
        return this.items.some(item => Number(item.id) === Number(productId));
    }

    // --- Backend Integration ---

    /**
     * Sync local cart with backend database
     * Now supports non-destructive merging
     */
    async asyncSync(userId, sessionId) {
        // Only sync for logged-in users (numeric IDs). 
        // Guest IDs (strings starting with 'guest_') should stay in localStorage only.
        if (!userId || isNaN(Number(userId))) return null;
        
        try {
            // 1. Get or create cart record in DB
            let cartRecord = null;
            
            try {
                if (userId) {
                    const response = await API.cart.getByUser(userId);
                    if (response.success && response.data) cartRecord = response.data;
                } else if (sessionId) { // Only check session if no userId
                    const response = await API.cart.getBySession(sessionId);
                    if (response.success && response.data) cartRecord = response.data;
                }
            } catch (err) {
            }

            // Create if still null
            if (!cartRecord) {
                const response = await API.cart.create({ user_id: userId, session_id: sessionId });
                cartRecord = response.data;
            }

            if (!cartRecord || !cartRecord.id) throw new Error('Could not resolve cart record');

            // 2. Fetch server items and merge into local truth
            const itemsRes = await API.cart.getItems(cartRecord.id);
            const serverItems = itemsRes.data || [];
            
            // Map server items by product_id for fast lookup
            const serverMap = new Map();
            serverItems.forEach(si => serverMap.set(Number(si.product_id), si));

            let localChanged = false;

            // Step A: Pull missing server items into local (Server -> Local)
            for (const sItem of serverItems) {
                const productId = Number(sItem.product_id);
                const localItem = this.items.find(li => Number(li.id) === productId);

                if (!localItem) {
                    // Item exists on server but not locally -> Pull down
                    this.items.push({
                        id: productId,
                        name: sItem.name || 'Product',
                        category_name: sItem.category_name || 'Selection',
                        image_url: sItem.image_url,
                        price_cents: Number(sItem.price_at_add_cents),
                        quantity: Number(sItem.quantity)
                    });
                    localChanged = true;
                } else {
                    // Item in both places: Sync quantity up to the server value if server has MORE
                    // (This handles cases where the user added more on another device)
                    const serverQty = Number(sItem.quantity);
                    if (localItem.quantity < serverQty) {
                        localItem.quantity = serverQty;
                        localChanged = true;
                    }
                }
            }

            if (localChanged) {
                this._save();
            }

            // Step B: Push local changes back to server (Local -> Server)
            const syncOps = [];

            for (const lItem of this.items) {
                const productId = Number(lItem.id);
                const sItem = serverMap.get(productId);

                if (!sItem) {
                    // Exists locally but not on server -> ADD to server
                    syncOps.push(API.cart.addItem({
                        cart_id: cartRecord.id,
                        product_id: productId,
                        quantity: lItem.quantity,
                        price_at_add_cents: lItem.price_cents
                    }));
                } else if (Number(sItem.quantity) !== lItem.quantity) {
                    // Quantity mismatch -> UPDATE server to match local truth
                    syncOps.push(API.cart.updateByCartProduct(cartRecord.id, productId, {
                        quantity: lItem.quantity
                    }));
                }
            }

            // Step C: Delete items on server that were removed locally
            for (const sItem of serverItems) {
                const productId = Number(sItem.product_id);
                if (!this.items.find(li => Number(li.id) === productId)) {
                    syncOps.push(API.cart.removeItem(sItem.id));
                }
            }

            if (syncOps.length > 0) {
                await Promise.allSettled(syncOps);
            }

            return cartRecord;
        } catch (error) {
            console.error('Cart sync failed:', error);
            // Don't throw, allow the app to function in local-only mode
            return null;
        }
    }

    /**
     * Legacy wrapper for sync to maintain compatibility
     */
    async sync(userId, sessionId) {
        return await this.asyncSync(userId, sessionId);
    }
}

// Export a single instance for use throughout the app
export const cart = new CartService();
export default cart;
