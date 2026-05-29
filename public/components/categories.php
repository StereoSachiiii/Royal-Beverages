<?php
require_once __DIR__ . "/../config/urls.php";

$sectionTitle = $categoriesSectionTitle ?? 'Browse Collections';
$sectionSubtitle = $categoriesSectionSubtitle ?? 'Discovery';
$excludeCategoryId = $categoriesExcludeId ?? 0;
?>

<section class="section max-w-[1440px] mx-auto px-8">
    <div class="flex flex-col items-center mb-16">
        <span class="text-xs uppercase tracking-[0.4em] text-black font-extrabold mb-4 text-center"><?= htmlspecialchars($sectionSubtitle) ?></span>
        <h2 class="text-3xl font-heading text-center uppercase tracking-widest font-extrabold"><?= htmlspecialchars($sectionTitle) ?></h2>
    </div>
    
    <div class="flex flex-row flex-wrap justify-center items-center gap-12 categories-container">
        <!-- Categories injected here -->
    </div>
</section>

<!-- Category Detail Modal -->
<div class="fixed inset-0 flex items-center justify-center z-[9999] opacity-0 invisible transition-all duration-500 bg-black/90 backdrop-blur-sm p-4 md:p-12" id="detailModalCategory">
    <div class="bg-white w-full max-w-[1000px] h-auto max-h-[90vh] flex flex-col md:flex-row relative shadow-[0_0_50px_rgba(0,0,0,0.5)] scale-95 transition-transform duration-500 overflow-hidden" id="detailModalCategoryContent">
        <button class="absolute top-4 right-4 p-2 bg-black text-white hover:bg-gray-800 z-[100] transition-colors" id="closeModalCategory">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div id="modalBodyCategory" class="flex flex-col md:flex-row w-full">
            <!-- Content injected here -->
        </div>
    </div>
</div>

