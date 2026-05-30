<?php
/**
 * For You AI Recommendation Component
 * Renders dynamically based on Gemini AI responses
 */
?>
<section class="py-24 bg-gray-50/50 border-b border-gray-100" id="aiRecommendationsWrapper">
    <div class="max-w-[1440px] mx-auto px-8">
        <div class="flex items-end justify-between mb-16">
            <div class="flex flex-col">
                <span class="text-[10px] uppercase font-black tracking-[0.4em] text-gold mb-2 flex items-center gap-2">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Curated By AI
                </span>
                <h2 class="text-4xl lg:text-5xl font-heading tracking-widest uppercase mb-4">For You</h2>
                <p class="text-xs uppercase tracking-[0.2em] text-gray-400 font-bold max-w-xl">Handpicked selections tailored to your personal taste</p>
            </div>
            <a href="<?= PAGE_URLS['shop'] ?? '/shop.php' ?>" class="hidden md:inline-flex border border-black px-8 py-3 text-[10px] shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] uppercase font-black tracking-widest hover:bg-black hover:text-white hover:shadow-none transition-all items-center gap-2">
                All Curations <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>

        <!-- Carousel / Grid Container -->
        <div class="relative">
            <div class="overflow-x-auto pb-8 hide-scrollbar cursor-grab active:cursor-grabbing" id="aiProductsContainer">
                
                <!-- Skeleton Loader -->
                <div class="flex gap-4 lg:grid lg:grid-cols-4 md:gap-8 min-w-max lg:min-w-0" id="aiSkeletonLoader">
                    <?php for($i=0; $i<4; $i++): ?>
                    <div class="w-[280px] lg:w-full bg-white border border-gray-100 p-6 animate-pulse flex flex-col items-center">
                        <div class="w-full h-48 bg-gray-100 mb-6 mx-auto"></div>
                        <div class="w-3/4 h-3 bg-gray-200 mb-3"></div>
                        <div class="w-1/2 h-2 bg-gray-100 mb-6"></div>
                        <div class="w-1/4 h-3 bg-gray-200 mt-auto"></div>
                    </div>
                    <?php endfor; ?>
                </div>

                <!-- Product Implementation Vector -->
                <div class="flex gap-4 lg:grid lg:grid-cols-4 md:gap-8 min-w-max lg:min-w-0 hidden opacity-0 transition-opacity duration-1000" id="aiRecommendationsList">
                    <!-- Javascript Will Hydrate Product Cards Here -->
                </div>

            </div>
        </div>
        
        <!-- CTA for Taste Profile -->
        <div class="mt-16 w-full flex justify-center">
            <a href="<?= BASE_URL ?>myaccount/taste-profile.php" class="border border-black px-10 py-4 text-[10px] shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] uppercase font-black tracking-widest hover:bg-black hover:text-white hover:shadow-none transition-all flex items-center gap-3 bg-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                Tune Your Taste Profile
            </a>
        </div>
    </div>
</section>

<style>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<script type="module">
import API from '<?= BASE_URL ?>assets/js/api-helper.js';

document.addEventListener('DOMContentLoaded', async () => {
    const listContainer = document.getElementById('aiRecommendationsList');
    const skeleton = document.getElementById('aiSkeletonLoader');

    try {
        const response = await API.request('/recommendations/for-you', { method: 'GET' });
        
        if (response && response.success && response.data && response.data.length > 0) {
            
            // Build the catalog HTML referencing the standard product-card structure
            const html = response.data.map(product => {
                const isAvailable = product.stock && product.stock.available_stock > 0;
                const statusBadge = isAvailable 
                    ? `<span class="bg-black text-white text-[8px] font-black uppercase tracking-widest px-3 py-1">In Stock</span>`
                    : `<span class="bg-gray-100 text-gray-500 text-[8px] font-black uppercase tracking-widest px-3 py-1">Depleted</span>`;

                const price = `Rs. ${(product.price_cents / 100).toFixed(2)}`;
                const imageFilename = product.image_url ? product.image_url.split('/').pop() : 'placeholder-product.webp';
                const image = `<?= BASE_URL ?>assets/images/${imageFilename}`;

                return `
                <a href="<?= BASE_URL ?>product.php?slug=${product.slug}" class="group w-[280px] lg:w-full bg-white border border-gray-100 p-8 flex flex-col relative overflow-hidden transition-all duration-500 hover:border-black">
                    <!-- Badges -->
                    <div class="absolute top-6 left-6 z-10 flex flex-col gap-2">
                        ${statusBadge}
                        <span class="bg-gold text-white text-[8px] font-black uppercase tracking-widest px-3 py-1 shadow-sm">AI Match</span>
                    </div>

                    <!-- Image -->
                    <div class="h-56 mb-8 mt-4 relative flex items-center justify-center">
                        <img src="${image}" alt="${product.name}" class="max-h-full max-w-full object-contain transition-transform duration-700 group-hover:scale-110 drop-shadow-2xl" loading="lazy">
                    </div>

                    <!-- Meta -->
                    <div class="text-center flex flex-col flex-grow items-center justify-end">
                        <span class="text-[9px] uppercase font-black tracking-[0.3em] text-gray-400 mb-2 truncate max-w-full block">
                            ${product.category_name || 'Spirit'}
                        </span>
                        <h3 class="text-sm font-heading uppercase tracking-widest mb-4 group-hover:text-gold transition-colors line-clamp-2 px-2">
                            ${product.name}
                        </h3>
                        <span class="text-xs font-black tracking-widest mt-auto uppercase">${price}</span>
                    </div>

                    <!-- Hover Add to Cart Layer -->
                    ${isAvailable ? `
                    <div class="absolute inset-x-0 bottom-0 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                        <button class="w-full bg-black text-white py-4 text-[10px] font-black uppercase tracking-widest hover:bg-gold transition-colors flex items-center justify-center gap-2">
                            Add to Cart <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>
                    ` : ''}
                </a>
                `;
            }).join('');

            listContainer.innerHTML = html;
        } else {
            // Hide section if totally empty (rare)
            document.getElementById('aiRecommendationsWrapper').style.display = 'none';
        }
    } catch(err) {
        document.getElementById('aiRecommendationsWrapper').style.display = 'none';
    } finally {
        skeleton.classList.add('hidden');
        listContainer.classList.remove('hidden');
        // Trigger reflow for fade in
        void listContainer.offsetWidth;
        listContainer.classList.remove('opacity-0');
    }
});
</script>
