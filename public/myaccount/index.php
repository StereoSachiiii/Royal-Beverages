<?php
/**
 * MyAccount Dashboard
 * Main account overview page
 */
$pageName = 'account';
$pageTitle = 'My Account - Royal Beverages';
require_once __DIR__ . "/_layout.php";
?>

<div class="space-y-16">
    <!-- Header -->
    <header>
        <span class="text-xs uppercase tracking-[0.4em] text-gold font-extrabold mb-4 block italic">Dashboard</span>
        <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tight leading-none">Your <br>Account</h1>
    </header>

    <!-- Quick Stats Registry -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
        <div class="p-10 bg-white border border-gray-100 flex flex-col justify-between group hover:border-gold transition-colors">
            <span class="text-[9px] uppercase font-black tracking-[0.4em] text-gray-400 mb-8 block">Orders</span>
            <div class="flex items-baseline justify-between">
                <span class="text-4xl font-black tracking-tight" id="orderCount">-</span>
                <svg class="w-6 h-6 text-gray-50 group-hover:text-gold/20 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
        </div>

        <div class="p-10 bg-white border border-gray-100 flex flex-col justify-between group hover:border-gold transition-colors">
            <span class="text-[9px] uppercase font-black tracking-[0.4em] text-gray-400 mb-8 block">Wishlist</span>
            <div class="flex items-baseline justify-between">
                <span class="text-4xl font-black tracking-tight" id="wishlistCount">-</span>
                <svg class="w-6 h-6 text-gray-50 group-hover:text-gold/20 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            </div>
        </div>

        <div class="p-10 bg-white border border-gray-100 flex flex-col justify-between group hover:border-gold transition-colors">
            <span class="text-[9px] uppercase font-black tracking-[0.4em] text-gray-400 mb-8 block">Addresses</span>
            <div class="flex items-baseline justify-between">
                <span class="text-4xl font-black tracking-tight" id="addressCount">-</span>
                <svg class="w-6 h-6 text-gray-50 group-hover:text-gold/20 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
        </div>


    </div>

    <!-- Archival Ledger (Recent Orders) -->
    <section class="bg-white border border-gray-100">
        <div class="p-10 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-xs uppercase font-black tracking-[0.3em]">Recent Orders</h2>
            <a href="<?= BASE_URL ?>myaccount/orders.php" class="text-[9px] uppercase font-black tracking-widest text-gold hover:text-black transition-colors">View All Orders</a>
        </div>
        <div id="recentOrders" class="divide-y divide-gray-50">
            <!-- Populated via JS -->
            <div class="p-20 text-center">
                <p class="text-[11px] uppercase tracking-widest text-gray-400 font-bold mb-8">No Recent Orders</p>
                <a href="<?= getPageUrl('shop') ?>" class="inline-block border border-black px-10 py-4 text-[10px] uppercase font-black tracking-widest hover:bg-black hover:text-white transition-all">Start Shopping</a>
            </div>
        </div>
    </section>

    <!-- Rapid Access Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <a href="<?= BASE_URL ?>myaccount/addresses.php" class="p-12 border border-gray-100 bg-gray-50/50 hover:bg-white hover:border-gold transition-all duration-300 group flex items-center gap-8">
            <div class="w-12 h-12 flex items-center justify-center border border-gray-200 group-hover:bg-black group-hover:text-white transition-all duration-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </div>
            <div>
                <span class="block text-xs uppercase font-black tracking-widest mb-1">Manage Addresses</span>
                <span class="block text-[10px] uppercase font-bold text-gray-400 tracking-widest">Update Delivery Locations</span>
            </div>
        </a>

        <a href="<?= getPageUrl('shop') ?>" class="p-12 border border-gray-100 bg-gray-50/50 hover:bg-white hover:border-gold transition-all duration-300 group flex items-center gap-8">
            <div class="w-12 h-12 flex items-center justify-center border border-gray-200 group-hover:bg-black group-hover:text-white transition-all duration-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <span class="block text-xs uppercase font-black tracking-widest mb-1">Store</span>
                <span class="block text-[10px] uppercase font-bold text-gray-400 tracking-widest">Return to The Main Collection</span>
            </div>
        </a>
    </div>
</div>

<script type="module">
import { getWishlist } from '<?= BASE_URL ?>assets/js/wishlist-storage.js';
import { fetchUserOrders } from '<?= BASE_URL ?>assets/js/orders.js';
import { API } from '<?= BASE_URL ?>assets/js/api-helper.js';

async function loadDashboard() {
    const userId = <?= json_encode($session->getUserId() ?? null) ?>;
    if (!userId) return;

    // Wishlist
    const wishlist = getWishlist();
    document.getElementById('wishlistCount').textContent = wishlist.length;
    
    // Orders
    try {
        const orders = await fetchUserOrders(userId);
        const orderList = Array.isArray(orders) ? orders : [];
        document.getElementById('orderCount').textContent = orderList.length;
        renderRecentOrders(orderList.slice(0, 3));
    } catch(e) {
        document.getElementById('orderCount').textContent = '0';
    }

    // Addresses
    try {
        const result = await API.addresses.list(userId);
        const addresses = result?.data || [];
        document.getElementById('addressCount').textContent = addresses.length;
    } catch(e) {
        document.getElementById('addressCount').textContent = '0';
    }
}

function renderRecentOrders(orders) {
    const container = document.getElementById('recentOrders');
    if (!orders || orders.length === 0) return;
    
    const statusClasses = {
        pending: 'bg-amber-100 text-amber-700',
        processing: 'bg-blue-100 text-blue-700',
        shipped: 'bg-indigo-100 text-indigo-700',
        delivered: 'bg-emerald-100 text-emerald-700',
        cancelled: 'bg-rose-100 text-rose-700'
    };
    
    container.innerHTML = orders.map(order => `
        <div class="flex items-center justify-between p-10 hover:bg-gray-50 transition-colors group">
            <div class="flex flex-col gap-1">
                <span class="text-[11px] font-black uppercase tracking-widest">Order #${order.id.toString().padStart(6, '0')}</span>
                <span class="text-[9px] uppercase font-bold text-gray-400 tracking-widest">${new Date(order.created_at || order.createdAt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).toUpperCase()}</span>
            </div>
            <div class="flex items-center gap-12">
                <div class="text-right flex flex-col gap-1">
                    <span class="text-[10px] font-black tracking-widest text-gold">$${((order.total_cents || order.total) / 100).toFixed(2)}</span>
                    <span class="text-[9px] font-bold text-gray-300 uppercase tracking-widest">${order.items?.length || order.item_count || 0} ITEMS</span>
                </div>
                <div class="px-6 py-2 text-[8px] font-black uppercase tracking-[0.2em] rounded-sm ${statusClasses[order.status] || 'bg-gray-100 text-gray-700'}">
                    ${order.status}
                </div>
                <a href="<?= BASE_URL ?>myaccount/orders.php" class="p-2 text-gray-200 hover:text-black transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    `).join('');
}

document.addEventListener('DOMContentLoaded', loadDashboard);
</script>

<?php require_once __DIR__ . "/_layout_end.php"; ?>
