<?php
/**
 * MyAccount Orders
 * Order history archival ledger
 */
$pageName = 'account';
$pageTitle = 'Order History - Royal Beverages';
require_once __DIR__ . "/_layout.php";
?>

<div class="space-y-16">
    <!-- Header -->
    <header>
    <header>
        <span class="text-xs uppercase tracking-[0.4em] text-gold font-extrabold mb-4 block italic">History</span>
        <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tight leading-none">Your <br>Orders</h1>
    </header>
    </header>

    <!-- Procurement Ledger Cabinet -->
    <section class="bg-white border border-gray-100 min-h-[400px] flex flex-col">
        <div class="p-10 border-b border-gray-100 flex items-center justify-between bg-gray-50/30">
            <h2 class="text-[10px] uppercase font-black tracking-[0.3em]">Status</h2>
            <div class="flex items-center gap-8">
                <span class="text-[9px] uppercase font-bold text-gray-400 tracking-widest" id="ledgerStatus">Syncing...</span>
            </div>
        </div>

        <div id="ordersList" class="divide-y divide-gray-50 flex-grow">
            <!-- Populated via JS -->
            <div class="p-20 text-center flex flex-col items-center justify-center min-h-[300px]">
                <div class="w-16 h-16 border border-gray-100 flex items-center justify-center mb-8 opacity-20">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <p class="text-[11px] uppercase tracking-widest text-gray-400 font-bold">No orders found in your history</p>
                <a href="<?= getPageUrl('shop') ?>" class="mt-8 inline-block border border-black px-12 py-4 text-[10px] uppercase font-black tracking-widest hover:bg-black hover:text-white transition-all">Browse Collection</a>
            </div>
        </div>

        <div class="p-10 border-t border-gray-100 bg-gray-50/30">
            <p class="text-[8px] uppercase tracking-[0.4em] text-center text-gray-300 font-bold">Secure Account Area • Royal Beverages</p>
        </div>
    </section>
</div>

<script type="module">
import { fetchUserOrders } from '<?= BASE_URL ?>assets/js/orders.js';

async function renderOrders() {
    const container = document.getElementById('ordersList');
    const statusText = document.getElementById('ledgerStatus');
    
    statusText.textContent = 'SYNCING LEDGER...';
    
    const userId = <?= json_encode($session->getUserId() ?? null) ?>;
    if (!userId) {
        statusText.textContent = 'UNAUTHORIZED';
        return;
    }

    const orders = await fetchUserOrders(userId);
    
    if (orders.length === 0) {
        statusText.textContent = 'VACANT';
        return;
    }

    statusText.textContent = `${orders.length} ENTRIES FOUND`;
    
    const statusClasses = {
        pending: 'bg-amber-100 text-amber-700',
        processing: 'bg-blue-100 text-blue-700',
        shipped: 'bg-indigo-100 text-indigo-700',
        delivered: 'bg-emerald-100 text-emerald-700',
        cancelled: 'bg-rose-100 text-rose-700'
    };
    
    container.innerHTML = orders.map(order => `
        <div class="p-12 hover:bg-[#fafafa] transition-all duration-300 group">
            <div class="lg:grid lg:grid-cols-[1fr_200px_200px_150px_50px] items-center gap-12">
                <!-- Entry ID & Date -->
                <div class="flex flex-col gap-1 mb-6 lg:mb-0">
                    <span class="text-[12px] font-black uppercase tracking-widest">Order #${order.id.toString().padStart(6, '0')}</span>
                    <span class="text-[9px] uppercase font-extrabold text-gray-400 tracking-widest">Date: ${new Date(order.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }).toUpperCase()}</span>
                </div>

                <!-- Volume -->
                <div class="mb-4 lg:mb-0">
                    <span class="text-[9px] uppercase font-black text-gray-300 tracking-[0.3em] block mb-1 uppercase">Selections</span>
                    <span class="text-[11px] font-bold uppercase tracking-widest">${order.item_count || 0} ITEMS</span>
                </div>

                <!-- Valuation -->
                <div class="mb-6 lg:mb-0">
                    <span class="text-[9px] uppercase font-black text-gray-300 tracking-[0.3em] block mb-1 uppercase">Order Total</span>
                    <span class="text-xl font-black tracking-tighter text-gold">$${(order.total_cents / 100).toFixed(2)}</span>
                </div>

                <!-- Current State -->
                <div class="mb-8 lg:mb-0">
                    <div class="inline-block px-4 py-1.5 text-[8px] font-black uppercase tracking-[0.2em] rounded-sm ${statusClasses[order.status] || 'bg-gray-100 text-gray-700'}">
                        ${order.status}
                    </div>
                </div>

                <!-- Navigation -->
                <div class="flex justify-end lg:block">
                    <button class="w-10 h-10 border border-gray-100 flex items-center justify-center hover:bg-black hover:text-white transition-all duration-500 opacity-0 group-hover:opacity-100 btn-view-details" data-id="${order.id}" title="View Details">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>
        </div>
    `).reverse().join('');
}

