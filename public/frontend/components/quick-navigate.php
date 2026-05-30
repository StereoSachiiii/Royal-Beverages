<div class="fixed bottom-8 right-8 flex flex-col gap-4 z-50">
    <a href="<?= BASE_URL ?>myaccount/wishlist.php" class="wishlist-icon w-14 h-14 bg-white border border-gray-100 shadow-2xl flex items-center justify-center group hover:scale-110 transition-all duration-300 relative">
        <svg class="w-6 h-6 text-black group-hover:text-gold transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
        </svg>
    </a>
    
    <a href="<?= BASE_URL ?>cart.php" class="cart-icon w-14 h-14 bg-black shadow-2xl flex items-center justify-center group hover:scale-110 transition-all duration-300 relative">
        <svg class="w-6 h-6 text-white group-hover:text-gold transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
    </a>
</div>

<script type="module">
    import { cart } from '<?= BASE_URL ?>assets/js/cart-service.js';
    import { getWishlist } from '<?= BASE_URL ?>assets/js/wishlist-storage.js';

    function updateCounts() {
        // Update cart count
        const cartCount = cart.getCount();
        const cartIcon = document.querySelector('.cart-icon');
        let cartBadge = cartIcon.querySelector('.badge');
        
        if (cartCount > 0) {
            const displayCount = cartCount > 99 ? '99+' : cartCount;
            if (!cartBadge) {
                cartBadge = document.createElement('span');
                cartBadge.className = 'badge absolute -top-2 -right-2 bg-gold text-black text-[9px] font-black h-5 min-w-[20px] px-1 flex items-center justify-center rounded-full border-2 border-white shadow-sm';
                cartIcon.appendChild(cartBadge);
            }
            cartBadge.textContent = displayCount;
        } else if (cartBadge) {
            cartBadge.remove();
        }

        // Update wishlist count
        const wishlistItems = getWishlist();
        const wishlistCount = wishlistItems ? wishlistItems.length : 0;
        const wishlistIcon = document.querySelector('.wishlist-icon');
        let wishlistBadge = wishlistIcon.querySelector('.badge');
        
        if (wishlistCount > 0) {
            const displayCount = wishlistCount > 99 ? '99+' : wishlistCount;
            if (!wishlistBadge) {
                wishlistBadge = document.createElement('span');
                wishlistBadge.className = 'badge absolute -top-2 -right-2 bg-black text-white text-[9px] font-black h-5 min-w-[20px] px-1 flex items-center justify-center rounded-full border-2 border-white shadow-sm';
                wishlistIcon.appendChild(wishlistBadge);
            }
            wishlistBadge.textContent = displayCount;
        } else if (wishlistBadge) {
            wishlistBadge.remove();
        }
    }

    updateCounts();
    window.addEventListener('storage', updateCounts);
    document.addEventListener('cart:updated', updateCounts);
    setInterval(updateCounts, 5000);
</script>
