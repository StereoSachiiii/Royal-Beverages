<?php
$pageName = 'category';
$pageTitle = 'Category - Royal Beverages';
require_once __DIR__ . "/components/header.php";

$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main class="category-page">
    <div class="container">
        <?php
        $heroTitle = '<span id="heroTitle">Loading...</span>';
        $heroSubtitle = 'Collection';
        $heroDescription = '';
        $heroId = 'categoryHero';
        $heroOffset = '15%';
        $heroBreadcrumbs = (new \App\UI\Breadcrumb())
            ->add('Home', BASE_URL)
            ->add('The Shop', BASE_URL . 'shop.php')
            ->add('<span id="breadcrumbCategory">Collection</span>');
        
        $heroExtraHtml = '
        <p class="text-gray-500 font-light italic max-w-2xl text-lg mb-8" id="heroDescription"></p>
        ';
        
        require_once __DIR__ . '/components/animated-hero.php';
        ?>

        <!-- Category Stats -->
        <div class="flex items-center justify-center gap-16 py-12 border-b border-gray-100 w-full max-w-2xl mx-auto mb-16">
            <div class="flex flex-col text-center">
                <span class="text-3xl font-heading font-black mb-1 text-black" id="productCount">0</span>
                <span class="text-[9px] uppercase tracking-widest text-gray-400 font-black">Vintages</span>
            </div>
            <div class="w-px h-10 bg-gray-200"></div>
            <div class="flex flex-col text-center">
                <span class="text-3xl font-heading font-black mb-1 text-black" id="priceRange">$0 - $0</span>
                <span class="text-[9px] uppercase tracking-widest text-gray-400 font-black">Price Range</span>
            </div>
            <div class="w-px h-10 bg-gray-200"></div>
            <div class="flex flex-col text-center">
                <span class="text-3xl font-heading font-black mb-1 text-black" id="avgRating">—</span>
                <span class="text-[9px] uppercase tracking-widest text-gray-400 font-black">Rating</span>
            </div>
        </div>

        <!-- Flavor Profile: High Contrast Radar Chart -->
        <section id="flavorSummary" class="hidden mb-16 p-10 bg-gray-50/50 border border-gray-100/50 max-w-4xl mx-auto mt-16">
            <div class="flex flex-col items-center mb-8 text-center">
                <span class="text-[10px] uppercase tracking-[0.4em] text-black font-black mb-2">Category Average</span>
                <h3 class="text-2xl font-heading uppercase tracking-widest italic">Tasting Archetype</h3>
            </div>
            <div class="relative h-[400px] w-full flex items-center justify-center">
                <canvas id="flavorChart"></canvas>
            </div>
            <div id="flavorTags" class="mt-8 flex flex-wrap justify-center gap-2"></div>
        </section>

        <!-- Products Section -->
        <section class="mb-32 max-w-[1440px] mx-auto px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-12 border-b border-gray-100 pb-6">
                <h2 class="text-2xl font-serif italic text-black">Products in this Collection</h2>
                <div class="mt-4 sm:mt-0 flex items-center gap-3">
                    <span class="text-[9px] uppercase tracking-widest text-gray-400 font-bold">Sort By:</span>
                    <select id="sortSelect" class="bg-transparent border-none outline-none text-[10px] uppercase font-black tracking-widest cursor-pointer text-black hover:text-gray-500 transition-colors">
                        <option value="newest">Newest Arrival</option>
                        <option value="price_asc">Price Low-High</option>
                        <option value="price_desc">Price High-Low</option>
                        <option value="name_asc">A-Z</option>
                        <option value="rating">Highest Rated</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-8" id="productsGrid">
                <!-- Skeleton Loader -->
                <div class="col-span-full grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-8">
                    <div class="bg-gray-50 rounded-lg aspect-[3/4] animate-pulse"></div>
                    <div class="bg-gray-50 rounded-lg aspect-[3/4] animate-pulse"></div>
                    <div class="bg-gray-50 rounded-lg aspect-[3/4] animate-pulse"></div>
                    <div class="bg-gray-50 rounded-lg aspect-[3/4] animate-pulse"></div>
                </div>
            </div>

            <div id="emptyState" class="hidden py-32 text-center flex flex-col items-center">
                <div class="text-4xl mb-6">∅</div>
                <h2 class="text-xs uppercase tracking-[0.3em] font-black mb-4">No Products Found</h2>
                <p class="text-gray-400 text-sm italic font-light mb-8">Check back soon for new arrivals in this collection.</p>
                <a href="<?= BASE_URL ?>shop.php" class="btn-premium px-12">Browse All Products</a>
            </div>
        </section>

        <!-- Related Categories -->
        <?php
            $categoriesSectionTitle = 'Explore Other Collections';
            $categoriesSectionSubtitle = 'Discover More';
            $categoriesExcludeId = $categoryId;
            require_once __DIR__ . '/components/categories.php';
        ?>
    </div>
