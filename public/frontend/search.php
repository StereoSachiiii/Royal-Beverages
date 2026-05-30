<?php
$pageName = 'shop';
$pageTitle = 'Search Results - Royal Beverages';
require_once __DIR__ . "/components/header.php";

$query = isset($_GET['q']) ? htmlspecialchars(trim($_GET['q'])) : '';
?>

<main class="search-results-page">
    <div class="container">
        <div class="search-header">
            <h1 class="page-title">
                <?php if ($query): ?>
                    Search results for "<span class="search-query"><?= $query ?></span>"
                <?php else: ?>
                    Search Products
                <?php endif; ?>
            </h1>
            
            <!-- Search Bar -->
            <div class="search-page-bar">
                <form action="search.php" method="GET" class="search-form">
                    <input type="search" name="q" value="<?= $query ?>" placeholder="Search products, categories..." class="search-input" id="pageSearchInput" autocomplete="off">
                    <button type="submit" class="search-submit btn btn-gold">Search</button>
                </form>
            </div>
        </div>

        <!-- Results Grid -->
        <div class="search-results-container">
            <div class="results-info">
                <span id="resultsCount">Loading...</span>
                <div class="results-sort">
                    <label for="sortBy">Sort by:</label>
                    <select id="sortBy">
                        <option value="relevance">Relevance</option>
                        <option value="price-low">Price: Low to High</option>
                        <option value="price-high">Price: High to Low</option>
                        <option value="name">Name A-Z</option>
                    </select>
                </div>
            </div>
            
            <div class="search-results-grid" id="searchResultsGrid">
                <!-- Results loaded via JavaScript -->
            </div>
            
            <div class="search-empty-state" id="emptyState" style="display: none;">
                <div class="empty-icon">🔍</div>
                <h2>No products found</h2>
                <p>Try adjusting your search or browse our categories</p>
                <a href="<?= BASE_URL ?>shop.php" class="btn btn-gold">Browse All Products</a>
            </div>
        </div>
    </div>
</main>

<style>
.search-results-page {
    padding: var(--space-2xl) 0 var(--space-3xl);
    min-height: calc(100vh - 200px);
}

.search-header {
    text-align: center;
    margin-bottom: var(--space-2xl);
}

.page-title {
    font-family: var(--font-serif);
    font-size: 2.25rem;
    font-weight: 300;
    font-style: italic;
    color: var(--black);
    margin-bottom: var(--space-xl);
}

.search-query {
    color: var(--gold);
}

.search-page-bar {
    max-width: 600px;
    margin: 0 auto;
}

.search-form {
    display: flex;
    gap: var(--space-md);
}

.search-input {
    flex: 1;
    padding: var(--space-md) var(--space-lg);
    border: 2px solid var(--gray-200);
    border-radius: var(--radius-lg);
    font-size: 1rem;
    transition: all var(--duration-fast);
}

.search-input:focus {
    outline: none;
    border-color: var(--gold);
    box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.15);
}

.search-submit {
    padding: var(--space-md) var(--space-xl);
}

.results-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-xl);
    padding-bottom: var(--space-md);
    border-bottom: 1px solid var(--gray-200);
}

#resultsCount {
    font-size: 0.95rem;
    color: var(--gray-600);
}

.results-sort {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.results-sort label {
    font-size: 0.9rem;
    color: var(--gray-500);
}

.results-sort select {
    padding: var(--space-sm) var(--space-md);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-md);
    font-size: 0.9rem;
    background: var(--white);
}

.search-results-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--space-xl);
}

.result-card {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: all var(--duration-normal);
    text-decoration: none;
    color: inherit;
}

.result-card:hover {
    border-color: rgba(212, 175, 55, 0.3);
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
}

.result-card-image {
    aspect-ratio: 1/1;
    overflow: hidden;
}

.result-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--duration-slow);
}

.result-card:hover .result-card-image img {
    transform: scale(1.08);
}

.result-card-info {
    padding: var(--space-lg);
}

.result-card-category {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gold);
    margin-bottom: var(--space-xs);
}

.result-card-name {
    font-family: var(--font-serif);
    font-size: 1.1rem;
    font-weight: 500;
    color: var(--black);
    margin-bottom: var(--space-sm);
    line-height: 1.3;
}

