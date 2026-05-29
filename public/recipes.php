<?php
$pageName = 'recipes';
$pageTitle = 'Cocktail Recipes - Royal Beverages';
require_once __DIR__ . "/components/header.php";
?>

<main class="min-h-screen bg-white">
    <!-- Animated Hero Section -->
    <?php
    $heroTitle = 'Cocktail <br>Vintages';
    $heroSubtitle = 'The Art of the Cocktail';
    $heroDescription = 'Discover meticulously curated recipes crafted by our master distillers and guest mixologists.';
    $heroId = 'recipesHero';
    $heroOffset = '-17.5%';
    $heroBreadcrumbs = [
        ['url' => BASE_URL, 'label' => 'Home'],
        ['url' => '', 'label' => 'The Vintages']
    ];
    require_once __DIR__ . '/components/animated-hero.php';
    ?>

    <div class="px-8 md:px-16 pb-32">
        <div class="flex flex-col lg:flex-row gap-16">
            <!-- Sidebar: Filters -->
            <aside class="w-full lg:w-80 shrink-0 space-y-12">
                <div class="flex items-center justify-between border-b border-black pb-4">
                    <h2 class="text-xs uppercase tracking-[0.3em] font-black">Refine Selections</h2>
                    <button id="resetFiltersLink" class="text-[9px] uppercase tracking-widest font-black text-gray-400 hover:text-red-600 transition-colors">Reset All</button>
                </div>

                <!-- Difficulty Filter -->
                <div class="space-y-6">
                    <h3 class="text-[10px] uppercase tracking-widest font-black text-gray-400">Mastery Level</h3>
                    <select id="difficultyFilter" class="w-full h-12 bg-gray-50 border-none outline-none px-4 text-[10px] font-black uppercase tracking-widest cursor-pointer hover:bg-white transition-colors">
                        <option value="">All Levels</option>
                        <option value="easy">Elementary</option>
                        <option value="medium">Intermediate</option>
                        <option value="expert">Connoisseur</option>
                    </select>
                </div>

                <!-- Time Filter -->
                <div class="space-y-6">
                    <h3 class="text-[10px] uppercase tracking-widest font-black text-gray-400">Preparation Time</h3>
                    <select id="timeFilter" class="w-full h-12 bg-gray-50 border-none outline-none px-4 text-[10px] font-black uppercase tracking-widest cursor-pointer hover:bg-white transition-colors">
                        <option value="">Any Duration</option>
                        <option value="5">Under 5 Minutes</option>
                        <option value="10">Under 10 Minutes</option>
                        <option value="15">Under 15 Minutes</option>
                    </select>
                </div>

                <!-- Toggle: I Can Make -->
                <div class="space-y-6 pt-6 border-t border-gray-100">
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" id="canMakeFilter" class="w-4 h-4 accent-black">
                        <div class="flex flex-col">
                            <span class="text-[10px] uppercase tracking-widest font-bold group-hover:text-black transition-colors">Ready to Craft</span>
                            <span class="text-[8px] text-gray-300 uppercase font-bold tracking-widest italic">Matches your collection</span>
                        </div>
                    </label>
                </div>
            </aside>

            <!-- Main Listing Area -->
            <div class="flex-grow">
                <!-- Toolbar -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 mb-12 py-6 border-b border-gray-50">
                    <div id="resultsCount" class="text-[10px] uppercase tracking-[0.3em] font-black text-gray-400 italic">Exploring archive...</div>
                </div>

                <!-- Recipe Grid -->
                <div id="recipesGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-x-8 gap-y-16">
                    <!-- Recipes loaded via JS -->
                </div>

                <!-- Empty State -->
                <div id="emptyState" class="hidden py-32 text-center flex-col items-center">
                    <div class="text-4xl mb-6">∅</div>
                    <h2 class="text-xs uppercase tracking-[0.3em] font-black mb-4">No Recipes Found</h2>
                    <p class="text-gray-400 text-sm italic font-light mb-8">Broaden your search criteria to discover hidden gems.</p>
                    <button id="resetFiltersBtn" class="btn-premium px-12">Clear Filters</button>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Recipe Detail Modal -->
