/**
 * Address Management Utility
 * Uses centralized API helper for backend calls
 */

import { API } from './api-helper.js';

/**
 * Fetch user address by ID
 * @param {Number} addressId
 * @returns {Promise<string>} Formatted address string
 */
export const fetchUserAddresses = async (addressId) => {
  try {
    const response = await API.addresses.get(addressId);

    if (response.success && response.data) {
      return formatAddress(response.data);
    }

    return 'Address unavailable';
  } catch (error) {
    console.error('Error fetching address:', error);
    return 'Address unavailable';
  }
};

/**
 * Format a full address object into a clean display string.
 *
 * @param {Object} addr - address object returned by API
 * @returns {String} formatted address
 */
export const formatAddress = (addr) => {
  if (!addr || typeof addr !== 'object') return 'Address unavailable';

  const { recipient_name, address_line1, address_line2, city, state, postal_code, country, phone } =
    addr;

  return [
    recipient_name ? `${recipient_name}` : '',
    address_line1 ? `${address_line1}` : '',
    address_line2 ? `${address_line2}` : '',
    city || state ? `${city || ''}${state ? ', ' + state : ''}` : '',
    postal_code ? `${postal_code}` : '',
    country ? `${country}` : '',
    phone ? `Phone: ${phone}` : '',
  ]
    .filter(Boolean) // remove empty fields
    .join(', ');
};

/**
 * Parse addresses to HTML for display
 */
export const parseAddresses = (addresses) => {
  if (!Array.isArray(addresses)) return '<p>No addresses found.</p>';

  return addresses
    .map(
      (addr) => `
        <div class="address-card">
            <strong>Type:</strong> ${addr.address_type || '-'}<br>
            <strong>Name:</strong> ${addr.recipient_name || '-'}<br>
            <strong>Phone:</strong> ${addr.phone || '-'}<br>
            <strong>Address:</strong> 
                ${addr.address_line1 || '-'} 
                ${addr.address_line2 || ''}<br>
            ${addr.city || '-'}, ${addr.state || '-'}<br>
            <strong>Postal Code:</strong> ${addr.postal_code || '-'}<br>
            <strong>Country:</strong> ${addr.country || '-'}
        </div>
    `
    )
    .join('');
};

/**
 * Fetch all addresses by user_id
 * @param {Number} userId
 * @returns {Promise<Array|Object>}
 */
export const getAddresses = async (userId) => {
  try {
    const response = await API.addresses.list(userId);

    if (response.success && response.data) {
      return response.data?.items || response.data || [];
    }

    return [];
  } catch (error) {
    console.error('Error fetching addresses:', error);
    return { error: error.message };
  }
};

/**
 * Create a new address
 * @param {Object} addressData
 * @returns {Promise<Object|null>}
 */
export const createAddress = async (addressData) => {
  try {
    const response = await API.addresses.create(addressData);

    if (response.success) {
      return response.data;
    }

    throw new Error(response.message || 'Failed to create address');
  } catch (error) {
    console.error('Error creating address:', error);
    return null;
  }
};

/**
 * Update an existing address
 * @param {number} addressId
 * @param {Object} addressData
 * @returns {Promise<Object|null>}
 */
export const updateAddress = async (addressId, addressData) => {
  try {
    const response = await API.addresses.update(addressId, addressData);

    if (response.success) {
      return response.data;
    }

    throw new Error(response.message || 'Failed to update address');
  } catch (error) {
    console.error('Error updating address:', error);
    return null;
  }
};

/**
 * Delete an address
 * @param {number} addressId
 * @returns {Promise<boolean>}
 */
export const deleteAddress = async (addressId) => {
  try {
    const response = await API.addresses.delete(addressId);
    return response.success;
  } catch (error) {
    console.error('Error deleting address:', error);
    return false;
  }
};

/**
 * Set address as default
 * @param {number} addressId
 * @returns {Promise<boolean>}
 */
export const setDefaultAddress = async (addressId) => {
  try {
    const response = await API.addresses.setDefault(addressId);
    return response.success;
  } catch (error) {
    console.error('Error setting default address:', error);
    return false;
  }
};

export default {
  fetchUserAddresses,
  formatAddress,
  parseAddresses,
  getAddresses,
  createAddress,
  updateAddress,
  deleteAddress,
  setDefaultAddress,
};
