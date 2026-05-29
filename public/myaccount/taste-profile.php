<?php
$pageName = 'taste-profile';
$pageTitle = 'My Taste Profile - Royal Beverages';
require_once __DIR__ . "/../components/header.php";
?>

<main class="taste-profile-page">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <nav class="breadcrumb">
                <a href="<?= BASE_URL ?>">Home</a>
                <span class="separator">›</span>
                <a href="<?= getPageUrl('account') ?>">My Account</a>
                <span class="separator">›</span>
                <span class="current">Taste Profile</span>
            </nav>
            <h1 class="page-title">My Taste Profile</h1>
            <p class="page-subtitle">Customize your flavor preferences to get personalized product recommendations</p>
        </div>

        <div class="profile-grid">
            <!-- Preference Sliders -->
            <div class="preferences-card">
                <h2 class="card-title">Flavor Preferences</h2>
                <p class="card-description">Adjust the sliders to match your taste. Products will be scored based on how well they match your preferences.</p>
                
                <div class="preference-sliders">
                    <div class="slider-group">
                        <label class="slider-label">
                            <span class="label-text">🍯 Sweetness</span>
                            <span class="label-value" id="sweetnessValue">5</span>
                        </label>
                        <input type="range" class="preference-slider" id="sweetness" min="0" max="10" value="5">
                        <div class="slider-hints">
                            <span>Dry</span>
                            <span>Sweet</span>
                        </div>
                    </div>
                    
                    <div class="slider-group">
                        <label class="slider-label">
                            <span class="label-text">☕ Bitterness</span>
                            <span class="label-value" id="bitternessValue">5</span>
                        </label>
                        <input type="range" class="preference-slider" id="bitterness" min="0" max="10" value="5">
                        <div class="slider-hints">
                            <span>Smooth</span>
                            <span>Bitter</span>
                        </div>
                    </div>
                    
                    <div class="slider-group">
                        <label class="slider-label">
                            <span class="label-text">💪 Strength</span>
                            <span class="label-value" id="strengthValue">5</span>
                        </label>
                        <input type="range" class="preference-slider" id="strength" min="0" max="10" value="5">
                        <div class="slider-hints">
                            <span>Light</span>
                            <span>Strong</span>
                        </div>
                    </div>
                    
                    <div class="slider-group">
                        <label class="slider-label">
                            <span class="label-text">💨 Smokiness</span>
                            <span class="label-value" id="smokinessValue">5</span>
                        </label>
                        <input type="range" class="preference-slider" id="smokiness" min="0" max="10" value="5">
                        <div class="slider-hints">
                            <span>Clean</span>
                            <span>Smoky</span>
                        </div>
                    </div>
                    
                    <div class="slider-group">
                        <label class="slider-label">
                            <span class="label-text">🍎 Fruitiness</span>
                            <span class="label-value" id="fruitinessValue">5</span>
                        </label>
                        <input type="range" class="preference-slider" id="fruitiness" min="0" max="10" value="5">
                        <div class="slider-hints">
                            <span>Neutral</span>
                            <span>Fruity</span>
                        </div>
                    </div>
                    
                    <div class="slider-group">
                        <label class="slider-label">
                            <span class="label-text">🌶️ Spiciness</span>
                            <span class="label-value" id="spicinessValue">5</span>
                        </label>
                        <input type="range" class="preference-slider" id="spiciness" min="0" max="10" value="5">
                        <div class="slider-hints">
                            <span>Mild</span>
                            <span>Spicy</span>
                        </div>
                    </div>
                </div>
                
                <div class="preferences-actions">
                    <button class="btn btn-gold btn-lg" id="saveBtn">Save Preferences</button>
                    <button class="btn btn-outline" id="resetBtn">Reset to Default</button>
                </div>
                
                <div class="save-feedback" id="saveFeedback" style="display: none;">
                    <span class="feedback-icon">✓</span>
                    <span>Preferences saved! Products will now show match scores.</span>
                </div>
            </div>
            
            <!-- Preview Panel -->
            <div class="preview-card">
                <h2 class="card-title">Top Matches For You</h2>
                <p class="card-description">Based on your current preferences</p>
                
                <div class="matches-list" id="matchesList">
                    <!-- Will be populated dynamically -->
                    <div class="loading-matches">
                        <div class="loading-spinner"></div>
                        <p>Loading recommendations...</p>
                    </div>
                </div>
                
                <a href="<?= getPageUrl('shop') ?>?sort=match" class="btn btn-outline btn-block" id="viewAllMatches">
                    View All Matches in Shop
                </a>
            </div>
        </div>
    </div>
</main>

<style>
.taste-profile-page {
    padding: var(--space-xl) 0 var(--space-3xl);
    background: var(--gray-50);
    min-height: calc(100vh - 200px);
}

.page-header {
    margin-bottom: var(--space-2xl);
}

.breadcrumb {
    padding: 0 0 var(--space-md);
    font-size: 0.9rem;
    color: var(--gray-500);
}

