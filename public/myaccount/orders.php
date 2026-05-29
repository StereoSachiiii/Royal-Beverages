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
                    <button class="w-10 h-10 border border-gray-100 flex items-center justify-center hover:bg-black hover:text-white transition-all duration-500 opacity-0 group-hover:opacity-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>
        </div>
    `).reverse().join('');
}

document.addEventListener('DOMContentLoaded', renderOrders);
</script>

<?php require_once __DIR__ . "/_layout_end.php"; ?>
