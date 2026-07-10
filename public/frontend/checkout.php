<?php 
require_once __DIR__ . "/../../src/Core/bootstrap.php";
$session = \App\Core\Session::getInstance();

// 1. Strict Auth Guard (Before any output)
if (!$session->isLoggedIn()) {
    header("Location: " . BASE_URL . "auth.php?redirect=checkout.php");
    exit;
}

$pageName = 'checkout';
$pageTitle = 'Checkout - Royal Beverages';
require_once __DIR__ . "/components/header.php"; 

$userId = $session->getUserId();
?>

<main class="min-h-screen bg-[#fafafa] pb-32">
    <!-- Breadcrumb -->
    <div class="px-8 md:px-16 pt-12 pb-6 flex justify-center">
        <nav class="flex items-center gap-4 text-sm uppercase font-black tracking-[0.3em] text-gray-400 text-center">
            <a href="<?= BASE_URL ?>" class="hover:text-gold transition-colors">Home</a>
            <span>/</span>
            <a href="cart.php" class="hover:text-gold transition-colors">Cart</a>
            <span>/</span>
            <span class="text-black italic">Secure Checkout</span>
        </nav>
    </div>

    <!-- Layout Container -->
    <div class="px-8 md:px-16 max-w-[1440px] mx-auto">
        <div class="lg:grid lg:grid-cols-12 gap-16">
            
            <!-- Left: Stepper -->
            <div class="lg:col-span-8">
                
                <!-- Step 1: Delivery -->
                <section id="step-delivery" class="checkout-step block">
                    <div class="mb-12">
                        <label class="text-[10px] uppercase font-black tracking-[0.3em] text-gold mb-2 block italic">Step 01</label>
                        <h3 class="text-3xl font-black uppercase tracking-tight">Delivery Details</h3>
                    </div>

                    <div id="address-container" class="space-y-8">
                        <div id="address-list" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Populated via JS -->
                            <div class="h-32 flex items-center justify-center border border-dashed border-gray-200 col-span-full">
                                <span class="text-[9px] uppercase font-black tracking-widest text-gray-300 animate-pulse">Loading Addresses...</span>
                            </div>
                        </div>

                        <button id="btn-add-address" class="w-full h-16 border-2 border-dashed border-gray-200 text-[10px] uppercase font-black tracking-widest text-gray-400 hover:border-gold hover:text-gold transition-all">
                            + Add New Address
                        </button>
                    </div>

                    <!-- Add Address Form (Hidden) -->
                    <div id="form-address" class="hidden bg-white p-10 border border-gray-300 shadow-2xl space-y-8">
                        <form id="address-form" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <label class="text-[9px] uppercase font-black tracking-widest text-gray-400 ml-1">Recipient Name</label>
                                <input type="text" name="recipient_name" required class="w-full h-14 bg-white border border-gray-300 px-6 text-sm font-bold outline-none focus:border-gold focus:ring-1 focus:ring-gold rounded-none">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[9px] uppercase font-black tracking-widest text-gray-400 ml-1">Phone</label>
                                <input type="tel" name="phone" required class="w-full h-14 bg-white border border-gray-300 px-6 text-sm font-bold outline-none focus:border-gold focus:ring-1 focus:ring-gold rounded-none">
                            </div>
                            <div class="md:col-span-2 space-y-2">
                                <label class="text-[9px] uppercase font-black tracking-widest text-gray-400 ml-1">Street Address</label>
                                <input type="text" name="address_line1" required class="w-full h-14 bg-white border border-gray-300 px-6 text-sm font-bold outline-none focus:border-gold focus:ring-1 focus:ring-gold rounded-none">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[9px] uppercase font-black tracking-widest text-gray-400 ml-1">City</label>
                                <input type="text" name="city" required class="w-full h-14 bg-white border border-gray-300 px-6 text-sm font-bold outline-none focus:border-gold focus:ring-1 focus:ring-gold rounded-none">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[9px] uppercase font-black tracking-widest text-gray-400 ml-1">Postal Code</label>
                                <input type="text" name="postal_code" required class="w-full h-14 bg-white border border-gray-300 px-6 text-sm font-bold outline-none focus:border-gold focus:ring-1 focus:ring-gold rounded-none">
                            </div>
                            <div class="md:col-span-2 flex gap-4 pt-4">
                                <button type="button" id="btn-cancel-address" class="flex-1 h-16 border border-gray-300 text-[10px] uppercase font-bold tracking-widest hover:bg-gray-50 transition-colors">Cancel</button>
                                <button type="submit" class="flex-1 h-16 bg-black text-white text-[10px] uppercase font-black tracking-widest hover:bg-gold transition-all shadow-xl active:scale-95">Save Address</button>
                            </div>
                        </form>
                    </div>

                    <div class="mt-20 pt-12 border-t border-gray-100 flex justify-between items-center">
                        <a href="<?= BASE_URL ?>cart.php" class="text-[10px] uppercase font-black tracking-widest text-gray-300 hover:text-black">← Return to Cart</a>
                        <button id="btn-to-payment" class="btn-premium px-16 h-16 disabled:opacity-30 disabled:cursor-not-allowed" disabled>Continue to Payment</button>
                    </div>
                </section>

                <!-- Step 2: Payment -->
                <section id="step-payment" class="checkout-step hidden">
                    <div class="mb-12">
                        <label class="text-[10px] uppercase font-black tracking-[0.3em] text-gold mb-2 block italic">Step 02</label>
                        <h3 class="text-3xl font-black uppercase tracking-tight">Payment Method</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <label class="payment-card p-10 bg-white border border-gray-300 hover:border-gold cursor-pointer transition-all selected" data-value="card">
                            <input type="radio" name="payment_type" value="card" checked class="hidden">
                            <span class="block text-xs uppercase font-black tracking-widest mb-2 italic">Credit / Debit Card</span>
                            <span class="block text-[9px] text-gray-400 font-bold uppercase tracking-[0.2em]">Secure Stripe Gateway</span>
                        </label>
                        <label class="payment-card p-10 bg-white border border-gray-300 hover:border-gold cursor-pointer transition-all" data-value="cod">
                            <input type="radio" name="payment_type" value="cod" class="hidden">
                            <span class="block text-xs uppercase font-black tracking-widest mb-2 italic">Cash on Delivery</span>
                            <span class="block text-[9px] text-gray-400 font-bold uppercase tracking-[0.2em]">Pay upon arrival</span>
                        </label>
                    </div>

                    <div class="mt-20 pt-12 border-t border-gray-100 flex justify-between items-center">
                        <button id="btn-back-to-delivery" class="text-[10px] uppercase font-black tracking-widest text-gray-300 hover:text-black">← Back to Delivery</button>
                        <button id="btn-to-review" class="btn-premium px-16 h-16">Review Order</button>
                    </div>
                </section>

                <!-- Step 3: Review -->
                <section id="step-review" class="checkout-step hidden">
                    <div class="mb-12">
                        <label class="text-[10px] uppercase font-black tracking-[0.3em] text-gold mb-2 block italic">Step 03</label>
                        <h3 class="text-3xl font-black uppercase tracking-tight">Review & Pay</h3>
                    </div>

                    <div class="bg-white border border-gray-300 p-10 space-y-12 mb-12 shadow-sm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                            <div>
                                <h4 class="text-[9px] uppercase font-black tracking-widest text-gray-300 mb-4 italic">Shipping To</h4>
                                <div id="review-address" class="text-sm font-black leading-relaxed"></div>
                            </div>
                            <div>
                                <h4 class="text-[9px] uppercase font-black tracking-widest text-gray-300 mb-4 italic">Payment via</h4>
                                <div id="review-payment" class="text-sm font-black uppercase tracking-widest"></div>
                            </div>
                        </div>
                        <div class="pt-12 border-t border-gray-100">
                            <h4 class="text-[9px] uppercase font-black tracking-widest text-gray-300 mb-4 italic">Order Notes</h4>
                            <textarea id="order-notes" placeholder="Any special requests or delivery instructions?" class="w-full h-32 bg-gray-50 border border-gray-300 p-6 text-sm font-bold outline-none focus:border-gold focus:ring-1 focus:ring-gold resize-none rounded-none placeholder:text-gray-300"></textarea>
                        </div>
                    </div>

                    <div class="mt-20 pt-12 border-t border-gray-100 flex justify-between items-center">
                        <button id="btn-back-to-payment" class="text-[10px] uppercase font-black tracking-widest text-gray-300 hover:text-black">← Back to Payment</button>
                        <button id="btn-place-order" class="btn-premium px-16 h-16 shadow-2xl">Place Order Now</button>
                    </div>
                </section>

                <!-- Step 4: Success -->
                <section id="step-success" class="checkout-step hidden py-24 text-center">
                    <h2 class="text-6xl font-black uppercase tracking-tighter mb-4 italic">Complete</h2>
                    <p class="text-[10px] uppercase font-bold tracking-[0.4em] text-gray-400 mb-12">Order <span id="success-order-id" class="text-black">#000000</span> secure and processing.</p>
                    <div class="flex gap-4 justify-center">
                        <a href="<?= BASE_URL ?>myaccount/orders.php" class="btn-premium px-12 h-16">View Order History</a>
                        <a href="<?= BASE_URL ?>shop.php" class="btn-premium-outline px-12 h-16">Continue Shopping</a>
                    </div>
                </section>
            </div>

            <!-- Right: Sidebar -->
            <aside class="lg:col-span-4 mt-16 lg:mt-0">
                <div class="sticky top-32 space-y-8">
                    <div class="bg-white border border-gray-100 p-10 shadow-sm">
                        <h3 class="text-xs uppercase font-black tracking-widest border-b border-black pb-4 mb-8">Selection Summary</h3>
                        
                        <div id="summary-items" class="space-y-6 max-h-[400px] overflow-y-auto pr-4 custom-scrollbar mb-8">
                            <!-- Populated via JS -->
                        </div>

                        <div class="pt-8 border-t border-gray-50 space-y-4">
                            <div class="flex justify-between items-center text-[10px] uppercase font-black tracking-widest text-gray-400">
                                <span>Subtotal</span>
                                <span id="summary-subtotal" class="text-black text-sm">Rs. 0.00</span>
                            </div>
                            <div class="flex justify-between items-center text-[10px] uppercase font-black tracking-widest text-gray-400">
                                <span>Shipping</span>
                                <span class="text-emerald-600 italic">Complimentary</span>
                            </div>
                        </div>

                        <div class="pt-8 border-t border-black mt-8">
                            <div class="flex justify-between items-end">
                                <span class="text-xs uppercase font-black tracking-widest italic">Total</span>
                                <span id="summary-total" class="text-4xl font-black tracking-tighter">Rs. 0.00</span>
                            </div>
                        </div>
                </div>
            </aside>
        </div>
    </div>
