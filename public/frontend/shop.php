<?php
$pageName = 'shop';
$pageTitle = 'Shop All - Royal Beverages';
require_once __DIR__ . "/components/header.php";
?>

<main class="min-h-screen bg-white">
    <?php
    $heroTitle = 'Shop All <br class="hidden md:block">Spirits';
    $heroSubtitle = 'Purveyors of Excellence';
    $heroDescription = 'Discover our meticulously curated collection of world-class spirits, rare releases, and artisanal craft beverages.';
    $heroId = 'shopHero';
    $heroOffset = '-17.5%';
    $heroBreadcrumbs = (new \App\UI\Breadcrumb())
        ->add('Home', BASE_URL)
        ->add('The Shop');
    require_once __DIR__ . '/components/animated-hero.php';
    ?>

    <div class="px-8 md:px-16 pb-32">
        <div class="flex flex-col lg:flex-row gap-16">
            <!-- Sidebar: Filters -->
            <aside class="w-full lg:w-80 shrink-0 space-y-12">
                <div class="flex items-center justify-between border-b border-black pb-4">
                    <h2 class="text-xs uppercase tracking-[0.3em] font-black">Filters</h2>
                    <button id="clearFilters" class="text-[9px] uppercase tracking-widest font-black text-gray-400 hover:text-red-600 transition-colors">Reset All</button>
                </div>

                <!-- Search Internal -->
                <div class="space-y-4">
                    <h3 class="text-[10px] uppercase tracking-widest font-black text-gray-400">Search Within</h3>
                    <div class="relative group">
                        <input type="text" id="internalSearch" placeholder="Type name..." class="w-full h-12 bg-gray-50 border-none outline-none px-4 text-xs font-bold uppercase tracking-widest placeholder:text-gray-300 focus:bg-white transition-colors">
                    </div>
                </div>

                <!-- Category Filter -->
                <div class="space-y-6">
                    <h3 class="text-[10px] uppercase tracking-widest font-black text-gray-400">Collections</h3>
                    <div class="flex flex-col gap-2" id="categoryFilters">
                        <!-- Populated via JS -->
                    </div>
                </div>

                <!-- Price Range -->
                <div class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h3 class="text-[10px] uppercase tracking-widest font-black text-gray-400">Price Ceiling</h3>
                        <span id="maxPriceLabel" class="text-xs font-bold font-heading text-black">Rs. 50,000+</span>
                    </div>
                    <input type="range" id="priceSlider" min="0" max="50000" step="500" value="50000" class="w-full h-1.5 bg-gray-100 appearance-none cursor-pointer accent-black">
                    <div class="flex justify-between text-[9px] uppercase tracking-widest text-gray-300 font-bold">
                        <span>Rs. 0</span>
                        <span>Rs. 50,000+</span>
                    </div>
                </div>

                <!-- Rating -->
                <div class="space-y-6">
                    <h3 class="text-[10px] uppercase tracking-widest font-black text-gray-400">Minimum Rating</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="rating" value="4" class="hidden peer">
                            <div class="peer-checked:bg-black peer-checked:text-white bg-gray-50 py-3 text-center text-[10px] font-black uppercase tracking-widest transition-all">4+ ★</div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="rating" value="3" class="hidden peer">
                            <div class="peer-checked:bg-black peer-checked:text-white bg-gray-50 py-3 text-center text-[10px] font-black uppercase tracking-widest transition-all">3+ ★</div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="rating" value="0" checked class="hidden peer">
                            <div class="peer-checked:bg-black peer-checked:text-white bg-gray-50 py-3 text-center text-[10px] font-black uppercase tracking-widest transition-all col-span-2">Any</div>
                        </label>
                    </div>
                </div>


            </aside>

            <!-- Main Listing Area -->
            <div class="flex-grow">
                <!-- Toolbar -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 mb-12 py-6 border-b border-gray-50">
                    <div id="resultsCount" class="text-[10px] uppercase tracking-[0.3em] font-black text-gray-400 italic">Searching collection...</div>
                    
                    <div class="flex items-center gap-6">
                        <!-- Checkboxes moved here -->
                        <div class="flex items-center gap-6 border-r border-gray-200 pr-6">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" id="inStockOnly" class="w-3.5 h-3.5 accent-black">
                                <span class="text-[9px] uppercase tracking-widest font-bold text-gray-400 group-hover:text-black transition-colors">In Stock Only</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" id="premiumOnly" class="w-3.5 h-3.5 accent-black">
                                <span class="text-[9px] uppercase tracking-widest font-bold text-gray-400 group-hover:text-black transition-colors">Vintage Reserve</span>
                            </label>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="text-[9px] uppercase tracking-widest text-gray-400 font-bold">Priority:</span>
                            <select id="sortSelect" class="bg-transparent border-none outline-none text-[10px] uppercase font-black tracking-widest cursor-pointer text-black hover:text-gray-500 transition-colors">
                                <option value="newest">Newest Arrival</option>
                                <option value="price_asc">Price Low-High</option>
                                <option value="price_desc">Price High-Low</option>
                                <option value="rating">Highest Rated</option>
                                <option value="popularity">Most Coveted</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Product Grid -->
                <div id="productsGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-x-8 gap-y-16">
                    <!-- Products loaded via JS -->
                </div>

                <!-- Empty State -->
                <div id="emptyState" class="hidden py-32 text-center flex-col items-center">
                    <div class="text-4xl mb-6">∅</div>
                    <h2 class="text-xs uppercase tracking-[0.3em] font-black mb-4">No Vintages Found</h2>
                    <p class="text-gray-400 text-sm italic font-light mb-8">Refine your search parameters to discover other spirits.</p>
                    <button id="resetFiltersBtn" class="btn-premium px-12">Reset Filters</button>
                </div>

                <!-- Load More -->
                <div id="loadMore" class="mt-20 flex justify-center hidden">
                    <button id="loadMoreBtn" class="btn-premium-outline px-16 h-14">Load More Selections</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script type="module">