<div class="fixed inset-0 flex items-center justify-center opacity-0 invisible transition-all duration-500 py-4 md:p-12" id="recipeModal" style="z-index: 10000; background-color: rgba(0, 0, 0, 0.95); backdrop-filter: blur(12px);">
    <div class="bg-white w-full max-w-[1200px] h-auto max-h-[95vh] flex flex-col lg:flex-row relative shadow-[0_0_100px_rgba(0,0,0,0.9)] scale-95 transition-transform duration-500 overflow-hidden" id="recipeModalContent" style="z-index: 10001;">
        <button class="absolute top-6 right-6 p-4 bg-black text-white hover:bg-gold z-[100] transition-transform hover:rotate-90 flex items-center justify-center" id="recipeModalClose">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        
        <!-- Modal Image Section -->
        <div class="lg:w-1/2 h-[300px] lg:h-auto bg-gray-100 overflow-hidden relative" id="recipeModalImage">
            <!-- Image injected via JS -->
        </div>
        
        <!-- Modal Content Section -->
        <div class="lg:w-1/2 p-8 md:p-16 overflow-y-auto custom-scrollbar">
            <div id="recipeModalBadges" class="flex gap-4 mb-8">
                <!-- Badges injected via JS -->
            </div>
            
            <h2 id="recipeModalName" class="text-4xl md:text-5xl font-heading font-black uppercase tracking-widest leading-none mb-8"></h2>
            <p id="recipeModalDescription" class="text-gray-400 text-sm md:text-base italic leading-relaxed mb-12"></p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-16">
                <div>
                    <h3 class="text-[10px] uppercase tracking-[0.3em] font-black text-black border-b border-black pb-2 mb-6">The Elements</h3>
                    <ul id="recipeModalIngredients" class="space-y-4">
                        <!-- Ingredients injected via JS -->
                    </ul>
                </div>
                <div>
                    <h3 class="text-[10px] uppercase tracking-[0.3em] font-black text-black border-b border-black pb-2 mb-6">The Ritual</h3>
                    <div id="recipeModalInstructions" class="text-xs uppercase font-black tracking-widest leading-loose text-gray-500">
                        <!-- Instructions injected via JS -->
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 pt-12 border-t border-gray-100">
                <button class="btn-premium flex-grow h-16 flex items-center justify-center gap-4" id="addAllToCart">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Acquire Ingredients
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f9fafb; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #000; }
    #recipeModal.active { opacity: 1; visibility: visible; }
    #recipeModal.active #recipeModalContent { transform: scale(1); }
</style>

<script type="module">
import { API } from '<?= ASSET_URL ?>js/api-helper.js';
import { cart } from '<?= BASE_URL ?>assets/js/cart-service.js';

let allRecipes = [];
let filteredRecipes = [];
let currentRecipe = null;

const recipesGrid = document.getElementById('recipesGrid');
const resultsCount = document.getElementById('resultsCount');
const emptyState = document.getElementById('emptyState');
const difficultyFilter = document.getElementById('difficultyFilter');
const timeFilter = document.getElementById('timeFilter');
const canMakeFilter = document.getElementById('canMakeFilter');
const recipeModal = document.getElementById('recipeModal');

const init = async () => {
    await loadRecipes();
    applyFilters();
    setupEventListeners();
};

const loadRecipes = async () => {
    try {
        const response = await API.recipes.list({ limit: 50 });
        if (response.success && response.data) {
            allRecipes = response.data.items || [];
        }
    } catch (error) {
        console.error('[Recipes] Failed to load:', error);
    }
};

const applyFilters = () => {
    filteredRecipes = allRecipes.filter(recipe => {
        if (difficultyFilter.value && recipe.difficulty !== difficultyFilter.value) return false;
        if (timeFilter.value && recipe.preparation_time > parseInt(timeFilter.value)) return false;
        return true;
    });
    renderRecipes();
};

const renderRecipes = () => {
    if (filteredRecipes.length === 0) {
        recipesGrid.classList.add('hidden');
        emptyState.classList.remove('hidden');
        resultsCount.textContent = '0 COLLECTION VINTAGES';
        return;
    }
    
    recipesGrid.classList.remove('hidden');
    emptyState.classList.add('hidden');
    resultsCount.textContent = `${filteredRecipes.length} COLLECTION VINTAGES`;
    
    recipesGrid.innerHTML = filteredRecipes.map(recipe => {
        const ingredients = recipe.ingredients || [];
        const image = recipe.image_url || '<?= ASSET_URL ?>images/placeholder-spirit.png';
        
        return `
            <article class="group relative cursor-pointer recipe-card" data-id="${recipe.id}">
                <div class="aspect-[4/5] overflow-hidden bg-gray-50 relative">
                    <img src="${image}" alt="${recipe.name}" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110 grayscale group-hover:grayscale-0" loading="lazy" onerror="this.src='<?= ASSET_URL ?>images/placeholder-spirit.png'">
                    
                    <div class="absolute top-4 left-4 flex flex-col gap-2 z-10">
                        <span class="px-3 py-1 bg-white text-black text-[8px] font-black uppercase tracking-widest">${recipe.difficulty}</span>
                        <span class="px-3 py-1 bg-black text-white text-[8px] font-black uppercase tracking-widest">${recipe.preparation_time || 0} MIN</span>
                    </div>

                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity duration-500">
                        <div class="text-white text-[10px] font-black uppercase tracking-[0.3em] border border-white/30 px-6 py-3 backdrop-blur-sm">View Ritual</div>
                    </div>
                </div>
                
                <div class="mt-8 text-center px-4">
                    <span class="text-[9px] uppercase tracking-[0.3em] text-black font-extrabold mb-2 block italic">Spirit Ritual</span>
                    <h3 class="text-lg font-extrabold uppercase tracking-widest mb-3 line-clamp-1">${recipe.name}</h3>
                    <div class="flex items-center justify-center gap-4 text-[9px] uppercase font-black tracking-widest text-gray-400">
                        <span>Serves ${recipe.serves}</span>
                        <div class="w-1 h-1 bg-gray-200 rounded-full"></div>
                        <span>${ingredients.length} Elements</span>
                    </div>
                </div>
            </article>
        `;
    }).join('');
};