<script type="module">
    import { API } from '<?= BASE_URL ?>assets/js/api-helper.js';

    let categoriesData = [];

    const fetchCategories = async () => {
        try {
            const response = await API.categories.list({ enriched: true });
            if (response.success && response.data) {
                return response.data.items || response.data || [];
            }
            return [];
        } catch (e) {
            console.error('[Categories] Failed to load categories:', e);
            return [];
        }
    };

    const fixImagePath = (path) => path ? (path.startsWith('http') ? path : `<?= BASE_URL ?>assets/images/${path}`) : `<?= BASE_URL ?>assets/images/placeholder-product.png`;

    const renderCard = (cat) => {
        const productCount = parseInt(cat.product_count) || 0;
        return `
            <div class="card-premium group w-full max-w-sm bg-white border border-gray-100 flex flex-col relative overflow-hidden transition-all duration-500 hover:border-black" data-id="${cat.id}">
                <div class="card-premium-image-wrapper !bg-gray-50/50 overflow-hidden cursor-pointer relative p-6">
                    <img src="${fixImagePath(cat.image_url)}" 
                         alt="${cat.name}" 
                         class="card-premium-image !object-contain h-[260px] w-full transition-transform duration-1000 group-hover:scale-110" 
                         loading="lazy"
                         onerror="this.src='<?= BASE_URL ?>assets/images/placeholder-product.png'; this.onerror=null;">
                    
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex items-center justify-center z-10">
                        <div class="text-white text-[10px] font-black uppercase tracking-[0.3em] border border-white/30 px-6 py-3 backdrop-blur-sm">View Series</div>
                    </div>
                </div>
                
                <div class="p-8 flex flex-col w-full flex-grow">
                    <div class="flex flex-col items-center justify-center flex-grow mb-8 text-center">
                        <span class="text-[9px] uppercase font-black tracking-[0.3em] text-gray-400 mb-2 block">
                            ${productCount} Varieties
                        </span>
                        <h3 class="text-sm font-heading uppercase tracking-widest group-hover:text-gold transition-colors">
                            ${cat.name}
                        </h3>
                    </div>
                    
                    <div class="flex gap-2 w-full mt-auto relative z-20">
                        <a href="<?= BASE_URL ?>category.php?id=${cat.id}" class="btn-premium-outline flex-grow h-12 text-[9px] flex items-center justify-center cursor-pointer" style="padding: 0 0.5rem;">Browse Collection</a>
                    </div>
                </div>
            </div>
        `;
    };

    const renderDetail = (cat) => {
        if (!cat) return `<div class="p-20 text-center uppercase tracking-widest text-red-500">Resource Unreachable</div>`;
        return `
            <div class="w-full md:w-1/2 bg-[#f4f4f4] flex items-center justify-center p-8 md:p-12 h-[300px] md:h-auto">
                <img src="${fixImagePath(cat.image_url)}" alt="${cat.name}" class="w-full h-full object-contain max-h-[400px]" onerror="this.src='<?= BASE_URL ?>assets/images/placeholder-product.png'">
            </div>
            <div class="w-full md:w-1/2 p-8 md:p-12 flex flex-col justify-center bg-white">
                <span class="text-[10px] font-bold uppercase tracking-[0.4em] text-gray-400 mb-2">Series Overview</span>
                <h2 class="text-2xl md:text-3xl font-serif font-bold uppercase tracking-widest mb-4 text-black border-b border-gray-100 pb-4">${cat.name}</h2>
                <p class="text-gray-500 text-sm leading-relaxed italic font-light mb-8">${cat.description || 'Our master distillers have curated this collection with uncompromising standards.'}</p>
                
                <div class="grid grid-cols-2 gap-6 mb-8 py-6 border-y border-gray-50">
                    <div>
                        <span class="block text-[9px] uppercase font-bold tracking-widest text-gray-400 mb-1">Stock Count</span>
                        <span class="text-[11px] font-bold uppercase">${cat.product_count || 0} Registered</span>
                    </div>
                    <div>
                        <span class="block text-[9px] uppercase font-bold tracking-widest text-gray-400 mb-1">Status</span>
                        <span class="text-[11px] font-bold text-green-600 uppercase">Active</span>
                    </div>
                </div>
                
                <a href="<?= BASE_URL ?>category.php?id=${cat.id}" class="bg-black text-white w-full h-12 flex items-center justify-center text-[10px] uppercase font-bold tracking-widest hover:bg-gray-800 transition-colors gap-4 group">
                    Enter Collection
                    <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        `;
    };

    const initCategories = async () => {
        const container = document.querySelector('.categories-container');
        if (!container) return;

        const excludeId = <?= (int)$excludeCategoryId ?>;
        const allCategories = await fetchCategories();
        
        categoriesData = excludeId > 0 ? allCategories.filter(c => c.id != excludeId) : allCategories;

        if (categoriesData.length === 0) {
            container.innerHTML = '<div class="col-span-full py-32 text-center uppercase tracking-widest text-gray-400 font-bold">No collections found.</div>';
            return;
        }
        container.innerHTML = categoriesData.map(renderCard).join('');
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCategories);
    } else {
        initCategories();
    }

    document.addEventListener('click', async (e) => {
        const modal = document.getElementById('detailModalCategory');
        const modalContent = document.getElementById('detailModalCategoryContent');
        const body = document.getElementById('modalBodyCategory');
        const clickable = e.target.closest('.btn-details-category') || e.target.closest('.card-premium-image-wrapper');
        const card = e.target.closest('.card-premium');
        
        if (clickable && card) {
            modal.classList.remove('opacity-0', 'invisible');
            modal.classList.add('opacity-100', 'visible');
            modalContent.classList.remove('scale-95');
            modalContent.classList.add('scale-100');
            document.body.style.overflow = 'hidden';

            body.innerHTML = '<div class="w-full p-20 text-center uppercase tracking-widest text-[10px] font-black animate-pulse">Loading Collection...</div>';
            const catId = card.dataset.id;
            const cat = categoriesData.find(c => c.id == catId);
            setTimeout(() => { body.innerHTML = renderDetail(cat); }, 400);
        }

        if (e.target.closest('#closeModalCategory') || (e.target === modal)) {
            modal.classList.add('opacity-0', 'invisible');
            modal.classList.remove('opacity-100', 'visible');
            modalContent.classList.add('scale-95');
            modalContent.classList.remove('scale-100');
            document.body.style.overflow = '';
        }
    });

    document.addEventListener('keydown', (e) => { 
        if (e.key === 'Escape') {
            const modal = document.getElementById('detailModalCategory');
            const modalContent = document.getElementById('detailModalCategoryContent');
            modal.classList.add('opacity-0', 'invisible');
            modal.classList.remove('opacity-100', 'visible');
            modalContent.classList.add('scale-95');
            modalContent.classList.remove('scale-100');
            document.body.style.overflow = '';
        }
    });
</script>