</main>

<script type="module">
import { cart } from './assets/js/cart-service.js';
import { API } from './assets/js/api-helper.js';
import { toast } from './assets/js/toast.js';
import { fixImagePath, showErrorBoundary } from './assets/js/ui-components.js';

// --- State Management ---
const state = {
    step: 1,
    addresses: [],
    selectedAddressId: null,
    paymentType: 'card',
    userId: <?= json_encode($userId) ?>,
    sessionId: <?= json_encode(session_id()) ?>,
    cartId: null
};

// --- DOM Cache ---
const ui = {
    steps: document.querySelectorAll('.checkout-step'),
    addressList: document.getElementById('address-list'),
    summaryItems: document.getElementById('summary-items'),
    summarySubtotal: document.getElementById('summary-subtotal'),
    summaryTotal: document.getElementById('summary-total'),
    btnToPayment: document.getElementById('btn-to-payment'),
    placeOrderBtn: document.getElementById('btn-place-order')
};

// --- Initialization ---
const init = async () => {
    // 0. Check for Stripe success callback
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'success') {
        const orderId = urlParams.get('order_id') || '000000';
        document.getElementById('success-order-id').textContent = `#${orderId.toString().padStart(6, '0')}`;
        cart.clear(); // Wipe cart
        goToStep(4);
        toast.gold('Payment Processed Successfully');
        // Clean up URL
        window.history.replaceState({}, document.title, window.location.pathname);
        return;
    }

    // 1. Force check cart count
    const items = cart.getItems();
    if (!items || items.length === 0) {
        window.location.href = '<?= BASE_URL ?>shop.php';
        return;
    }

    renderSummary();
    
    // 2. Load Addresses
    try {
        const res = await API.addresses.list(state.userId);
        state.addresses = res.data || res || [];
        renderAddresses();
    } catch (err) {
        console.error('Failed to load addresses:', err);
        showErrorBoundary("We couldn't securely verify your identity details. Please return home and try again.");
    }

    // 3. Background Cart Sync (Non-blocking)
    try {
        const syncRes = await cart.sync(state.userId, state.sessionId);
        if (syncRes && syncRes.id) state.cartId = syncRes.id;
    } catch (err) {
        console.warn('Sync failed, operating on local truth.');
    }
};