const openModal = (recipeId) => {
    currentRecipe = allRecipes.find(r => r.id === recipeId);
    if (!currentRecipe) return;
    
    const image = currentRecipe.image_url || '<?= ASSET_URL ?>images/placeholder-spirit.png';
    document.getElementById('recipeModalImage').innerHTML = `<img src="${image}" alt="${currentRecipe.name}" class="w-full h-full object-cover transition-opacity duration-1000" onerror="this.src='<?= ASSET_URL ?>images/placeholder-spirit.png'">`;
    document.getElementById('recipeModalName').textContent = currentRecipe.name;
    document.getElementById('recipeModalDescription').textContent = currentRecipe.description;
    
    // Improved instruction parsing: remove escaped newlines and split by numbered steps or periods
    const instructions = (currentRecipe.instructions || '').replace(/\\n/g, ' ').replace(/\n/g, ' ');
    const steps = instructions.split(/(?=\d\.)/).filter(i => i.trim());
    
    document.getElementById('recipeModalInstructions').innerHTML = steps.length > 1 
        ? steps.map(s => `<p class="mb-6">${s.trim()}</p>`).join('')
        : instructions.split('.').filter(i => i.trim()).map(i => `<p class="mb-6">${i.trim()}.</p>`).join('');
    
    document.getElementById('recipeModalBadges').innerHTML = `
        <span class="px-4 py-2 bg-black text-white text-[9px] font-black uppercase tracking-widest">${currentRecipe.difficulty}</span>
        <span class="px-4 py-2 border border-black text-black text-[9px] font-black uppercase tracking-widest">${currentRecipe.preparation_time || 0} MIN</span>
    `;
    
    const ingredientsList = document.getElementById('recipeModalIngredients');
    const ingredients = currentRecipe.ingredients || [];
    ingredientsList.innerHTML = ingredients.map(ing => `
        <li class="flex items-center justify-between py-2 border-b border-gray-50">
            <span class="flex items-center gap-3 text-[10px] uppercase font-black tracking-widest">
                <div class="w-1 h-1 bg-black rounded-full"></div>
                ${ing.product_name}
            </span>
            <span class="text-[10px] font-bold text-gray-400 italic">${ing.quantity} ${ing.unit}</span>
        </li>
    `).join('');
    
    recipeModal.classList.add('active');
    document.body.style.overflow = 'hidden';
};

const closeModal = () => {
    recipeModal.classList.remove('active');
    document.body.style.overflow = '';
    currentRecipe = null;
};

const addAllIngredients = async () => {
    if (!currentRecipe) return;
    const productsToAdd = (currentRecipe.ingredients || [])
        .filter(ing => ing.product_id !== null)
        .map(ing => ing.product_id);
    
    if (productsToAdd.length === 0) return;
    
    for (const id of productsToAdd) {
        await cart.add(id, 1, false);
    }
    
    const btn = document.getElementById('addAllToCart');
    const originalContent = btn.innerHTML;
    btn.innerHTML = 'COCKTAIL READY ✓';
    btn.classList.add('bg-green-600');
    
    setTimeout(() => {
        btn.innerHTML = originalContent;
        btn.classList.remove('bg-green-600');
    }, 2000);
};

const setupEventListeners = () => {
    difficultyFilter.addEventListener('change', applyFilters);
    timeFilter.addEventListener('change', applyFilters);
    canMakeFilter.addEventListener('change', applyFilters);
    
    const resetFilters = () => {
        difficultyFilter.value = '';
        timeFilter.value = '';
        canMakeFilter.checked = false;
        applyFilters();
    };

    document.getElementById('resetFiltersBtn').addEventListener('click', resetFilters);
    document.getElementById('resetFiltersLink').addEventListener('click', resetFilters);
    
    recipesGrid.addEventListener('click', (e) => {
        const card = e.target.closest('.recipe-card');
        if (card) openModal(parseInt(card.dataset.id));
    });
    
    document.getElementById('recipeModalClose').addEventListener('click', closeModal);
    // Overlay click is handled by the wrapper being the target in some implementations, 
    // but here we have a dedicated wrapper. Let's make it click-closable.
    recipeModal.addEventListener('click', (e) => {
        if (e.target === recipeModal) closeModal();
    });
    
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });
    document.getElementById('addAllToCart').addEventListener('click', addAllIngredients);
};

document.addEventListener('DOMContentLoaded', init);
</script>

<?php require_once __DIR__ . "/components/footer.php"; ?>