document.addEventListener('DOMContentLoaded', renderOrders);
</script>

<!-- Order Details Modal (Overlay) -->
<div id="orderDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 opacity-0 pointer-events-none transition-all duration-300 backdrop-blur-sm">
    <div class="bg-white border border-gray-100 shadow-2xl max-w-2xl w-full max-h-[85vh] flex flex-col relative overflow-hidden">
        <!-- Close Button -->
        <button id="closeModalBtn" class="absolute top-6 right-6 text-gray-400 hover:text-black transition-colors z-10">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <!-- Modal Header -->
        <div class="p-8 border-b border-gray-50 flex flex-col gap-1">
            <span class="text-[9px] uppercase font-black tracking-[0.4em] text-gold italic">Procurement Details</span>
            <h3 class="text-2xl font-black uppercase tracking-tight" id="modalOrderNumber">Order #000000</h3>
        </div>

        <!-- Modal Scrollable Content -->
        <div class="p-8 overflow-y-auto flex-grow space-y-8" id="modalContent">
            <!-- Loader / Spinner -->
            <div class="flex items-center justify-center py-20">
                <span class="text-[9px] uppercase font-black tracking-widest text-gray-300 animate-pulse">Retrieving Ledger...</span>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-8 bg-gray-50/50 border-t border-gray-50 flex gap-4 justify-between items-center">
            <button id="cancelOrderBtn" class="px-6 h-12 bg-red-600 text-white text-[9px] uppercase font-black tracking-widest hover:bg-red-700 transition-colors hidden shadow-lg shadow-red-500/10">
                Request Cancellation
            </button>
            <button id="closeModalFooterBtn" class="px-8 h-12 border border-gray-200 text-[9px] uppercase font-black tracking-widest hover:bg-black hover:text-white hover:border-black transition-all ml-auto">
                Close Details
            </button>
        </div>
    </div>
</div>

<script type="module">
import { fetchUserOrders } from '<?= BASE_URL ?>assets/js/orders.js';
import { API } from '<?= BASE_URL ?>assets/js/api-helper.js';
import { toast } from '<?= BASE_URL ?>assets/js/toast.js';

const modal = document.getElementById('orderDetailModal');
const modalContent = document.getElementById('modalContent');
const modalOrderNumber = document.getElementById('modalOrderNumber');
const cancelBtn = document.getElementById('cancelOrderBtn');

let activeOrderId = null;

const fixImagePath = (url) => {
    if (!url) return '<?= BASE_URL ?>assets/images/placeholder-product.png';
    if (url.includes('products/')) {
        const filename = url.split('/').pop();
        return '<?= BASE_URL ?>assets/images/' + filename;
    }
    return '<?= BASE_URL ?>assets/images/' + url.split('/').pop();
};

