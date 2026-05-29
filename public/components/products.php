<?php
require_once __DIR__ . "/header.php";
?>

<div class="section max-w-[1440px] mx-auto px-8">
    <div class="flex flex-col items-center mb-20">
        <span class="text-xs uppercase tracking-[0.4em] text-black font-extrabold mb-4 text-center">The Collection</span>
        <h2 class="text-4xl font-heading text-center uppercase tracking-[0.2em] font-extrabold">Our Finest Selection</h2>
    </div>

    <!-- Product Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-12 justify-center justify-items-center" id="productsContainer">
        <!-- Products will be inserted here by JS -->
    </div>
    
    <!-- Pagination Controls -->
    <div class="flex items-center justify-center gap-12 mt-24 py-8 border-t border-gray-100" id="pagination">
        <button class="group flex items-center gap-4 text-xs uppercase tracking-widest font-extrabold text-gray-400 hover:text-black transition-all disabled:opacity-20" id="prevPage" aria-label="Previous Page" disabled>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="group-hover:-translate-x-1 transition-transform">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            Prev
        </button>
        <div class="text-center">
            <div id="pageInfo" class="text-xs uppercase tracking-[0.3em] font-black mb-1">Page 1 of 1</div>
            <div id="productCount" class="text-[10px] uppercase tracking-widest text-gray-400">0 products found</div>
        </div>
        <button class="group flex items-center gap-4 text-xs uppercase tracking-widest font-extrabold text-gray-400 hover:text-black transition-all disabled:opacity-20" id="nextPage" aria-label="Next Page">
            Next
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="group-hover:translate-x-1 transition-transform">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </button>
    </div>
</div>

<!-- Product Detail Modal rewrite -->
</div>

