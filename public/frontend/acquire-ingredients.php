<?php
$pageName = 'acquire_ingredients';
$pageTitle = 'Acquire Ingredients - Royal Beverages';
require_once __DIR__ . "/components/header.php";

$recipeId = $_GET['recipe_id'] ?? 1;
?>

<main class="min-h-screen bg-white pt-32 pb-32 px-8 md:px-16">
    <div class="max-w-4xl mx-auto" id="acquireContainer">
        <div class="flex items-center gap-4 mb-12">
            <a href="recipes.php" class="p-3 bg-gray-50 hover:bg-black hover:text-white rounded-full transition-colors flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-3xl font-heading font-black uppercase tracking-widest">Acquire Elements</h1>
        </div>

        <div id="loadingState" class="py-20 flex flex-col items-center justify-center">
            <div class="w-8 h-8 border-2 border-black border-t-transparent rounded-full animate-spin mb-4"></div>
            <div class="text-[10px] uppercase font-black tracking-[0.3em] text-gray-400">Consulting the Cellar...</div>
        </div>

        <div id="contentState" class="hidden">
            <div class="bg-[#0a0a0a] text-white p-8 md:p-12 rounded-2xl border border-gray-800 shadow-2xl mb-12 flex flex-col md:flex-row gap-8 items-center md:items-start">
                <img id="recipeImage" src="" alt="Recipe" class="w-32 h-32 md:w-48 md:h-48 object-cover rounded-xl border border-gray-800 shadow-lg" onerror="this.src='<?= ASSET_URL ?>images/placeholder-spirit.webp'">
                <div class="flex-1 text-center md:text-left">
                    <span class="text-[9px] uppercase tracking-[0.4em] font-black text-gold mb-2 block">Cocktail Ritual</span>
                    <h2 id="recipeName" class="text-3xl font-heading font-black uppercase tracking-widest mb-4">Recipe Name</h2>
                    <p id="recipeDesc" class="text-sm italic text-gray-400 font-light leading-relaxed"></p>
                </div>
            </div>

            <h3 class="text-[10px] uppercase tracking-[0.3em] font-black border-b border-black pb-4 mb-8">Required Spirits & Elements</h3>
            
            <div id="ingredientsList" class="space-y-4 mb-12">
                <!-- Ingredients injected via JS -->
            </div>

            <div class="flex flex-col md:flex-row items-center justify-between p-8 bg-gray-50 rounded-xl border border-gray-200">
                <div class="text-center md:text-left mb-6 md:mb-0">
                    <span class="text-[10px] uppercase tracking-[0.3em] font-black text-gray-400 block mb-2">Total Investment</span>
                    <div class="text-3xl font-black uppercase tracking-widest" id="totalPrice">$0.00</div>
                </div>
                <button id="addBundleBtn" class="btn-premium px-12 h-16 flex items-center justify-center gap-3 w-full md:w-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    Acquire Selected Collection
                </button>
            </div>
        </div>
    </div>
</main>

<script type="module">
import { API } from '<?= ASSET_URL ?>js/api-helper.js';
import { cart } from '<?= ASSET_URL ?>js/cart-service.js';
import { toast } from '<?= ASSET_URL ?>js/toast.js';
import { showErrorBoundary, fixImagePath } from '<?= ASSET_URL ?>js/ui-components.js';

const recipeId = parseInt(<?= json_encode($recipeId) ?>);
let currentRecipe = null;
let ingredientProducts = [];

const loadingState = document.getElementById('loadingState');
const contentState = document.getElementById('contentState');
const ingredientsList = document.getElementById('ingredientsList');
const totalPriceEl = document.getElementById('totalPrice');
const addBundleBtn = document.getElementById('addBundleBtn');

