/**
 * User Preferences Storage
 * Stores taste profile preferences in localStorage
 */

import { API } from './api-helper.js';

const STORAGE_KEY = 'royal_liquor_taste_profile';

// Default preferences
const DEFAULT_PREFERENCES = {
  sweetness: 5,
  bitterness: 5,
  strength: 5,
  smokiness: 5,
  fruitiness: 5,
  spiciness: 5,
  favoriteCategories: [],
  lastUpdated: null,
};

/**
 * Get user's taste preferences
 * @param {number|null} userId
 * @returns {Promise<Object>} User preferences object
 */
export async function getTastePreferences(userId = null) {
  if (userId) {
    try {
      const resp = await API.preferences.get(userId);
      // Assuming endpoint returns array of pref objects or a direct object
      const data = resp.data;
      if (resp.success && data) {
        // If the array has elements, or if it's directly an object
        const dbPref = Array.isArray(data) ? data[0] : data;
        if (dbPref && dbPref.id) {
          const prefs = {
            sweetness: dbPref.preferred_sweetness,
            bitterness: dbPref.preferred_bitterness,
            strength: dbPref.preferred_strength,
            smokiness: dbPref.preferred_smokiness,
            fruitiness: dbPref.preferred_fruitiness,
            spiciness: dbPref.preferred_spiciness,
            favoriteCategories: dbPref.favorite_categories || [],
            lastUpdated: dbPref.updated_at || new Date().toISOString(),
            id: dbPref.id,
          };
          localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
          return prefs;
        }
      }
    } catch (e) {
      console.error('[Preferences] Error reading from API:', e);
    }
  }

  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
      return { ...DEFAULT_PREFERENCES, ...JSON.parse(stored) };
    }
  } catch (e) {
    console.error('[Preferences] Error reading:', e);
  }
  return { ...DEFAULT_PREFERENCES };
}

/**
 * Save user's taste preferences
 * @param {Object} preferences - Preferences to save
 * @param {number|null} userId
 */
export async function saveTastePreferences(preferences, userId = null) {
  const toSave = {
    ...preferences,
    lastUpdated: new Date().toISOString(),
  };

  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(toSave));
  } catch (e) {
    console.error('[Preferences] Error saving locally:', e);
  }

  if (userId) {
    try {
      const payload = {
        user_id: userId,
        preferred_sweetness: parseInt(preferences.sweetness),
        preferred_bitterness: parseInt(preferences.bitterness),
        preferred_strength: parseInt(preferences.strength),
        preferred_smokiness: parseInt(preferences.smokiness),
        preferred_fruitiness: parseInt(preferences.fruitiness),
        preferred_spiciness: parseInt(preferences.spiciness),
      };

      if (preferences.id) {
        await API.preferences.update(preferences.id, payload);
      } else {
        await API.preferences.create(payload);
      }
    } catch (e) {
      console.error('[Preferences] Error saving to API:', e);
      return false;
    }
  }

  return true;
}

/**
 * Check if user has set preferences
 * @returns {Promise<boolean>}
 */
export async function hasPreferences(userId = null) {
  const prefs = await getTastePreferences(userId);
  return prefs.lastUpdated !== null;
}

/**
 * Clear user preferences
 * @returns {void}
 */
export function clearPreferences() {
  localStorage.removeItem(STORAGE_KEY);
}

/**
 * Calculate match score between user preferences and a product's flavor profile
 * @param {Object} product - Product with flavor_profile field
 * @returns {number} Match percentage 0-100
 */
export async function calculateMatchScore(product, userId = null) {
  const prefs = await getTastePreferences(userId);

  // If no preferences set, return 0
  if (!prefs.lastUpdated) return 0;

  try {
    const flavor =
      typeof product.flavor_profile === 'string'
        ? JSON.parse(product.flavor_profile)
        : product.flavor_profile;

    if (!flavor) return 0;

    const attributes = [
      'sweetness',
      'bitterness',
      'strength',
      'smokiness',
      'fruitiness',
      'spiciness',
    ];

    // Calculate distance
    const maxDistance = Math.sqrt(600); // sqrt(6 * 10^2)
    const distance = Math.sqrt(
      attributes.reduce((sum, attr) => {
        const diff = (prefs[attr] || 5) - (flavor[attr] || 5);
        return sum + diff * diff;
      }, 0)
    );

    // Convert to percentage
    const matchPercentage = Math.round(((maxDistance - distance) / maxDistance) * 100);
    return Math.max(0, Math.min(100, matchPercentage));
  } catch (e) {
    return 0;
  }
}

export async function sortByMatch(products, userId = null) {
  const scoredProducts = await Promise.all(
    products.map(async (p) => ({ ...p, matchScore: await calculateMatchScore(p, userId) }))
  );
  return scoredProducts.sort((a, b) => b.matchScore - a.matchScore);
}