const renderSummary = () => {
    const items = cart.getItems();
    ui.summaryItems.innerHTML = items.map(item => `
        <div class="flex gap-4 group">
            <div class="w-20 h-20 bg-gray-50 flex-shrink-0 overflow-hidden border border-gray-200">
                <img src="${fixImagePath(item.image_url)}" class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-500 scale-110 group-hover:scale-100" onerror="this.src='<?= BASE_URL ?>assets/images/placeholder-product.webp'">
            </div>
            <div class="flex-1 min-w-0">
                <span class="block text-[8px] uppercase font-black text-gold italic mb-1">${item.category_name || 'Item'}</span>
                <span class="block text-[11px] font-black uppercase tracking-tight truncate leading-none mb-2">${item.name}</span>
                <div class="flex justify-between items-center bg-gray-50 p-2 mt-2">
                    <span class="text-[9px] font-bold text-gray-400">QTY: ${item.quantity}</span>
                    <span class="text-[10px] font-black tracking-tight">Rs. ${((item.price_cents * item.quantity) / 100).toFixed(2)}</span>
                </div>
            </div>
        </div>
    `).join('');

    const total = (cart.getTotal() / 100).toFixed(2);
    ui.summarySubtotal.textContent = `Rs. ${total}`;
    ui.summaryTotal.textContent = `Rs. ${total}`;
};