</main>



<script type="module">
import { API } from '<?= BASE_URL ?>assets/js/api-helper.js?v=<?= time() ?>';
import { cart } from '<?= BASE_URL ?>assets/js/cart-service.js?v=<?= time() ?>';
import { toast } from '<?= BASE_URL ?>assets/js/toast.js?v=<?= time() ?>';
import { isInWishlist, toggleWishlistItem } from '<?= BASE_URL ?>assets/js/wishlist-storage.js?v=<?= time() ?>';
import { renderProductCard, showErrorBoundary, initCustomDropdowns } from '<?= BASE_URL ?>assets/js/ui-components.js?v=<?= time() ?>';

const categoryId = <?= $categoryId ?>;
let productsData = [];
let categoryData = null;
let allCategories = [];

// Initialize
const init = async () => {
    initCustomDropdowns();
    
    if (!categoryId) {
        showError('Invalid category');
        return;
    }
    
    await loadData();
    renderCategory();
    renderProducts();
    renderRelatedCategories();
    setupEventListeners();

    // Trigger hero shrink animation after 1 second
    setTimeout(() => {
        const hero = document.getElementById('categoryHero');
        if (hero) hero.classList.add('shrunk');
    }, 1000);
};

// Load data
const loadData = async () => {
    try {
        const [productsRes, categoriesRes] = await Promise.all([
            API.products.getByCategory(categoryId),
            API.categories.list()
        ]);
        
        if (!productsRes.success || !categoriesRes.success) {
            throw new Error("Failed to load category information");
        }

        allCategories = categoriesRes.data || [];
        categoryData = allCategories.find(c => c.id === categoryId);
        
        productsData = productsRes.data?.items || productsRes.data || [];
    } catch (error) {
        console.error('[Category] Failed to load data:', error);
        showErrorBoundary("We couldn't load this category right now. Please try again later.");
    }
};

// Render category hero
const renderCategory = () => {
    if (!categoryData) {
        showError('Category not found');
        return;
    }

    // Update breadcrumb
    document.getElementById('breadcrumbCategory').textContent = categoryData.name;
    
    // Update hero
    document.getElementById('heroTitle').textContent = categoryData.name;
    document.getElementById('heroDescription').textContent = 
        categoryData.description || `Explore our collection of premium ${categoryData.name.toLowerCase()}.`;
    
    // Calculate stats
    document.getElementById('productCount').textContent = productsData.length;
    
    if (productsData.length > 0) {
        const prices = productsData.map(p => p.price_cents / 100);
        const minPrice = Math.min(...prices).toFixed(0);
        const maxPrice = Math.max(...prices).toFixed(0);
        document.getElementById('priceRange').textContent = `$${minPrice} - $${maxPrice}`;
        
        const ratings = productsData.map(p => parseFloat(p.avg_rating) || 0).filter(r => r > 0);
        if (ratings.length > 0) {
            const avgRating = (ratings.reduce((a, b) => a + b, 0) / ratings.length).toFixed(1);
            document.getElementById('avgRating').textContent = `${avgRating} ★`;
        }
        
        // Flavor summary
        renderFlavorSummary();
    }
    
    // Update page title
    document.title = `${categoryData.name} - Royal Beverages`;
};

let flavorChartInstance = null;