import { API } from '<?= BASE_URL ?>assets/js/api-helper.js';
import { cart } from '<?= BASE_URL ?>assets/js/cart-service.js';
import { toast } from '<?= BASE_URL ?>assets/js/toast.js';
import { isInWishlist, toggleWishlistItem } from '<?= BASE_URL ?>assets/js/wishlist-storage.js';
import { renderProductCard, showErrorBoundary, initCustomDropdowns } from '<?= BASE_URL ?>assets/js/ui-components.js?v=<?= time() ?>';

let productsData = [];
let categoriesData = [];
let filteredProducts = [];
const filters = {
    category: null,
    minPrice: null,
    maxPrice: null,
    rating: 0,
    inStockOnly: false,
    premiumOnly: false,
    sortBy: 'newest',
    searchQuery: ''
};

const productsGrid = document.getElementById('productsGrid');
const resultsCount = document.getElementById('resultsCount');
const emptyState = document.getElementById('emptyState');
const categoryFilters = document.getElementById('categoryFilters');
const sortSelect = document.getElementById('sortSelect');



const init = async () => {
    initCustomDropdowns();
    await loadData();
    populateCategoryFilters();
    applyFilters();
    setupEventListeners();

    // Trigger hero shrink animation after 1 second
    setTimeout(() => {
        const hero = document.getElementById('shopHero');
        if (hero) hero.classList.add('shrunk');
    }, 1000);
};

const loadData = async () => {
    try {
        const [productsRes, categoriesRes] = await Promise.all([
            API.request('/products/enriched/all' + API.buildQuery({ limit: 200 })),
            API.categories.list()
        ]);
        
        if (!productsRes.success) throw new Error("Failed to load catalog");
        
        productsData = productsRes.success ? (productsRes.data.items || productsRes.data) : [];
        categoriesData = categoriesRes.success ? (categoriesRes.data.items || categoriesRes.data) : [];
    } catch (error) {
        console.error('[Shop] Data Fetch Failed:', error);
        showErrorBoundary("We couldn't load the spirits catalog. Please try again later.");
    }
};

const populateCategoryFilters = () => {
    categoryFilters.innerHTML = `
        <label class="group flex items-center justify-between cursor-pointer">
            <input type="radio" name="category" value="" checked class="hidden peer">
            <span class="text-[10px] uppercase font-bold tracking-widest peer-checked:text-black transition-colors">Select All</span>
            <span class="text-[9px] text-gray-300 font-bold">(${productsData.length})</span>
        </label>
        ${categoriesData.map(c => `
            <label class="group flex items-center justify-between cursor-pointer">
                <input type="radio" name="category" value="${c.id}" class="hidden peer">
                <span class="text-[10px] uppercase font-bold tracking-widest peer-checked:text-black transition-colors">${c.name}</span>
                <span class="text-[9px] text-gray-300 font-bold">(${productsData.filter(p => p.category_id == c.id).length})</span>
            </label>
        `).join('')}
    `;
};



