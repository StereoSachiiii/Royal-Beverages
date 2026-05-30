<?php
$pageName = 'taste-profile';
$pageTitle = 'My Taste Profile - Royal Beverages';
require_once __DIR__ . "/../components/header.php";
?>

<main class="min-h-screen bg-white py-10 px-4 sm:px-6 lg:px-8 font-body text-gray-900">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="mb-10 text-center">
            <h1 class="font-heading text-3xl font-medium tracking-tight mb-3">Taste Profile</h1>
            <p class="text-gray-500 text-sm max-w-xl mx-auto">Adjust the sliders to match your preferences and get personalized recommendations.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-start">
            
            <!-- Sliders -->
            <div>
                <div class="space-y-8">
                    <?php
                    $attributes = [
                        ['id' => 'sweetness', 'label' => 'Sweetness', 'low' => 'Dry', 'high' => 'Sweet'],
                        ['id' => 'bitterness', 'label' => 'Bitterness', 'low' => 'Smooth', 'high' => 'Bitter'],
                        ['id' => 'strength', 'label' => 'Strength', 'low' => 'Light', 'high' => 'Strong'],
                        ['id' => 'smokiness', 'label' => 'Smokiness', 'low' => 'Clean', 'high' => 'Smoky'],
                        ['id' => 'fruitiness', 'label' => 'Fruitiness', 'low' => 'Neutral', 'high' => 'Fruity'],
                        ['id' => 'spiciness', 'label' => 'Spiciness', 'low' => 'Mild', 'high' => 'Spicy'],
                    ];
                    foreach ($attributes as $attr):
                    ?>
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-medium text-sm"><?= $attr['label'] ?></span>
                            <span class="text-xs font-mono bg-gray-100 px-2 py-1 rounded" id="<?= $attr['id'] ?>Value">5</span>
                        </div>
                        <input type="range" id="<?= $attr['id'] ?>" min="0" max="10" value="5" class="w-full h-2 bg-gray-200 rounded-lg cursor-pointer accent-black mb-1">
                        <div class="flex justify-between text-xs text-gray-500">
                            <span><?= $attr['low'] ?></span>
                            <span><?= $attr['high'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-8 flex gap-3">
                    <button id="saveBtn" class="flex-1 px-4 py-2 bg-black text-white text-sm hover:bg-gray-800 transition-colors rounded">
                        Save
                    </button>
                    <button id="resetBtn" class="px-4 py-2 bg-gray-100 text-black text-sm hover:bg-gray-200 transition-colors rounded">
                        Reset
                    </button>
                </div>
                
                <div id="saveFeedback" class="hidden mt-4 p-3 bg-green-50 text-green-700 text-sm rounded text-center">
                    Preferences saved!
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="bg-gray-50 rounded-lg p-6">
                <h3 class="font-medium text-lg mb-4 border-b border-gray-200 pb-2">Top Matches</h3>
                
                <div id="matchesList" class="space-y-4 max-h-[400px] overflow-y-auto">
                    <div class="py-8 text-center text-sm text-gray-500">
                        Loading...
                    </div>
                </div>
                
                <a href="<?= getPageUrl('shop') ?>?sort=match" class="mt-6 block w-full text-center px-4 py-2 border border-black text-black text-sm hover:bg-black hover:text-white transition-colors rounded">
                    View All in Shop
                </a>
            </div>
            
        </div>
    </div>
</main>

<script type="module">
import { API } from '<?= BASE_URL ?>assets/js/api-helper.js?v=<?= time() ?>';
import { getTastePreferences, saveTastePreferences } from '<?= BASE_URL ?>assets/js/preferences-storage.js?v=<?= time() ?>';
import { fixImagePath } from '<?= BASE_URL ?>assets/js/ui-components.js?v=<?= time() ?>';

const currentUserId = <?= \App\Core\Session::getInstance()->get('user_id') ?? 'null' ?>;
const attributes = ['sweetness', 'bitterness', 'strength', 'smokiness', 'fruitiness', 'spiciness'];
let currentPrefsId = null;

const init = async () => {
    await loadPreferences();
    setupEventListeners();
    await updatePreview();
};



const loadPreferences = async () => {
    const prefs = await getTastePreferences(currentUserId);
    currentPrefsId = prefs.id || null;
    
    attributes.forEach(attr => {
        const slider = document.getElementById(attr);
        if (slider) {
            slider.value = prefs[attr] !== undefined ? prefs[attr] : 5;
            document.getElementById(`${attr}Value`).textContent = slider.value;
        }
    });
};

const getCurrentValues = () => {
    const values = {};
    attributes.forEach(attr => {
        const slider = document.getElementById(attr);
        values[attr] = parseInt(slider?.value || 5);
    });
    return values;
};

const updatePreview = async () => {
    const prefs = getCurrentValues();
    const matchesList = document.getElementById('matchesList');
    
    matchesList.innerHTML = '<div class="py-8 text-center text-sm text-gray-500">Loading matches...</div>';
    
    try {
        const response = await API.recommendations.previewMatches(prefs);
        const topMatches = response.data || [];
        
        if (topMatches.length === 0) {
            matchesList.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">No matches found.</p>';
            return;
        }
        
        const currencyFormatter = new Intl.NumberFormat('en-LK', { style: 'currency', currency: 'LKR' });
        
        matchesList.innerHTML = topMatches.map(p => {
            const price = currencyFormatter.format(p.price_cents / 100);
            return `
                <a href="../product.php?id=${p.id}" class="flex items-center gap-3 group">
                    <img src="${fixImagePath(p.image_url)}" alt="${p.name}" class="w-12 h-12 object-contain rounded bg-white border border-gray-100" onerror="this.src='<?= BASE_URL ?>assets/images/placeholder-product.webp'">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 truncate group-hover:underline">${p.name}</div>
                        <div class="text-xs text-gray-500">${price}</div>
                    </div>
                    <div class="text-xs font-bold text-gray-700 bg-gray-200 px-2 py-1 rounded">
                        ${p.matchScore}%
                    </div>
                </a>
            `;
        }).join('');
    } catch (e) {
        console.error('Failed to load dynamic matches', e);
        matchesList.innerHTML = '<p class="text-sm text-red-500 text-center py-4">Failed to load matches.</p>';
    }
};

const savePreferences = async () => {
    const values = getCurrentValues();
    if (currentPrefsId) values.id = currentPrefsId;
    
    const success = await saveTastePreferences(values, currentUserId);
    
    if (success) {
        const feedback = document.getElementById('saveFeedback');
        feedback.classList.remove('hidden');
        setTimeout(() => feedback.classList.add('hidden'), 3000);
    }
};

const resetPreferences = async () => {
    attributes.forEach(attr => {
        const slider = document.getElementById(attr);
        if (slider) {
            slider.value = 5;
            document.getElementById(`${attr}Value`).textContent = 5;
        }
    });
    await updatePreview();
};

const setupEventListeners = () => {
    attributes.forEach(attr => {
        const slider = document.getElementById(attr);
        if (slider) {
            slider.addEventListener('input', (e) => {
                document.getElementById(`${attr}Value`).textContent = e.target.value;
            });
            slider.addEventListener('change', async () => {
                await updatePreview();
            });
        }
    });
    
    document.getElementById('saveBtn').addEventListener('click', savePreferences);
    document.getElementById('resetBtn').addEventListener('click', resetPreferences);
};

document.addEventListener('DOMContentLoaded', init);
</script>

<?php require_once __DIR__ . "/../components/footer.php"; ?>
