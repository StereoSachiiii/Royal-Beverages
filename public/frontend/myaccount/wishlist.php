<?php
/**
 * MyAccount Wishlist
 * Saved interests gallery
 */
$pageName = 'account';
$pageTitle = 'Wishlist - Royal Beverages';
require_once __DIR__ . "/_layout.php";
?>

<div class="space-y-16">
    <!-- Header -->
    <header>
        <span class="text-xs uppercase tracking-[0.4em] text-gold font-extrabold mb-4 block italic">Curated Interests</span>
        <h1 class="text-4xl md:text-5xl font-black uppercase tracking-widest leading-none">Your <br>Wishlist</h1>
    </header>

    <!-- Wishlist Gallery Grid -->
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="wishlistGrid">
        <!-- Populated via JS -->
        <div class="col-span-full py-32 text-center bg-white border border-gray-100 flex flex-col items-center justify-center">
            <div class="w-20 h-20 border border-gray-50 flex items-center justify-center mb-10 opacity-10">
                <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
            </div>
            <p class="text-[11px] uppercase tracking-[0.3em] text-gray-400 font-black">Your wishlist is currently empty</p>
            <a href="<?= getPageUrl('shop') ?>" class="btn-premium mt-12 px-16">Browse Collection</a>
        </div>
    </section>
</div>

<script type="module">
import { getWishlist, removeItemFromWishlist, isInWishlist, toggleWishlistItem } from '<?= BASE_URL ?>assets/js/wishlist-storage.js';
import { cart } from '<?= BASE_URL ?>assets/js/cart-service.js';
import { toast } from '<?= BASE_URL ?>assets/js/toast.js';
import { API } from '<?= BASE_URL ?>assets/js/api-helper.js';

const fixImagePath = (url) => {
    if (!url) return '<?= BASE_URL ?>assets/images/placeholder-product.webp';
    if (url.includes('products/')) return '<?= BASE_URL ?>assets/images/' + url.split('/').pop();
    return '<?= BASE_URL ?>assets/images/' + url.split('/').pop();
};

// Same card template as shop.php / products.php
const renderProductCard = (p) => {
    const price = (p.price_cents / 100).toFixed(2);
    const inStock = p.available_stock > 0;
    const isPremium = p.price_cents >= 10000;
    let badgeHtml = '';
    if (isPremium) badgeHtml = 'Vintage';
    else if (p.available_stock < 20 && inStock) badgeHtml = `Low Stock: ${p.available_stock}`;

    return `
        <div class="group w-full bg-white border border-gray-100 p-8 flex flex-col relative overflow-hidden transition-all duration-500 hover:border-black ${!inStock ? 'opacity-40 grayscale' : ''}" data-id="${p.id}">
            <div class="absolute top-6 left-6 z-10 flex flex-col gap-2">
                ${!inStock ? `<span class="bg-gray-100 text-gray-500 text-[8px] font-black uppercase tracking-widest px-3 py-1">Depleted</span>` : ''}
                ${badgeHtml ? `<span class="bg-black text-white text-[8px] font-black uppercase tracking-widest px-3 py-1 shadow-sm">${badgeHtml}</span>` : ''}
            </div>
            <!-- Remove from wishlist -->
            <button class="btn-remove-wishlist absolute top-6 right-6 z-10 w-8 h-8 bg-white border border-gray-100 flex items-center justify-center text-gray-300 hover:text-black hover:border-black transition-all opacity-0 group-hover:opacity-100" data-id="${p.id}" title="Remove from Wishlist">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <a href="<?= BASE_URL ?>product.php?id=${p.id}" class="block h-56 mb-8 mt-4 relative flex items-center justify-center cursor-pointer">
                <img src="${fixImagePath(p.image_url)}" alt="${p.name}" class="max-h-full max-w-full object-contain transition-transform duration-700 group-hover:scale-110 drop-shadow-2xl" loading="lazy" onerror="this.src='<?= BASE_URL ?>assets/images/placeholder-product.webp'">
            </a>
            <div class="text-center flex flex-col flex-grow items-center justify-end w-full">
                <span class="text-[9px] uppercase font-black tracking-[0.3em] text-gray-400 mb-2 truncate max-w-full block">${p.category_name || 'Spirit'}</span>
                <h3 class="text-sm font-heading uppercase tracking-widest mb-4 group-hover:text-gold transition-colors line-clamp-2 px-2">${p.name}</h3>
                <span class="text-xs font-black tracking-widest mb-8 uppercase">Rs. ${price}</span>
                <div class="flex gap-2 w-full mt-auto">
                    <a href="<?= BASE_URL ?>product.php?id=${p.id}" class="btn-premium-outline flex-grow h-12 text-[9px] flex items-center justify-center" style="padding: 0 0.5rem;">View Details</a>
                    <button class="btn-premium w-12 h-12 flex-shrink-0 flex items-center justify-center btn-add-cart" style="padding: 0;" data-id="${p.id}" ${!inStock ? 'disabled' : ''} title="Add to Cart">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </button>
                </div>
            </div>
        </div>
    `;
};

async function renderWishlist() {
    const wishlist = getWishlist();
    const container = document.getElementById('wishlistGrid');

    if (wishlist.length === 0) return;

    // Show skeleton while fetching live data
    container.innerHTML = `<div class="col-span-full py-16 text-center text-[10px] uppercase tracking-widest text-gray-300 font-black">Loading collection...</div>`;

    // Fetch live enriched data for all wishlisted IDs
    const ids = wishlist.map(i => Number(i.product_id || i.id));
    let products = [];
    try {
        const res = await API.request('/products/enriched/all' + API.buildQuery({ limit: 200 }));
        if (res.success && res.data) {
            const all = res.data.items || res.data;
            products = all.filter(p => ids.includes(Number(p.id)));
        }
    } catch (e) {
        // Fallback to localStorage data if API fails
        products = wishlist;
    }

    if (products.length === 0) {
        container.innerHTML = `<div class="col-span-full py-32 text-center bg-white border border-gray-100 flex flex-col items-center justify-center">
            <p class="text-[11px] uppercase tracking-[0.3em] text-gray-400 font-black">Your wishlist is currently empty</p>
            <a href="<?= getPageUrl('shop') ?>" class="btn-premium mt-12 px-16">Browse Collection</a>
        </div>`;
        return;
    }

    container.innerHTML = products.map(renderProductCard).join('');

    // Attach event listeners
    container.addEventListener('click', async (e) => {
        const removeBtn = e.target.closest('.btn-remove-wishlist');
        if (removeBtn) {
            removeItemFromWishlist(removeBtn.dataset.id);
            removeBtn.closest('[data-id]').remove();
            if (container.querySelectorAll('[data-id]').length === 0) renderWishlist();
        }

        const cartBtn = e.target.closest('.btn-add-cart');
        if (cartBtn && !cartBtn.disabled) {
            await cart.add(cartBtn.dataset.id, 1);
        }
    });
}

document.addEventListener('DOMContentLoaded', renderWishlist);
</script>

<?php require_once __DIR__ . "/_layout_end.php"; ?>