const applyFilters = () => {
    filteredProducts = productsData.filter(p => {
        if (filters.category && p.category_id !== parseInt(filters.category)) return false;
        const price = p.price_cents / 100;
        if (filters.maxPrice && price > filters.maxPrice) return false;
        if (filters.rating > 0 && (parseFloat(p.avg_rating) || 0) < filters.rating) return false;
        if (filters.inStockOnly && p.available_stock <= 0) return false;
        if (filters.premiumOnly && p.price_cents < 10000) return false;
        if (filters.searchQuery && !p.name.toLowerCase().includes(filters.searchQuery.toLowerCase())) return false;
        return true;
    });

    sortProducts();
    renderProducts();
};

const sortProducts = () => {
    filteredProducts.sort((a, b) => {
        switch (filters.sortBy) {
            case 'price_asc': return a.price_cents - b.price_cents;
            case 'price_desc': return b.price_cents - a.price_cents;
            case 'rating': return (parseFloat(b.avg_rating) || 0) - (parseFloat(a.avg_rating) || 0);
            case 'popularity': return (b.units_sold || 0) - (a.units_sold || 0);
            default: return new Date(b.created_at || 0) - new Date(a.created_at || 0);
        }
    });
};

const renderProducts = () => {
    if (filteredProducts.length === 0) {
        productsGrid.classList.add('hidden');
        emptyState.classList.remove('hidden');
        resultsCount.textContent = 'No items found';
        return;
    }
    productsGrid.classList.remove('hidden');
    emptyState.classList.add('hidden');
    resultsCount.textContent = `${filteredProducts.length} items found`;
    productsGrid.innerHTML = filteredProducts.map(renderProductCard).join('');
};

const setupEventListeners = () => {
    categoryFilters.addEventListener('change', (e) => {
        if (e.target.name === 'category') {
            filters.category = e.target.value || null;
            applyFilters();
        }
    });

    document.getElementById('priceSlider').addEventListener('input', (e) => {
        filters.maxPrice = parseInt(e.target.value);
        const displayValue = parseInt(e.target.value).toLocaleString();
        document.getElementById('maxPriceLabel').textContent = `Rs. ${displayValue}${e.target.value == 50000 ? '+' : ''}`;
        applyFilters();
    });

    document.querySelectorAll('input[name="rating"]').forEach(input => {
        input.addEventListener('change', (e) => {
            filters.rating = parseInt(e.target.value);
            applyFilters();
        });
    });

    document.getElementById('inStockOnly').addEventListener('change', (e) => { filters.inStockOnly = e.target.checked; applyFilters(); });
    document.getElementById('premiumOnly').addEventListener('change', (e) => { filters.premiumOnly = e.target.checked; applyFilters(); });
    document.getElementById('internalSearch').addEventListener('input', (e) => { filters.searchQuery = e.target.value; applyFilters(); });
    sortSelect.addEventListener('change', (e) => { filters.sortBy = e.target.value; applyFilters(); });

    document.getElementById('clearFilters').addEventListener('click', clearFilters);
    document.getElementById('resetFiltersBtn').addEventListener('click', clearFilters);

    productsGrid.addEventListener('click', async (e) => {
        const addCartBtn = e.target.closest('.btn-add-cart');
        if (addCartBtn && !addCartBtn.disabled) {
            const productId = addCartBtn.dataset.id;
            await cart.add(productId, 1);
            toast.success('Added to Cart');
        }

        const wishlistBtn = e.target.closest('.btn-add-wishlist');
        if (wishlistBtn) {
            const id = wishlistBtn.dataset.id;
            const adding = !isInWishlist(id);
            // Optimistic UI — flip heart immediately
            const svg = wishlistBtn.querySelector('svg');
            if (svg) svg.setAttribute('fill', adding ? 'currentColor' : 'none');
            wishlistBtn.classList.toggle('text-red-500', adding);
            toggleWishlistItem(id); // fire-and-forget (toasts handled inside)
        }
    });
};

const clearFilters = () => {
    filters.category = null;
    filters.maxPrice = null;
    filters.rating = 0;
    filters.inStockOnly = false;
    filters.premiumOnly = false;
    filters.searchQuery = '';
    
    document.querySelectorAll('input[name="category"]')[0].checked = true;
    document.getElementById('priceSlider').value = 50000;
    document.getElementById('maxPriceLabel').textContent = 'Rs. 50,000+';
    document.querySelectorAll('input[name="rating"]').forEach(r => r.checked = r.value === '0');
    document.getElementById('inStockOnly').checked = false;
    document.getElementById('premiumOnly').checked = false;
    document.getElementById('internalSearch').value = '';
    applyFilters();
};

document.addEventListener('DOMContentLoaded', init);
</script>

<?php require_once __DIR__ . "/components/footer.php"; ?>