const init = async () => {
    try {
        // Fetch recipes to find ours
        const recipesRes = await API.recipes.list({ limit: 100 });
        if (!recipesRes.success || !recipesRes.data) throw new Error("Could not load recipes");
        
        const allRecipes = recipesRes.data.items || recipesRes.data;
        currentRecipe = allRecipes.find(r => r.id === recipeId);
        
        if (!currentRecipe) {
            showErrorBoundary("Recipe not found in the archives. Please explore other vintages.");
            return;
        }

        document.getElementById('recipeName').textContent = currentRecipe.name;
        document.getElementById('recipeDesc').textContent = currentRecipe.description || '';
        document.getElementById('recipeImage').src = currentRecipe.image_url || '<?= ASSET_URL ?>images/placeholder-spirit.webp';

        // Fetch ingredients properly via the API
        const ingredientsRes = await API.recipes.getIngredients(recipeId);
        const ingredients = ingredientsRes.success ? (ingredientsRes.data.items || ingredientsRes.data || []) : [];
        currentRecipe.ingredients = ingredients;
        
        // Fetch product details for ingredients that map to products
        const productPromises = ingredients.map(async (ing) => {
            if (!ing.product_id) return { ...ing, mappedProduct: null };
            try {
                const pRes = await API.request('/products/' + ing.product_id + '/enriched');
                return { ...ing, mappedProduct: pRes.success ? pRes.data : null };
            } catch (e) {
                return { ...ing, mappedProduct: null };
            }
        });
        
        ingredientProducts = await Promise.all(productPromises);
        
        renderIngredients();
        updateTotal();
        
        loadingState.classList.add('hidden');
        contentState.classList.remove('hidden');
        
    } catch (error) {
        console.error('Acquire Error:', error);
        showErrorBoundary("We encountered an anomaly while consulting the cellar. Please return later.");
    }
};

const renderIngredients = () => {
    if (ingredientProducts.length === 0) {
        ingredientsList.innerHTML = '<div class="text-center text-gray-400 py-8 text-sm italic">No specific elements required for this ritual.</div>';
        return;
    }

    ingredientsList.innerHTML = ingredientProducts.map((item, index) => {
        const hasProduct = item.mappedProduct !== null;
        const product = item.mappedProduct;
        
        if (!hasProduct) {
            return `
                <div class="flex items-center justify-between p-6 bg-white border border-gray-100 rounded-lg opacity-60">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-50 flex items-center justify-center rounded-md border border-gray-200">
                            <span class="text-lg text-gray-400">?</span>
                        </div>
                        <div>
                            <span class="text-xs font-black uppercase tracking-widest block mb-1">${item.product_name || 'Ingredient'}</span>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest italic">${item.quantity} ${item.unit}</span>
                        </div>
                    </div>
                    <div class="text-[9px] uppercase tracking-[0.2em] font-black text-gray-400 bg-gray-50 px-4 py-2 rounded-md">Common Pantry Item</div>
                </div>
            `;
        }

        const price = (product.price_cents / 100).toFixed(2);
        const inStock = product.available_stock > 0;
        
        return `
            <div class="flex flex-col sm:flex-row items-center gap-6 p-6 bg-white border border-gray-200 rounded-lg shadow-sm transition-colors hover:border-black ingredient-row" data-index="${index}">
                <div class="flex items-center justify-center w-24 h-24 shrink-0 bg-gray-50 rounded-md p-2">
                    <img src="${fixImagePath(product.image_url)}" alt="${product.name}" class="max-w-full max-h-full object-contain" onerror="this.src='<?= ASSET_URL ?>images/placeholder-product.webp'">
                </div>
                
                <div class="flex-1 text-center sm:text-left">
                    <span class="text-[9px] uppercase tracking-[0.3em] text-gray-400 font-black mb-1 block">Requires: ${item.quantity} ${item.unit}</span>
                    <h4 class="text-sm font-black uppercase tracking-widest mb-2">${product.name}</h4>
                    <div class="flex items-center justify-center sm:justify-start gap-4 text-[10px] uppercase font-bold tracking-widest">
                        <span class="text-black">$${price}</span>
                        ${inStock 
                            ? `<span class="text-green-600 bg-green-50 px-2 py-1 rounded-sm">In Stock (${product.available_stock})</span>`
                            : `<span class="text-red-600 bg-red-50 px-2 py-1 rounded-sm">Depleted</span>`
                        }
                    </div>
                </div>
                
                ${inStock ? `
                <div class="flex items-center gap-3 bg-gray-50 p-2 rounded-lg border border-gray-200 shrink-0">
                    <button class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-black hover:bg-white rounded transition-colors qty-btn" data-action="minus">-</button>
                    <input type="number" class="w-12 text-center bg-transparent text-sm font-black outline-none item-qty" value="1" min="0" max="${product.available_stock}" readonly>
                    <button class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-black hover:bg-white rounded transition-colors qty-btn" data-action="plus">+</button>
                </div>
                ` : `
                <div class="shrink-0 flex items-center">
                    <span class="text-[10px] font-black uppercase tracking-widest text-red-500 px-4 py-2 border border-red-100 rounded-md bg-red-50">Unavailable</span>
                </div>
                `}
            </div>
        `;
    }).join('');

    // Setup quantity listeners
    document.querySelectorAll('.ingredient-row').forEach(row => {
        const index = row.dataset.index;
        const item = ingredientProducts[index];
        if (!item.mappedProduct || item.mappedProduct.available_stock <= 0) return;

        const input = row.querySelector('.item-qty');
        const minusBtn = row.querySelector('[data-action="minus"]');
        const plusBtn = row.querySelector('[data-action="plus"]');

        const updateQty = (newQty) => {
            if (newQty >= 0 && newQty <= item.mappedProduct.available_stock) {
                input.value = newQty;
                item.selectedQty = newQty;
                updateTotal();
            }
        };

        // Initialize default selected quantity
        item.selectedQty = 1;

        minusBtn.addEventListener('click', () => updateQty(parseInt(input.value) - 1));
        plusBtn.addEventListener('click', () => updateQty(parseInt(input.value) + 1));
    });
};

