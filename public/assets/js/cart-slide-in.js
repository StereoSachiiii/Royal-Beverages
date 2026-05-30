/**
 * Cart Slide-in Preview
 * Shows a slide-in panel when items are added to cart
 */

let slideInTimeout = null;

// DOM ready
const initCartSlideIn = () => {
    // Create slide-in container if not exists
    if (!document.getElementById('cartSlideIn')) {
        const slideIn = document.createElement('div');
        slideIn.id = 'cartSlideIn';
        // Inline styles for safe transition without needing JIT compiler for arbitrary values
        slideIn.style.cssText = 'position: fixed; top: 100px; right: -450px; width: 420px; max-width: 100vw; background: white; z-index: 9999; transition: right 0.5s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column; box-shadow: -10px 0 40px rgba(0,0,0,0.15);';
        slideIn.className = 'border-l border-gray-100';
        slideIn.innerHTML = `
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white">
                <div class="flex items-center gap-3 text-gold text-[10px] uppercase font-black tracking-widest italic">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>Added to Cart</span>
                </div>
                <button class="text-gray-300 hover:text-black transition-colors" id="slideInClose">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-8" id="slideInContent"></div>
            <div class="p-8 border-t border-gray-100 flex gap-4 bg-gray-50/50">
                <a href="${window.ROYAL_CONFIG?.BASE_URL || '/'}cart.php" class="btn-premium-outline flex-1 text-[10px] uppercase h-12 flex items-center justify-center">View Cart</a>
                <a href="${window.ROYAL_CONFIG?.BASE_URL || '/'}checkout.php" class="btn-premium flex-1 text-[10px] uppercase h-12 flex items-center justify-center">Checkout</a>
            </div>
        `;
        document.body.appendChild(slideIn);

        // Close button
        document.getElementById('slideInClose').addEventListener('click', hideCartSlideIn);
    }
};

/**
 * Show cart slide-in with product info
 * @param {Object} product - Product object with id, name, image_url, price_cents
 * @param {number} quantity - Quantity added
 * @param {number} cartTotal - Total cart value in cents
 * @param {number} cartCount - Total items in cart
 */
export const showCartSlideIn = (product, quantity, cartTotal, cartCount) => {
    initCartSlideIn();

    const slideIn = document.getElementById('cartSlideIn');
    const content = document.getElementById('slideInContent');

    if (!slideIn || !content) return;

    const price = ((product.price_cents || 0) / 100).toFixed(2);
    const total = ((cartTotal || 0) / 100).toFixed(2);

    content.innerHTML = `
        <div class="flex gap-6 mb-8">
            <div class="w-24 h-24 bg-gray-50 flex items-center justify-center shrink-0 border border-gray-100 overflow-hidden">
                <img src="${product.image_url ? (window.ROYAL_CONFIG.ASSET_URL + 'images/' + product.image_url.split('/').pop()) : (window.ROYAL_CONFIG.ASSET_URL + 'images/placeholder-product.webp')}" alt="${product.name}" class="w-full h-full object-contain mix-blend-multiply" onerror="this.src='${window.ROYAL_CONFIG.ASSET_URL}images/placeholder-product.webp'">
            </div>
            <div class="flex flex-col justify-between py-1">
                <div>
                    <div class="text-xl font-heading uppercase font-black tracking-tight leading-none mb-2">${product.name}</div>
                    <div class="text-[9px] uppercase tracking-widest text-gray-400 font-bold">QTY: ${quantity}</div>
                </div>
                <div class="text-xl font-black text-black tracking-tighter">Rs. ${price}</div>
            </div>
        </div>
        <div class="flex justify-between items-end pt-6 border-t border-gray-100 border-dashed">
            <span class="text-[9px] uppercase font-black tracking-widest text-gray-400 leading-tight">Cart Total<br><span class="text-[8px]">(${cartCount} item${cartCount !== 1 ? 's' : ''})</span></span>
            <span class="text-2xl font-black tracking-tighter">Rs. ${total}</span>
        </div>
    `;

    // Show slide-in
    setTimeout(() => {
        slideIn.style.right = '0px';
    }, 10);

    // Auto-hide after 5 seconds
    clearTimeout(slideInTimeout);
    slideInTimeout = setTimeout(hideCartSlideIn, 5000);
};

/**
 * Hide cart slide-in
 */
export const hideCartSlideIn = () => {
    const slideIn = document.getElementById('cartSlideIn');
    if (slideIn) {
        slideIn.style.right = '-450px';
    }
    clearTimeout(slideInTimeout);
};

// Initialize on load
document.addEventListener('DOMContentLoaded', initCartSlideIn);