<!-- Product Detail Modal rewrite -->
<div class="fixed inset-0 flex items-center justify-center z-[9999] opacity-0 invisible transition-all duration-500 bg-black/90 backdrop-blur-sm p-4 md:p-12 mb-0" id="detailModal">
    <div class="bg-white w-full max-w-[1100px] h-auto max-h-[95vh] flex flex-col lg:flex-row relative shadow-[0_0_50px_rgba(0,0,0,0.5)] scale-95 transition-transform duration-500 overflow-hidden" id="detailModalContent">
        <button class="absolute top-4 right-4 p-2 bg-black text-white hover:bg-gray-800 z-[100] transition-colors" id="detailCloseBtn">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        
        <!-- Left: Image -->
        <div class="w-full lg:w-1/2 bg-[#f4f4f4] flex items-center justify-center p-8 md:p-16 relative overflow-hidden h-[350px] lg:h-auto">
            <div id="modalBadge" class="absolute top-4 left-4 z-10 px-3 py-1 bg-black text-white text-[9px] font-bold uppercase tracking-widest"></div>
            <img id="modalImage" src="" alt="" class="w-full h-full object-contain max-h-[450px]" loading="eager">
        </div>
        
        <!-- Right: Info -->
        <div class="w-full lg:w-1/2 p-8 md:p-12 flex flex-col justify-center bg-white overflow-y-auto">
            <p id="modalCategory" class="text-[10px] uppercase font-bold tracking-[0.3em] text-gray-400 mb-2"></p>
            <h2 id="modalName" class="text-2xl md:text-3xl font-serif font-bold uppercase tracking-widest mb-4 leading-tight text-black"></h2>
            
            <div class="flex items-center gap-6 mb-8 pb-8 border-b border-gray-100">
                <span id="modalPrice" class="text-3xl font-bold tracking-tighter"></span>
                <div class="flex items-center gap-2" id="modalRating">
                    <!-- Stars injected here -->
                </div>
            </div>
            
            <div class="mb-8">
                <p id="modalDescription" class="text-gray-600 text-sm leading-relaxed italic font-light"></p>
            </div>
            
            <div class="py-4 border-y border-gray-50 mb-8 flex justify-between items-center">
                <span class="text-[9px] uppercase font-bold tracking-widest text-gray-400">Inventory Status</span>
                <span id="modalStock" class="text-[10px] font-bold uppercase"></span>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex items-center border border-gray-200 h-12 px-4 bg-gray-50 shrink-0">
                    <button onclick="this.parentNode.querySelector('input').stepDown()" class="px-2 hover:text-black transition-colors font-bold text-lg">−</button>
                    <input type="number" id="modalQuantity" value="1" min="1" class="bg-transparent border-none outline-none text-center font-bold w-10 text-sm">
                    <button onclick="this.parentNode.querySelector('input').stepUp()" class="px-2 hover:text-black transition-colors font-bold text-lg">+</button>
                </div>
                <div class="flex-grow flex gap-2">
                    <button id="modalAddToCart" class="bg-black text-white px-8 h-12 text-[10px] uppercase font-bold tracking-widest hover:bg-gray-800 transition-colors flex-grow">
                        Add to Cellar
                    </button>
                    <a id="viewFullDetailsLink" href="#" class="border border-black px-6 h-12 text-[10px] uppercase font-bold tracking-widest hover:bg-black hover:text-white transition-colors flex items-center justify-center">
                        Details
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="module">
    import { API } from '<?= BASE_URL ?>assets/js/api-helper.js';
    import { cart } from '<?= BASE_URL ?>assets/js/cart-service.js';
    import { toast } from '<?= BASE_URL ?>assets/js/toast.js';
    import { isInWishlist, toggleWishlistItem } from '<?= BASE_URL ?>assets/js/wishlist-storage.js';

    let productsData = [];
    const PRODUCTS_PER_PAGE = 12;
    let currentPage = 1;
    let totalPages = 1;

    // Helper to fix image paths
    const fixImagePath = (url) => {
        if (!url) return '<?= BASE_URL ?>assets/images/placeholder-product.png';
        if (url.includes('products/')) {
            const filename = url.split('/').pop();
            return '<?= BASE_URL ?>assets/images/' + filename;
        }
        return '<?= BASE_URL ?>assets/images/' + url.split('/').pop();
    };

    const fetchAllProducts = async () => {
        try {
            const response = await API.request('/products/enriched/all' + API.buildQuery({ limit: 100 }));
            if (response.success && response.data) {
                return response.data.items || response.data || [];
            }
            return [];
        } catch (error) {
            console.error('[Products] Error fetching products:', error);
            return [];
        }
    };

    const renderProductCard = (product) => {
        const isAvailable = product.is_available && product.available_stock > 0;
        const price = (product.price_cents / 100).toFixed(2);
        const isPremium = product.price_cents > 10000;
        
        let badgeHtml = '';
        if (isPremium) badgeHtml = 'Vintage';
        else if (product.available_stock < 20 && isAvailable) badgeHtml = `Low Stock: ${product.available_stock}`;

        return `
            <div class="group w-full bg-white border border-gray-100 p-8 flex flex-col relative overflow-hidden transition-all duration-500 hover:border-black ${!isAvailable ? 'opacity-40 grayscale' : ''}" data-id="${product.id}">
                <!-- Badges -->
                <div class="absolute top-6 left-6 z-10 flex flex-col gap-2">
                    ${!isAvailable ? `<span class="bg-gray-100 text-gray-500 text-[8px] font-black uppercase tracking-widest px-3 py-1">Depleted</span>` : ''}
                    ${badgeHtml ? `<span class="bg-black text-white text-[8px] font-black uppercase tracking-widest px-3 py-1 shadow-sm">${badgeHtml}</span>` : ''}
                </div>

                <!-- Image -->
                <a href="product.php?id=${product.id}" class="block h-56 mb-8 mt-4 relative flex items-center justify-center cursor-pointer btn-quick-view" data-id="${product.id}">
                    <img src="${fixImagePath(product.image_url)}" 
                         alt="${product.name}" 
                         class="max-h-full max-w-full object-contain transition-transform duration-700 group-hover:scale-110 drop-shadow-2xl" 
                         loading="lazy"
                         onerror="this.src='<?= BASE_URL ?>assets/images/placeholder-product.png'; this.onerror=null;">
                </a>

                <!-- Meta -->
                <div class="text-center flex flex-col flex-grow items-center justify-end w-full">
                    <span class="text-[9px] uppercase font-black tracking-[0.3em] text-gray-400 mb-2 truncate max-w-full block">
                        ${product.category_name || 'Spirit'}
                    </span>
                    <h3 class="text-sm font-heading uppercase tracking-widest mb-4 group-hover:text-gold transition-colors line-clamp-2 px-2">
                        ${product.name}
                    </h3>
                    <span class="text-xs font-black tracking-widest mb-8 uppercase">Rs. ${price}</span>
                    
                    <!-- Action Buttons -->
                    <div class="flex gap-2 w-full mt-auto">
                        <button class="btn-premium-outline flex-grow h-12 text-[9px] flex items-center justify-center btn-quick-view" data-id="${product.id}" style="padding: 0 0.5rem;">View Details</button>
                        <button class="btn-premium-outline w-12 h-12 flex-shrink-0 flex items-center justify-center btn-add-wishlist hover:bg-red-50 transition-colors" style="padding: 0;" data-id="${product.id}" title="Add to Wishlist">
                            <svg class="w-4 h-4" fill="${isInWishlist(product.id) ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </button>
                        <button class="btn-premium w-12 h-12 flex-shrink-0 flex items-center justify-center btn-add-cart" style="padding: 0;" data-id="${product.id}" ${!isAvailable ? 'disabled' : ''} title="Add to Cart">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
    };

    const renderProducts = (products) => {
        const container = document.getElementById('productsContainer');
        if (!products || products.length === 0) {
            container.innerHTML = '<div class="col-span-full py-32 text-center text-xs uppercase tracking-widest text-gray-400">No products match your criteria presently.</div>';
            return;
        }
        container.innerHTML = products.map(renderProductCard).join('');
    };

    const updatePagination = () => {
        totalPages = Math.ceil(productsData.length / PRODUCTS_PER_PAGE);
        const pageInfo = document.getElementById('pageInfo');
        const productCount = document.getElementById('productCount');
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const pagination = document.getElementById('pagination');
        
        pagination.classList.toggle('hidden', totalPages <= 1);
        pageInfo.textContent = `Scroll ${currentPage} / ${totalPages}`;
        productCount.textContent = `${productsData.length} Vintages Found`;
        
        prevBtn.disabled = currentPage <= 1;
        nextBtn.disabled = currentPage >= totalPages;
    };

    const openProductModal = (productId) => {
        const product = productsData.find(p => p.id === parseInt(productId));
        if (!product) return;
        
        const modal = document.getElementById('detailModal');
        const isAvailable = product.is_available && product.available_stock > 0;
        const price = (product.price_cents / 100).toFixed(2);
        
        document.getElementById('modalImage').src = fixImagePath(product.image_url);
        document.getElementById('modalName').textContent = product.name;
        document.getElementById('modalPrice').textContent = `Rs. ${price}`;
        document.getElementById('modalDescription').textContent = product.description;
        document.getElementById('modalCategory').textContent = product.category_name || 'Spirit';
        document.getElementById('modalStock').textContent = isAvailable ? `${product.available_stock} units in cellar` : 'Permanently Depleted';
        
        const badge = document.getElementById('modalBadge');
        badge.textContent = isAvailable ? (product.available_stock < 20 ? 'Critical Stock' : 'Secure Stock') : 'Out of Market';
        badge.className = isAvailable ? 'absolute top-8 left-8 z-10 px-4 py-1.5 bg-black text-white text-[10px] font-black uppercase tracking-widest shadow-lg' : 'absolute top-8 left-8 z-10 px-4 py-1.5 bg-gray-100 text-gray-400 text-[10px] font-black uppercase tracking-widest shadow-lg';
        
        const rating = parseFloat(product.avg_rating) || 0;
        const fullStars = Math.floor(rating);
        document.getElementById('modalRating').innerHTML = Array.from({length: 5}, (_, i) => 
            `<span class="${i < fullStars ? 'text-black' : 'text-gray-200'} text-lg">★</span>`
        ).join('') + `<span class="text-[10px] font-black uppercase tracking-widest ml-4">${rating} Rating</span>`;
        
        document.getElementById('modalAddToCart').dataset.id = product.id;
        document.getElementById('viewFullDetailsLink').href = `product.php?id=${product.id}`;
        
        modal.classList.remove('opacity-0', 'invisible');
        modal.classList.add('opacity-100', 'visible');
        document.getElementById('detailModalContent').classList.remove('scale-95');
        document.getElementById('detailModalContent').classList.add('scale-100');
        document.body.style.overflow = 'hidden';
    };

    const closeProductModal = () => {
        const modal = document.getElementById('detailModal');
        modal.classList.add('opacity-0', 'invisible');
        modal.classList.remove('opacity-100', 'visible');
        document.getElementById('detailModalContent').classList.add('scale-95');
        document.getElementById('detailModalContent').classList.remove('scale-100');
        document.body.style.overflow = '';
    };

    document.addEventListener('DOMContentLoaded', async () => {
        productsData = await fetchAllProducts();
        renderProducts(productsData.slice(0, PRODUCTS_PER_PAGE));
        updatePagination();
        
        document.getElementById('prevPage').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderProducts(productsData.slice((currentPage-1)*PRODUCTS_PER_PAGE, currentPage*PRODUCTS_PER_PAGE));
                updatePagination();
                document.getElementById('productsContainer').scrollIntoView({ behavior: 'smooth' });
            }
        });
        
        document.getElementById('nextPage').addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderProducts(productsData.slice((currentPage-1)*PRODUCTS_PER_PAGE, currentPage*PRODUCTS_PER_PAGE));
                updatePagination();
                document.getElementById('productsContainer').scrollIntoView({ behavior: 'smooth' });
            }
        });
        
        document.addEventListener('click', async (e) => {
            const ev = e.target;
            if (ev.closest('.btn-quick-view')) openProductModal(ev.closest('.btn-quick-view').dataset.id);
            if (ev.closest('#detailCloseBtn') || ev.id === 'detailModal') closeProductModal();
            
            if (ev.closest('.btn-add-cart') || ev.closest('#modalAddToCart')) {
                const btn = ev.closest('.btn-add-cart') || ev.closest('#modalAddToCart');
                const productId = parseInt(btn.dataset.id);
                const qty = parseInt(document.getElementById('modalQuantity')?.value) || 1;
                
                await cart.add(productId, qty);
                toast.success('Successfully added to cart');
                if (btn.id === 'modalAddToCart') closeProductModal();
            }

            if (ev.closest('.btn-add-wishlist')) {
                const btn = ev.closest('.btn-add-wishlist');
                const id = btn.dataset.id;
                const adding = !isInWishlist(id);
                // Optimistic UI — flip heart immediately
                const svg = btn.querySelector('svg');
                if (svg) svg.setAttribute('fill', adding ? 'currentColor' : 'none');
                toggleWishlistItem(id); // toasts handled inside wishlist-storage.js
            }
        });
        
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeProductModal(); });
    });
</script>