.result-card-price {
    font-family: var(--font-serif);
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--gold);
}

.search-empty-state {
    text-align: center;
    padding: var(--space-3xl);
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: var(--space-lg);
}

.search-empty-state h2 {
    font-family: var(--font-serif);
    font-size: 1.75rem;
    font-weight: 400;
    font-style: italic;
    margin-bottom: var(--space-sm);
}

.search-empty-state p {
    color: var(--gray-500);
    margin-bottom: var(--space-xl);
}

@media (max-width: 1024px) {
    .search-results-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .search-results-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .search-form {
        flex-direction: column;
    }
    
    .results-info {
        flex-direction: column;
        gap: var(--space-md);
        align-items: flex-start;
    }
}

@media (max-width: 480px) {
    .search-results-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script type="module">
import { API } from '<?= BASE_URL ?>assets/js/api-helper.js?v=<?= time() ?>';
import { showErrorBoundary, initCustomDropdowns } from '<?= BASE_URL ?>assets/js/ui-components.js?v=<?= time() ?>';

const query = (<?= json_encode($query) ?> || "").toLowerCase();
const resultsGrid = document.getElementById('searchResultsGrid');
const resultsCount = document.getElementById('resultsCount');
const emptyState = document.getElementById('emptyState');
const sortBy = document.getElementById('sortBy');

let allResults = [];

// Load and search products
const loadResults = async () => {
    try {
        const response = await API.request('/products/enriched/all' + API.buildQuery({ limit: 100 }));
        if (response.success && response.data) {
            showEmpty();
            return;
        }
        
        // Filter by search query
        allResults = response.data.filter(p => 
            p.name.toLowerCase().includes(query) ||
            p.description?.toLowerCase().includes(query) ||
            p.category_name?.toLowerCase().includes(query)
        );
        
        if (allResults.length === 0) {
            showEmpty();
            return;
        }
        
        renderResults();
    } catch (error) {
        console.error('[Search] Failed to load results:', error);
        showErrorBoundary("We couldn't perform your search right now. Please return home and try again.");
    }
};

// Render results
const renderResults = () => {
    emptyState.style.display = 'none';
    resultsGrid.style.display = 'grid';
    
    resultsCount.textContent = `${allResults.length} product${allResults.length !== 1 ? 's' : ''} found`;
    
    resultsGrid.innerHTML = allResults.map(p => `
        <a href="product.php?id=${p.id}" class="result-card">
            <div class="result-card-image">
                <img src="${p.image_url}" alt="${p.name}" loading="lazy">
            </div>
            <div class="result-card-info">
                <div class="result-card-category">${p.category_name || 'Spirits'}</div>
                <div class="result-card-name">${highlightQuery(p.name)}</div>
                <div class="result-card-price">Rs. ${(p.price_cents / 100).toFixed(2)}</div>
            </div>
        </a>
    `).join('');
};

// Highlight matching text
const highlightQuery = (text) => {
    if (!query) return text;
    const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
    return text.replace(regex, '<mark>$1</mark>');
};

const escapeRegex = (string) => string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

// Show empty state
const showEmpty = () => {
    resultsGrid.style.display = 'none';
    emptyState.style.display = 'block';
    resultsCount.textContent = '0 products found';
};

// Sort functionality
sortBy.addEventListener('change', () => {
    const sortValue = sortBy.value;
    
    switch (sortValue) {
        case 'price-low':
            allResults.sort((a, b) => a.price_cents - b.price_cents);
            break;
        case 'price-high':
            allResults.sort((a, b) => b.price_cents - a.price_cents);
            break;
        case 'name':
            allResults.sort((a, b) => a.name.localeCompare(b.name));
            break;
        default:
            // Relevance - items with query in name first
            allResults.sort((a, b) => {
                const aInName = a.name.toLowerCase().includes(query);
                const bInName = b.name.toLowerCase().includes(query);
                return bInName - aInName;
            });
    }
    
    renderResults();
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initCustomDropdowns();
    if (query) {
        loadResults();
    } else {
        showEmpty();
        resultsCount.textContent = 'Enter a search term';
    }
});
</script>

<?php require_once __DIR__ . "/components/footer.php"; ?>