.breadcrumb a {
    color: var(--gray-600);
    text-decoration: none;
}

.breadcrumb a:hover {
    color: var(--gold);
}

.breadcrumb .separator {
    margin: 0 var(--space-sm);
    color: var(--gray-300);
}

.breadcrumb .current {
    color: var(--black);
}

.page-title {
    font-family: var(--font-serif);
    font-size: 2.5rem;
    font-weight: 300;
    font-style: italic;
    margin-bottom: var(--space-sm);
}

.page-title::after {
    content: '';
    display: block;
    width: 60px;
    height: 2px;
    background: linear-gradient(90deg, var(--gold), transparent);
    margin-top: var(--space-sm);
}

.page-subtitle {
    color: var(--gray-500);
    font-size: 1.1rem;
}

/* Profile Grid */
.profile-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: var(--space-2xl);
    align-items: start;
}

/* Cards */
.preferences-card,
.preview-card {
    background: var(--white);
    border-radius: var(--radius-xl);
    padding: var(--space-2xl);
    box-shadow: var(--shadow-sm);
}

.card-title {
    font-family: var(--font-serif);
    font-size: 1.5rem;
    font-weight: 500;
    font-style: italic;
    margin-bottom: var(--space-sm);
}

.card-description {
    color: var(--gray-500);
    margin-bottom: var(--space-xl);
}

/* Sliders */
.preference-sliders {
    display: flex;
    flex-direction: column;
    gap: var(--space-xl);
}

.slider-group {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
}

.slider-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.label-text {
    font-weight: 600;
    font-size: 1rem;
}

.label-value {
    background: var(--gold);
    color: var(--black);
    padding: var(--space-xs) var(--space-sm);
    border-radius: var(--radius-sm);
    font-weight: 700;
    font-size: 0.9rem;
    min-width: 32px;
    text-align: center;
}

.preference-slider {
    -webkit-appearance: none;
    width: 100%;
    height: 8px;
    background: var(--gray-200);
    border-radius: var(--radius-full);
    outline: none;
}

.preference-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 24px;
    height: 24px;
    background: var(--gold);
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(212, 175, 55, 0.4);
    transition: transform var(--duration-fast);
}

.preference-slider::-webkit-slider-thumb:hover {
    transform: scale(1.15);
}

.preference-slider::-moz-range-thumb {
    width: 24px;
    height: 24px;
    background: var(--gold);
    border-radius: 50%;
    cursor: pointer;
    border: none;
}

.slider-hints {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: var(--gray-400);
}

/* Actions */
.preferences-actions {
    display: flex;
    gap: var(--space-md);
    margin-top: var(--space-2xl);
    padding-top: var(--space-xl);
    border-top: 1px solid var(--gray-100);
}

.save-feedback {
    margin-top: var(--space-lg);
    padding: var(--space-md);
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
    border-radius: var(--radius-md);
    color: #16a34a;
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    font-weight: 500;
}

.feedback-icon {
    font-size: 1.2rem;
}

/* Preview Panel */
.preview-card {
    position: sticky;
    top: 100px;
}

.matches-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-md);
    margin-bottom: var(--space-xl);
    max-height: 400px;
    overflow-y: auto;
}

.loading-matches {
    text-align: center;
    padding: var(--space-xl);
    color: var(--gray-400);
}