const updateTotal = () => {
    let totalCents = 0;
    ingredientProducts.forEach(item => {
        if (item.mappedProduct && item.selectedQty > 0) {
            totalCents += item.mappedProduct.price_cents * item.selectedQty;
        }
    });
    totalPriceEl.textContent = '$' + (totalCents / 100).toFixed(2);
    
    // Disable bundle button if nothing is selected
    if (totalCents === 0) {
        addBundleBtn.disabled = true;
        addBundleBtn.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        addBundleBtn.disabled = false;
        addBundleBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
};

const handleBundlePurchase = async () => {
    const itemsToAdd = ingredientProducts.filter(item => item.mappedProduct && item.selectedQty > 0);
    
    if (itemsToAdd.length === 0) return;
    
    const originalText = addBundleBtn.innerHTML;
    addBundleBtn.innerHTML = '<div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div> Securing...';
    addBundleBtn.disabled = true;
    
    try {
        for (const item of itemsToAdd) {
            await cart.add(item.mappedProduct.id, item.selectedQty, false);
        }
        
        toast.success(`Successfully secured ${itemsToAdd.length} elements for your cellar.`);
        
        addBundleBtn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Collection Secured';
        addBundleBtn.classList.replace('btn-premium', 'bg-green-600');
        addBundleBtn.classList.add('text-white');
        
        setTimeout(() => {
            addBundleBtn.innerHTML = originalText;
            addBundleBtn.classList.replace('bg-green-600', 'btn-premium');
            addBundleBtn.classList.remove('text-white');
            addBundleBtn.disabled = false;
        }, 3000);
        
    } catch (error) {
        console.error('Failed to add bundle:', error);
        toast.error('Encountered an issue securing the collection.');
        addBundleBtn.innerHTML = originalText;
        addBundleBtn.disabled = false;
    }
};

addBundleBtn.addEventListener('click', handleBundlePurchase);
document.addEventListener('DOMContentLoaded', init);
</script>

<?php require_once __DIR__ . "/components/footer.php"; ?>