const renderAddresses = () => {
    if (state.addresses.length === 0) {
        ui.addressList.innerHTML = `<div class="col-span-full p-6 text-center bg-red-50 border border-red-200 text-xs uppercase font-black text-red-600 tracking-widest shadow-sm">Please add a new address before continuing</div>`;
        return;
    }

    ui.addressList.innerHTML = state.addresses.map(addr => `
        <div class="address-card p-8 border-2 transition-all cursor-pointer ${state.selectedAddressId == addr.id ? 'border-black bg-white shadow-xl scale-[1.02]' : 'border-gray-50 opacity-50 hover:opacity-100'}" data-id="${addr.id}">
            <div class="flex justify-between items-start mb-6">
                <span class="text-[8px] uppercase font-black text-gold italic">Profile Verified</span>
                <div class="w-3 h-3 rounded-full border-2 ${state.selectedAddressId == addr.id ? 'bg-gold border-gold' : 'border-gray-200'}"></div>
            </div>
            <p class="text-sm font-black uppercase mb-1">${addr.recipient_name}</p>
            <p class="text-[10px] font-bold text-gray-400 uppercase leading-relaxed tracking-wider">
                ${addr.address_line1}<br>${addr.city}<br>T: ${addr.phone}
            </p>
        </div>
    `).join('');

    document.querySelectorAll('.address-card').forEach(card => {
        card.onclick = () => {
            state.selectedAddressId = card.dataset.id;
            renderAddresses();
            ui.btnToPayment.disabled = false;
        };
    });
};