.loading-spinner {
    width: 30px;
    height: 30px;
    border: 3px solid var(--gray-200);
    border-top-color: var(--gold);
    border-radius: 50%;
    margin: 0 auto var(--space-md);
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.match-item {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    padding: var(--space-md);
    background: var(--gray-50);
    border-radius: var(--radius-md);
    text-decoration: none;
    transition: all var(--duration-fast);
}

.match-item:hover {
    background: rgba(212, 175, 55, 0.1);
    transform: translateX(4px);
}

.match-image {
    width: 50px;
    height: 50px;
    border-radius: var(--radius-md);
    object-fit: cover;
}

.match-info {
    flex: 1;
    min-width: 0;
}

.match-name {
    font-weight: 600;
    color: var(--black);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}

.match-category {
    font-size: 0.8rem;
    color: var(--gray-500);
}

.match-score {
    padding: var(--space-xs) var(--space-sm);
    font-size: 0.8rem;
    font-weight: 700;
    border-radius: var(--radius-sm);
}

.match-score.high {
    background: linear-gradient(135deg, #22c55e 0%, #4ade80 100%);
    color: #fff;
}

.match-score.medium {
    background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 100%);
    color: var(--black);
}

.match-score.low {
    background: var(--gray-300);
    color: var(--gray-600);
}

.btn-block {
    width: 100%;
    text-align: center;
}

/* Responsive */
@media (max-width: 1024px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
    
    .preview-card {
        position: static;
    }
}

@media (max-width: 768px) {
    .page-title {
        font-size: 1.75rem;
    }
    
    .preferences-actions {
        flex-direction: column;
    }
}
</style>

<script type="module">
import { API } from '<?= BASE_URL ?>assets/js/api-helper.js';
import { getTastePreferences, saveTastePreferences, calculateMatchScore } from '<?= BASE_URL ?>assets/js/preferences-storage.js';

const currentUserId = <?= \App\Core\Session::getInstance()->get('user_id') ?? 'null' ?>;

const attributes = ['sweetness', 'bitterness', 'strength', 'smokiness', 'fruitiness', 'spiciness'];
let allProducts = [];
let currentPrefsId = null; // Store DB id if loaded

// Initialize
const init = async () => {
    await loadProducts();
    await loadPreferences();
    setupEventListeners();
    await updatePreview();
};

// Load products for preview
const loadProducts = async () => {
    try {
        const response = await API.request('/products/enriched/all' + API.buildQuery({ limit: 100 }));
        if (response.success && response.data) {
            allProducts = response.data;
        }
    } catch (error) {
        console.error('[TasteProfile] Failed to load products:', error);
    }
};

// Load saved preferences into sliders
const loadPreferences = async () => {
    const prefs = await getTastePreferences(currentUserId);
    currentPrefsId = prefs.id || null;
    
    attributes.forEach(attr => {
        const slider = document.getElementById(attr);
        const valueEl = document.getElementById(`${attr}Value`);
        if (slider && valueEl) {
            slider.value = prefs[attr] || 5;
            valueEl.textContent = prefs[attr] || 5;
        }
    });
};

// Get current slider values
const getCurrentValues = () => {
    const values = {};
    attributes.forEach(attr => {
        const slider = document.getElementById(attr);
        values[attr] = parseInt(slider?.value || 5);
    });
    return values;
};

// Update preview panel with matching products
const updatePreview = async () => {
    const prefs = getCurrentValues();
    const matchesList = document.getElementById('matchesList');
    
    // Calculate match scores for all products
    const productsWithScores = allProducts.map(p => {
        try {
            const flavor = typeof p.flavor_profile === 'string' 
                ? JSON.parse(p.flavor_profile) 
                : p.flavor_profile;
            
            if (!flavor) return { ...p, matchScore: 0 };
            
            const maxDistance = Math.sqrt(600);
            const distance = Math.sqrt(
                attributes.reduce((sum, attr) => {
                    const diff = (prefs[attr] || 5) - (flavor[attr] || 5);
                    return sum + (diff * diff);
                }, 0)
            );
            
            const matchScore = Math.round(((maxDistance - distance) / maxDistance) * 100);
            return { ...p, matchScore: Math.max(0, Math.min(100, matchScore)) };
        } catch (e) {
            return { ...p, matchScore: 0 };
        }
    });
    
    // Sort by match score and take top 5
    const topMatches = productsWithScores
        .sort((a, b) => b.matchScore - a.matchScore)
        .slice(0, 5);
    
    if (topMatches.length === 0) {
        matchesList.innerHTML = '<p class="no-matches">No products to show</p>';
        return;
    }
    
    matchesList.innerHTML = topMatches.map(p => {
        const scoreClass = p.matchScore >= 80 ? 'high' : (p.matchScore >= 60 ? 'medium' : 'low');
        const price = (p.price_cents / 100).toFixed(2);
        return `
            <a href="product.php?id=${p.id}" class="match-item">
                <img src="${p.image_url}" alt="${p.name}" class="match-image">
                <div class="match-info">
                    <div class="match-name">${p.name}</div>
                    <div class="match-category">${p.category_name} • $${price}</div>
                </div>
                <span class="match-score ${scoreClass}">${p.matchScore}%</span>
            </a>
        `;
    }).join('');
};

// Save preferences
const savePreferences = async () => {
    const values = getCurrentValues();
    if (currentPrefsId) values.id = currentPrefsId;
    
    const success = await saveTastePreferences(values, currentUserId);
    
    if (success) {
        const feedback = document.getElementById('saveFeedback');
        feedback.style.display = 'flex';
        
        setTimeout(() => {
            feedback.style.display = 'none';
        }, 3000);
    }
};

// Reset to defaults
const resetPreferences = async () => {
    attributes.forEach(attr => {
        const slider = document.getElementById(attr);
        const valueEl = document.getElementById(`${attr}Value`);
        if (slider && valueEl) {
            slider.value = 5;
            valueEl.textContent = 5;
        }
    });
    await updatePreview();
};

// Setup event listeners
const setupEventListeners = () => {
    // Slider changes
    attributes.forEach(attr => {
        const slider = document.getElementById(attr);
        const valueEl = document.getElementById(`${attr}Value`);
        
        if (slider) {
            slider.addEventListener('input', async () => {
                valueEl.textContent = slider.value;
                await updatePreview();
            });
        }
    });
    
    // Save button
    document.getElementById('saveBtn').addEventListener('click', savePreferences);
    
    // Reset button
    document.getElementById('resetBtn').addEventListener('click', resetPreferences);
};

document.addEventListener('DOMContentLoaded', init);
</script>

<?php require_once __DIR__ . "/../components/footer.php"; ?>