// Render flavor summary
const renderFlavorSummary = () => {
    // Calculate average flavors across all products in category
    const flavorAttrs = ['sweetness', 'bitterness', 'strength', 'smokiness', 'fruitiness', 'spiciness'];
    const flavorSums = { sweetness: 0, bitterness: 0, strength: 0, smokiness: 0, fruitiness: 0, spiciness: 0 };
    const tagCounts = {};
    let validProducts = 0;
    
    productsData.forEach(p => {
        try {
            const flavor = typeof p.flavor_profile === 'string' ? JSON.parse(p.flavor_profile) : p.flavor_profile;
            if (flavor && flavor.sweetness != null) {
                validProducts++;
                flavorAttrs.forEach(attr => {
                    flavorSums[attr] += (flavor[attr] || 0);
                });
                (flavor.tags || []).forEach(tag => {
                    tagCounts[tag] = (tagCounts[tag] || 0) + 1;
                });
            }
        } catch (e) {}
    });
    
    if (validProducts === 0) return;
    
    // Show flavor summary
    const container = document.getElementById('flavorSummary');
    container.classList.remove('hidden');
    container.classList.add('block');
    
    // Render Chart
    const ctx = document.getElementById('flavorChart').getContext('2d');
    const labels = ['Sweet', 'Bitter', 'Strength', 'Smoke', 'Fruit', 'Spice'];
    const values = flavorAttrs.map(attr => flavorSums[attr] / validProducts);

    if (flavorChartInstance) flavorChartInstance.destroy();

    flavorChartInstance = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Category Average',
                data: values,
                fill: true,
                backgroundColor: 'rgba(0, 0, 0, 0.05)',
                borderColor: 'rgb(0, 0, 0)',
                pointBackgroundColor: 'rgb(0, 0, 0)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgb(0, 0, 0)',
                borderWidth: 1.5,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    angleLines: { display: true, color: 'rgba(0, 0, 0, 0.05)' },
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    suggestedMin: 0,
                    suggestedMax: 10,
                    ticks: { display: false },
                    pointLabels: {
                        font: { family: 'Inter', size: 9, weight: '900' },
                        color: '#000',
                        padding: 15
                    }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#000',
                    titleFont: { size: 10 },
                    bodyFont: { size: 10 },
                    callbacks: {
                        label: (ctx) => `${ctx.label}: ${ctx.raw.toFixed(1)}/10`
                    }
                }
            }
        }
    });
    
    // Render top tags
    const topTags = Object.entries(tagCounts)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 6);
    
    if (topTags.length > 0) {
        document.getElementById('flavorTags').innerHTML = 
            topTags.map(([tag]) => `<span class="bg-black text-white text-[9px] uppercase font-black tracking-widest px-3 py-1">${tag}</span>`).join('');
    }
};

const renderProducts = () => {
    const grid = document.getElementById('productsGrid');
    const emptyState = document.getElementById('emptyState');
    
    if (productsData.length === 0) {
        grid.classList.add('hidden');
        emptyState.classList.remove('hidden');
        emptyState.classList.add('flex');
        return;
    }
    
    grid.classList.remove('hidden');
    emptyState.classList.add('hidden');
    emptyState.classList.remove('flex');
    grid.innerHTML = productsData.map(renderProductCard).join('');
};



// Show error
const showError = (message) => {
    document.getElementById('categoryHero').innerHTML = `
        <div class="flex flex-col items-center justify-center py-32 w-full text-center">
            <h1 class="text-4xl font-heading uppercase tracking-widest text-red-500 mb-8">${message}</h1>
            <a href="<?= BASE_URL ?>shop.php" class="btn-premium px-12 h-14">Browse All Products</a>
        </div>
    `;
};

// Setup event listeners
const setupEventListeners = () => {
    // Sort
    document.getElementById('sortSelect').addEventListener('change', (e) => {
        sortProducts(e.target.value);
        renderProducts();
    });
    
    // Add to cart
    document.getElementById('productsGrid').addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-add-cart');
        if (btn && !btn.disabled) {
            const productId = btn.dataset.id;
            const product = productsData.find(p => p.id === parseInt(productId));
            
            await cart.add(productId, 1);
            
            // Visual feedback
            const originalText = btn.textContent;
            btn.textContent = '✓';
            toast.success(product ? `${product.name} added to cart!` : 'Added to cart!');
            
            setTimeout(() => {
                btn.textContent = originalText;
            }, 1500);
        }
    });
};

// Sort products
const sortProducts = (sortBy) => {
    productsData.sort((a, b) => {
        switch (sortBy) {
            case 'price_asc': return a.price_cents - b.price_cents;
            case 'price_desc': return b.price_cents - a.price_cents;
            case 'name_asc': return a.name.localeCompare(b.name);
            case 'rating': return (parseFloat(b.avg_rating) || 0) - (parseFloat(a.avg_rating) || 0);
            default: return 0;
        }
    });
};

// Initialize
document.addEventListener('DOMContentLoaded', init);
</script>

<?php require_once __DIR__ . "/components/footer.php"; ?>
