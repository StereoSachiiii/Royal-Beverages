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
import { getWishlist, removeItemFromWishlist } from '<?= BASE_URL ?>assets/js/wishlist-storage.js';
import { cart } from '<?= BASE_URL ?>assets/js/cart-service.js';
import { toast } from '<?= BASE_URL ?>assets/js/toast.js';

const fixImagePath = (url) => {
    if (!url) return '<?= BASE_URL ?>assets/images/placeholder-product.png';
    if (url.includes('products/')) {
        const filename = url.split('/').pop();
        return '<?= BASE_URL ?>assets/images/' + filename;
    }
    return '<?= BASE_URL ?>assets/images/' + url.split('/').pop();
};

function renderWishlist() {
    const wishlist = getWishlist();
    const container = document.getElementById('wishlistGrid');
    
    if (wishlist.length === 0) return;
    
    container.innerHTML = wishlist.map(item => `
        <div class="bg-white border border-gray-100 group transition-all duration-500 hover:border-black relative flex flex-col overflow-hidden" data-id="${item.id}">
            <!-- Visual -->
            <div class="aspect-[4/5] bg-gray-50 flex items-center justify-center relative">
                <img src="${fixImagePath(item.image_url)}" alt="${item.name}" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110 grayscale group-hover:grayscale-0">
                
                <!-- Quick Removal -->
                <button class="remove-btn absolute top-4 right-4 w-8 h-8 bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-black hover:border-black transition-all duration-300 opacity-0 group-hover:opacity-100 translate-x-4 group-hover:translate-x-0 z-20" data-id="${item.id}" title="Remove from Wishlist">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <!-- Hover Add to Cart Layer -->
                <div class="absolute inset-x-0 bottom-0 translate-y-full group-hover:translate-y-0 transition-transform duration-300 z-10 hidden md:block">
                    <button class="add-to-cart w-full bg-black text-white py-4 text-[10px] font-black uppercase tracking-widest hover:bg-gold transition-colors flex items-center justify-center gap-2 block">
                        Add to Cart <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
            </div>

            <!-- Meta -->
            <div class="p-6 text-center flex flex-col flex-grow items-center justify-end bg-white relative z-20">
                <span class="text-[9px] uppercase font-black tracking-[0.3em] text-gray-400 mb-2 truncate max-w-full block">
                    ${item.category_name || 'Spirit'}
                </span>
                <a href="<?= BASE_URL ?>product.php?id=${item.id}" class="text-sm font-heading uppercase tracking-widest mb-4 group-hover:text-gold transition-colors line-clamp-2 px-2 hover:underline">
                    ${item.name}
                </a>
                <span class="text-xs font-black tracking-widest mt-auto uppercase">Rs. ${(item.price_cents / 100).toFixed(2)}</span>
            </div>

            <!-- Mobile Add to Cart (Only visible on small screens) -->
            <button class="add-to-cart md:hidden w-full bg-black text-white py-4 text-[10px] font-black uppercase tracking-widest hover:bg-gold transition-colors flex items-center justify-center gap-2">
                Add to Cart
            </button>
        </div>
    `).join('');

    // Re-attach listeners
    document.querySelectorAll('.remove-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            removeItemFromWishlist(btn.dataset.id);
            renderWishlist();
            toast.success('Removed from wishlist');
        });
    });

    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', async () => {
            const card = btn.closest('[data-id]');
            const id = card.dataset.id;
            const item = getWishlist().find(i => Number(i.id) === Number(id));
            
            if (item) {
                await cart.add(item.id, 1);
                toast.success(`Added ${item.name} to cart`);
                
                // Optionally remove from wishlist after adding to cart
                removeItemFromWishlist(id);
                renderWishlist();
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', renderWishlist);
</script>

<?php require_once __DIR__ . "/_layout_end.php"; ?>
