/**
 * Products API Utility
 * Uses centralized API helper for real backend calls
 */

import { API } from './api-helper.js';

/**
 * Fetch product by ID
 */
export async function fetchProductById(id) {
  try {
    const response = await API.products.get(parseInt(id));
    if (response.success && response.data) {
      return response.data;
    }
    return null;
  } catch (error) {
    console.error('Error fetching product:', error);
    return null;
  }
}

// Alias for compatibility with cart-storage.js
export const fetchProduct = fetchProductById;

/**
 * Fetch all products with optional params
 */
export async function fetchAllProducts(params = {}) {
  try {
    const response = await API.products.list(params);
    if (response.success) {
      // Handle both array and paginated response
      return response.data?.items || response.data || [];
    }
    return [];
  } catch (error) {
    console.error('Error fetching products:', error);
    return [];
  }
}

/**
 * Search products
 */
export async function searchProducts(query, params = {}) {
  try {
    const response = await API.products.search(query, params);
    if (response.success) {
      return response.data?.items || response.data || [];
    }
    return [];
  } catch (error) {
    console.error('Error searching products:', error);
    return [];
  }
}

/**
 * Fetch products by category
 */
export async function fetchProductsByCategory(categoryId, params = {}) {
  try {
    const response = await API.products.getByCategory(categoryId, params);
    if (response.success) {
      return response.data?.items || response.data || [];
    }
    return [];
  } catch (error) {
    console.error('Error fetching products by category:', error);
    return [];
  }
}

/**
 * Fetch category by ID
 */
export async function fetchCategoryById(id) {
  try {
    const response = await API.categories.get(parseInt(id));
    return response.success ? response.data : null;
  } catch (error) {
    console.error('Error fetching category:', error);
    return null;
  }
}

/**
 * Fetch all categories
 */
export async function fetchAllCategories() {
  try {
    const response = await API.categories.list();
    if (response.success) {
      return response.data?.items || response.data || [];
    }
    return [];
  } catch (error) {
    console.error('Error fetching categories:', error);
    return [];
  }
}

/**
 * Get product with full details (flavor profile, rating, stock)
 */
export async function fetchProductModalData(productId) {
  const product = await fetchProductById(productId);
  if (!product) return { error: 'Product not found' };

  // Parse flavor profile if it exists
  let flavorProfile = null;
  try {
    if (product.flavor_profile) {
      flavorProfile =
        typeof product.flavor_profile === 'string'
          ? JSON.parse(product.flavor_profile)
          : product.flavor_profile;
    }
  } catch (e) {
    console.warn('Could not parse flavor profile');
  }

  return {
    id: product.id,
    name: product.name,
    description: product.description,
    price_cents: product.price_cents,
    image_url: product.image_url,
    is_active: product.is_available ?? product.is_active,
    category: product.category_name || null,
    category_id: product.category_id || null,
    supplier: product.supplier_name || null,
    stock: product.stock || [
      {
        warehouse: 'Main Warehouse',
        quantity: product.available_stock || 0,
        reserved: 0,
      },
    ],
    flavor_profile: flavorProfile,
    feedback: {
      average_rating: product.avg_rating || null,
      review_count: product.feedback_count || 0,
    },
  };
}

/**
 * Get featured products
 */
export async function fetchFeaturedProducts(limit = 10) {
  try {
    const response = await API.products.featured({ limit });
    if (response.success) {
      return response.data?.items || response.data || [];
    }
    return [];
  } catch (error) {
    console.error('Error fetching featured products:', error);
    return [];
  }
}

export default {
  fetchProduct,
  fetchProductById,
  fetchAllProducts,
  searchProducts,
  fetchProductsByCategory,
  fetchCategoryById,
  fetchAllCategories,
  fetchProductModalData,
  fetchFeaturedProducts,
};