const openModal = async (orderId) => {
    activeOrderId = orderId;
    modalOrderNumber.textContent = `Order #${orderId.toString().padStart(6, '0')}`;
    cancelBtn.classList.add('hidden');
    modalContent.innerHTML = `
        <div class="flex items-center justify-center py-20">
            <span class="text-[9px] uppercase font-black tracking-widest text-gray-300 animate-pulse">Retrieving Ledger...</span>
        </div>
    `;
    modal.classList.remove('opacity-0', 'pointer-events-none');

    try {
        const res = await API.request('/orders/' + orderId + '/enriched');
        const order = res.data || res;
        
        if (!order) {
            modalContent.innerHTML = `<p class="text-xs uppercase font-black tracking-widest text-red-500 text-center py-10">Ledger details missing.</p>`;
            return;
        }

        // Show cancel button if cancellable (pending or processing)
        if (['pending', 'processing'].includes(order.status)) {
            cancelBtn.classList.remove('hidden');
        }

        // Render Address block
        const addr = order.shipping_address || {};
        const addressHtml = addr.recipient_name ? `
            <div>
                <h4 class="text-[9px] uppercase font-black tracking-widest text-gray-300 mb-2 italic">Delivery Ledger</h4>
                <p class="text-[11px] font-bold uppercase tracking-wider text-black leading-relaxed">
                    ${addr.recipient_name}<br>
                    ${addr.address_line1}${addr.address_line2 ? ', ' + addr.address_line2 : ''}<br>
                    ${addr.city}, ${addr.postal_code}<br>
                    Phone: ${addr.phone}
                </p>
            </div>
        ` : '';

        // Render Selections list
        const itemsHtml = (order.items || []).map(item => `
            <div class="flex gap-6 py-4 border-b border-gray-50 last:border-0 items-center">
                <div class="w-16 h-16 bg-gray-50 flex-shrink-0 border border-gray-100 overflow-hidden">
                    <img src="${fixImagePath(item.product_image_url)}" class="w-full h-full object-contain grayscale hover:grayscale-0 transition-all duration-300" onerror="this.src='<?= BASE_URL ?>assets/images/placeholder-product.png'">
                </div>
                <div class="flex-grow">
                    <span class="block text-[8px] uppercase font-black text-gray-300 tracking-[0.2em] mb-0.5">${item.warehouse_name || 'Central Warehouse'}</span>
                    <span class="block text-[11px] font-black uppercase tracking-tight">${item.product_name}</span>
                    <span class="block text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-1">QTY: ${item.quantity} × $${(item.price_cents / 100).toFixed(2)}</span>
                </div>
                <span class="text-xs font-black tracking-widest ml-auto">$${((item.price_cents * item.quantity) / 100).toFixed(2)}</span>
            </div>
        `).join('');

        // Subtotal & Total
        const total = (order.total_cents / 100).toFixed(2);

        modalContent.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 border-b border-gray-50 pb-8">
                ${addressHtml}
                <div>
                    <h4 class="text-[9px] uppercase font-black tracking-widest text-gray-300 mb-2 italic">Valuation Status</h4>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-black leading-relaxed">
                        State: <span class="text-gold italic">${order.status.toUpperCase()}</span><br>
                        Created: ${new Date(order.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }).toUpperCase()}
                    </p>
                </div>
            </div>
            
            <div>
                <h4 class="text-[9px] uppercase font-black tracking-widest text-gray-300 mb-4 italic font-black">Acquisitions</h4>
                <div class="divide-y divide-gray-50">
                    ${itemsHtml}
                </div>
            </div>

            <div class="pt-6 border-t border-black space-y-3">
                <div class="flex justify-between items-center text-[9px] uppercase font-black tracking-widest text-gray-400">
                    <span>Shipping</span>
                    <span class="text-emerald-600 italic">Complimentary</span>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-xs uppercase font-black tracking-widest italic">Acquisition Value</span>
                    <span class="text-2xl font-black tracking-tighter text-gold">$${total}</span>
                </div>
            </div>
        `;

    } catch (err) {
        modalContent.innerHTML = `<p class="text-xs uppercase font-black tracking-widest text-red-500 text-center py-10">Error retrieving details.</p>`;
        console.error(err);
    }
};

const closeModal = () => {
    modal.classList.add('opacity-0', 'pointer-events-none');
    activeOrderId = null;
};

// Event delegation for order list details click
document.getElementById('ordersList').addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-view-details');
    if (btn) {
        openModal(btn.dataset.id);
    }
});

// Close actions
document.getElementById('closeModalBtn').onclick = closeModal;
document.getElementById('closeModalFooterBtn').onclick = closeModal;
modal.onclick = (e) => {
    if (e.target === modal) closeModal();
};

// Cancel action
cancelBtn.onclick = async () => {
    if (!activeOrderId) return;
    if (!confirm('Are you sure you wish to cancel this acquisition ledger?')) return;
    
    cancelBtn.disabled = true;
    cancelBtn.textContent = 'CANCELLING...';

    try {
        const res = await API.request('/orders/' + activeOrderId + '/cancel', { method: 'POST' });
        if (res.success || res.status === 'cancelled') {
            toast.gold('Ledger Cancelled');
            closeModal();
            // Refresh list
            const container = document.getElementById('ordersList');
            const statusText = document.getElementById('ledgerStatus');
            statusText.textContent = 'SYNCING LEDGER...';
            const userId = <?= json_encode($session->getUserId() ?? null) ?>;
            const orders = await fetchUserOrders(userId);
            statusText.textContent = `${orders.length} ENTRIES FOUND`;
            
            const statusClasses = {
                pending: 'bg-amber-100 text-amber-700',
                processing: 'bg-blue-100 text-blue-700',
                shipped: 'bg-indigo-100 text-indigo-700',
                delivered: 'bg-emerald-100 text-emerald-700',
                cancelled: 'bg-rose-100 text-rose-700'
            };
            
            container.innerHTML = orders.map(order => `
                <div class="p-12 hover:bg-[#fafafa] transition-all duration-300 group">
                    <div class="lg:grid lg:grid-cols-[1fr_200px_200px_150px_50px] items-center gap-12">
                        <!-- Entry ID & Date -->
                        <div class="flex flex-col gap-1 mb-6 lg:mb-0">
                            <span class="text-[12px] font-black uppercase tracking-widest">Order #${order.id.toString().padStart(6, '0')}</span>
                            <span class="text-[9px] uppercase font-extrabold text-gray-400 tracking-widest">Date: ${new Date(order.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }).toUpperCase()}</span>
                        </div>

                        <!-- Volume -->
                        <div class="mb-4 lg:mb-0">
                            <span class="text-[9px] uppercase font-black text-gray-300 tracking-[0.3em] block mb-1 uppercase">Selections</span>
                            <span class="text-[11px] font-bold uppercase tracking-widest">${order.item_count || 0} ITEMS</span>
                        </div>

                        <!-- Valuation -->
                        <div class="mb-6 lg:mb-0">
                            <span class="text-[9px] uppercase font-black text-gray-300 tracking-[0.3em] block mb-1 uppercase">Order Total</span>
                            <span class="text-xl font-black tracking-tighter text-gold">$${(order.total_cents / 100).toFixed(2)}</span>
                        </div>

                        <!-- Current State -->
                        <div class="mb-8 lg:mb-0">
                            <div class="inline-block px-4 py-1.5 text-[8px] font-black uppercase tracking-[0.2em] rounded-sm ${statusClasses[order.status] || 'bg-gray-100 text-gray-700'}">
                                ${order.status}
                            </div>
                        </div>

                        <!-- Navigation -->
                        <div class="flex justify-end lg:block">
                            <button class="w-10 h-10 border border-gray-100 flex items-center justify-center hover:bg-black hover:text-white transition-all duration-500 opacity-0 group-hover:opacity-100 btn-view-details" data-id="${order.id}" title="View Details">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            `).reverse().join('');
        }
    } catch (err) {
        toast.error('Failed to cancel order.');
        console.error(err);
    } finally {
        cancelBtn.disabled = false;
        cancelBtn.textContent = 'Request Cancellation';
    }
};
</script>

<?php require_once __DIR__ . "/_layout_end.php"; ?>