const goToStep = (step) => {
    state.step = step;
    ui.steps.forEach((el, idx) => {
        el.classList.toggle('hidden', (idx + 1) !== step);
    });
    
    if (step === 3) {
        const addr = state.addresses.find(a => a.id == state.selectedAddressId);
        document.getElementById('review-address').innerHTML = `
            ${addr.recipient_name}<br>
            <span class="text-gray-400 font-bold uppercase text-[11px] tracking-widest leading-loose">
                ${addr.address_line1}, ${addr.city}<br>Phone: ${addr.phone}
            </span>
        `;
        document.getElementById('review-payment').textContent = state.paymentType === 'card' ? 'Secure Credit Card' : 'Cash on Delivery';
    }
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// --- Events ---
document.getElementById('btn-to-payment').onclick = () => goToStep(2);
document.getElementById('btn-to-review').onclick = () => goToStep(3);
document.getElementById('btn-back-to-delivery').onclick = () => goToStep(1);
document.getElementById('btn-back-to-payment').onclick = () => goToStep(2);

document.querySelectorAll('.payment-card').forEach(card => {
    card.onclick = () => {
        state.paymentType = card.dataset.value;
        document.querySelectorAll('.payment-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
    };
});

document.getElementById('btn-add-address').onclick = () => {
    document.getElementById('form-address').classList.remove('hidden');
    document.getElementById('address-container').classList.add('hidden');
};

document.getElementById('btn-cancel-address').onclick = () => {
    document.getElementById('form-address').classList.add('hidden');
    document.getElementById('address-container').classList.remove('hidden');
};

// --- Frontend validation (mirrors server-side CreateAddressRequest rules) ---
const validateAddressForm = (data) => {
    const errors = {};

    // recipient_name: optional but max 100
    if (data.recipient_name && data.recipient_name.length > 100) {
        errors.recipient_name = 'Recipient name must be 100 characters or less.';
    }

    // phone: required in this UI, must match /^\+?[0-9]{8,15}$/
    if (!data.phone || data.phone.trim() === '') {
        errors.phone = 'Phone number is required.';
    } else if (!/^\+?[0-9]{8,15}$/.test(data.phone.trim())) {
        errors.phone = 'Phone must be 8–15 digits (optionally starting with +).';
    }

    // address_line1: required, max 255
    if (!data.address_line1 || data.address_line1.trim() === '') {
        errors.address_line1 = 'Street address is required.';
    } else if (data.address_line1.length > 255) {
        errors.address_line1 = 'Street address must be 255 characters or less.';
    }

    // city: required, max 100
    if (!data.city || data.city.trim() === '') {
        errors.city = 'City is required.';
    } else if (data.city.length > 100) {
        errors.city = 'City must be 100 characters or less.';
    }

    // postal_code: required, max 20
    if (!data.postal_code || data.postal_code.trim() === '') {
        errors.postal_code = 'Postal code is required.';
    } else if (data.postal_code.length > 20) {
        errors.postal_code = 'Postal code must be 20 characters or less.';
    }

    return errors;
};

const showFormErrors = (form, errors) => {
    // Clear all previous errors
    form.querySelectorAll('.field-error').forEach(el => el.remove());
    form.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error', 'border-red-400'));

    Object.entries(errors).forEach(([field, message]) => {
        const input = form.querySelector(`[name="${field}"]`);
        if (!input) return;
        input.classList.add('border-red-400');
        const err = document.createElement('p');
        err.className = 'field-error text-[9px] font-bold text-red-500 uppercase tracking-widest mt-1 ml-1';
        err.textContent = message;
        input.parentNode.appendChild(err);
    });
};

document.getElementById('address-form').onsubmit = async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.user_id = state.userId;

    // Client-side validation
    const errors = validateAddressForm(data);
    if (Object.keys(errors).length > 0) {
        showFormErrors(e.target, errors);
        return;
    }

    // Clear any lingering errors before submit
    showFormErrors(e.target, {});

    btn.disabled = true;

    try {
        const res = await API.addresses.create(data);
        if (res.success) {
            toast.gold('Profile Registered');
            const listRes = await API.addresses.list(state.userId);
            state.addresses = listRes.data || listRes || [];
            state.selectedAddressId = res.data?.id || state.addresses[0]?.id;
            renderAddresses();
            ui.btnToPayment.disabled = false;
            document.getElementById('btn-cancel-address').click();
        }
    } catch (err) {
        // Show server-side errors inline if available
        if (err.errors && typeof err.errors === 'object') {
            showFormErrors(e.target, err.errors);
        } else {
            toast.error(err.message || 'Could not save address.');
        }
    } finally {
        btn.disabled = false;
    }
};

ui.placeOrderBtn.onclick = async () => {
    ui.placeOrderBtn.disabled = true;
    ui.placeOrderBtn.textContent = 'AUTHORIZING...';
    
    try {
        const items = cart.getItems();
        const total = cart.getTotal();
        
        // Prepare Order Payload
        const payload = {
            cart_id: state.cartId || 0, // Fallback if sync hasn't yielded ID yet
            user_id: state.userId,
            total_cents: total,
            shipping_address_id: state.selectedAddressId,
            billing_address_id: state.selectedAddressId,
            notes: document.getElementById('order-notes').value,
            items: items.map(item => ({
                product_id: item.id,
                product_name: item.name,
                price_cents: item.price_cents,
                quantity: item.quantity,
                product_image_url: item.image_url
            }))
        };

        const orderRes = await API.orders.create(payload);
        if (orderRes.success || orderRes.id) {
            const orderId = orderRes.id || orderRes.data?.id;

            // Handle Stripe Payment Redirect
            if (state.paymentType === 'card') {
                try {
                    const stripeRes = await API.payments.createStripeCheckout(orderId);
                    const checkoutUrl = stripeRes.data?.url || stripeRes.url;
                    if (checkoutUrl) {
                        window.location.href = checkoutUrl;
                        return; // Stop execution, browser will redirect
                    }
                } catch (err) {
                    throw new Error('Failed to initialize Stripe checkout: ' + (err.message || 'Unknown error'));
                }
            }

            document.getElementById('success-order-id').textContent = `#${orderId.toString().padStart(6, '0')}`;
            cart.clear(); // Wipe localStorage only on success
            goToStep(4);
            toast.gold('Transaction Complete');
        } else {
            throw new Error(orderRes.message || 'Order creation failed');
        }
    } catch (err) {
        toast.error(err.message || 'Payment failed. Please try again.');
        ui.placeOrderBtn.disabled = false;
        ui.placeOrderBtn.textContent = 'Place Order Now';
    }
};

// Start
init();
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f9f9f9; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #eee; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d4af37; }
    
    .payment-card.selected { border-color: #000; background: #fff; box-shadow: 0 20px 40px -20px rgba(0,0,0,0.1); }
    .payment-card.selected span:first-child { color: #d4af37; }
    
    .address-card:hover { border-color: #d4af37; opacity: 1; }

    /* Field-level validation error styles */
    .border-red-400 { border-color: #f87171 !important; }
    .field-error { color: #ef4444; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 4px; margin-left: 4px; }
</style>

<?php require_once __DIR__ . "/components/footer.php"; ?>
