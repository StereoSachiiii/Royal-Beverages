<?php 
$pageName = 'cart';
$pageTitle = 'Your Choices - Royal Beverages';
require_once __DIR__.'/components/header.php'; 
?>

<main class="min-h-screen bg-white pb-32">
    <?php
    $heroTitle = 'Your <br class="hidden md:block">Cart';
    $heroSubtitle = 'Selection';
    $heroDescription = '';
    $heroId = 'cartHero';
    $heroOffset = '-17.5%';
    $heroBreadcrumbs = (new \App\UI\Breadcrumb())
        ->add('Home', BASE_URL)
        ->add('Your Cart');
    require_once __DIR__ . '/components/animated-hero.php';
    ?>

    <div class="px-8 md:px-16">
        <div class="max-w-[1440px] mx-auto lg:grid lg:grid-cols-3 gap-24">
            <!-- Items Column -->
            <div class="lg:col-span-2 space-y-8" id="cartItemsList">
                <!-- Cart items injected here -->
            </div>

            <!-- Summary Column -->
            <aside class="mt-20 lg:mt-0">
                <div class="sticky top-32 bg-white border border-gray-100 p-10 shadow-sm space-y-12">
                    <div>
                        <div class="flex items-center justify-between border-b border-black pb-4 mb-8">
                            <h3 class="text-xs uppercase font-black tracking-[0.3em]">Order Summary</h3>
                            <button id="clearCart" class="text-[9px] uppercase font-black tracking-widest text-gray-300 hover:text-rose-500 transition-colors">Clear All</button>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center text-[10px] uppercase font-black tracking-widest text-gray-400">
                                <span>Subtotal</span>
                                <span id="cartSubtotal" class="text-black text-sm">$0.00</span>
                            </div>
                            <div class="flex justify-between items-center text-[10px] uppercase font-black tracking-widest text-gray-400">
                                <span>Shipping</span>
                                <span class="text-emerald-600 font-bold">Complimentary</span>
                            </div>
                        </div>

                        <div class="pt-10 border-t border-gray-200 mt-10">
                            <div class="flex justify-between items-end mb-6">
                                <span class="text-xs uppercase tracking-[0.2em] font-black">Total</span>
                                <span id="cartTotal" class="text-4xl font-bold tracking-tight">$0.00</span>
                            </div>
                            
                            <a href="<?= BASE_URL ?>checkout.php" id="checkoutBtn" class="btn-premium w-full h-16 flex items-center justify-center gap-4 shadow-xl">
                                <span>Checkout</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</main>

<script type="module">
import { cart } from './assets/js/cart-service.js';
import { toast } from './assets/js/toast.js';
import { fixImagePath } from './assets/js/ui-components.js';

const cartItemsList = document.getElementById('cartItemsList');
const cartSubtotalElement = document.getElementById('cartSubtotal');
const cartTotalElement = document.getElementById('cartTotal');



const renderCartItem = (item) => {
    const itemSubtotal = (item.price_cents * item.quantity / 100).toFixed(2);
    const unitPrice = (item.price_cents / 100).toFixed(2);
    
    return `
        <div class="flex flex-col md:flex-row gap-12 p-10 bg-white border border-gray-300 shadow-sm hover:shadow-xl transition-all group relative overflow-hidden" data-item-id="${item.id}">
            <!-- Visual -->
            <div class="w-full md:w-48 h-64 bg-gray-50 flex items-center justify-center shrink-0 p-8 overflow-hidden border border-gray-100">
                <img src="${fixImagePath(item.image_url)}" alt="${item.name}" class="w-full h-full object-contain transition-transform duration-1000 group-hover:scale-110" onerror="this.src='<?= BASE_URL ?>assets/images/placeholder-product.webp'">
            </div>
            
            <!-- Content -->
            <div class="flex-grow flex flex-col justify-between py-2">
                <div class="flex justify-between items-start gap-8">
                    <div>
                        <span class="text-[9px] uppercase tracking-[0.4em] text-gold font-black mb-3 block italic">${item.category_name || 'Spirit'}</span>
                        <h3 class="text-3xl font-black uppercase tracking-tight mb-2">${item.name}</h3>
                        <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">BATCH REF: RL-${item.id.toString().padStart(6, '0')}</p>
                    </div>
                    <button class="remove-item p-3 text-gray-300 hover:text-rose-600 transition-colors bg-gray-50 hover:bg-rose-50 rounded-full" data-id="${item.id}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="flex items-center justify-between border-t border-gray-100 pt-10 mt-10">
                    <div class="flex items-center bg-white h-14 px-2 border border-gray-300 shadow-sm">
                        <button class="qty-btn w-12 h-12 hover:text-gold transition-colors font-black text-xl minus flex items-center justify-center" data-id="${item.id}">−</button>
                        <span class="w-12 text-center font-black text-sm">${item.quantity}</span>
                        <button class="qty-btn w-12 h-12 hover:text-gold transition-colors font-black text-xl plus flex items-center justify-center" data-id="${item.id}">+</button>
                    </div>
                    
                    <div class="text-right">
                        <span class="block text-[9px] uppercase tracking-widest text-gray-400 font-black mb-1 italic">Rs. ${unitPrice} / UNIT</span>
                        <span class="text-3xl font-black tracking-tighter text-black">Rs. ${itemSubtotal}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
};

const renderCart = () => {
    const items = cart.getItems();
    
    if (!items || items.length === 0) {
        cartItemsList.innerHTML = `
            <div class="py-32 flex flex-col items-center text-center">
                <div class="text-6xl mb-8 opacity-20 italic font-heading text-gold">Your Cart</div>
                <h2 class="text-xl font-bold uppercase tracking-widest mb-8">Your cart is currently empty</h2>
                <a href="shop.php" class="btn-premium px-16 h-16 flex items-center gap-4">Enter The Shop</a>
            </div>`;
        cartSubtotalElement.textContent = 'Rs. 0.00';
        cartTotalElement.textContent = 'Rs. 0.00';
        return;
    }
    
    const subtotal = (cart.getTotal() / 100).toFixed(2);
    cartItemsList.innerHTML = items.map(item => renderCartItem(item)).join('');
    cartSubtotalElement.textContent = `Rs. ${subtotal}`;
    cartTotalElement.textContent = `Rs. ${subtotal}`;

    // Add event listeners for removal and quantity
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', () => {
            cart.remove(btn.dataset.id);
            renderCart();
            toast.success('Item removed from cart');
        });
    });

    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const items = cart.getItems();
            const item = items.find(i => Number(i.id) === Number(id));
            if (!item) return;
            const newQty = btn.classList.contains('plus') ? item.quantity + 1 : Math.max(1, item.quantity - 1);
            cart.updateQuantity(id, newQty);
            renderCart();
        });
    });
};

document.addEventListener('DOMContentLoaded', () => {
    renderCart();
    
    document.getElementById('clearCart').addEventListener('click', () => {
        if (confirm('Are you sure you want to empty your cart?')) {
            cart.clear();
            renderCart();
            toast.gold('Cart cleared');
        }
    });
    
    document.getElementById('checkoutBtn').addEventListener('click', (e) => {
        e.preventDefault();
        const items = cart.getItems();
        if (!items || items.length === 0) return toast.error('Add items to your cart to checkout');
        window.location.href = 'checkout.php';
    });
});
</script>

<?php require_once __DIR__ . "/components/footer.php"; ?>
