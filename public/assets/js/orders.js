/**
 * Order Management Utility
 * Uses centralized API helper for backend calls
 */

import { API } from './api-helper.js';

/**
 * Create order from cart
 * @param {Object} orderData - Order data
 * @param {number} orderData.userId - User ID
 * @param {number} orderData.addressId - Shipping address ID
 * @param {string} orderData.paymentMethod - Payment method
 * @param {number} orderData.totalCents - Total amount in cents
 * @param {Array} orderData.items - Order items
 * @returns {Promise<Object|null>} - Created order or null
 */
export async function createOrder(orderData) {
  try {
    const { userId, addressId, paymentMethod, totalCents, items } = orderData;

    // Create order record
    const orderResponse = await API.orders.create({
      user_id: userId,
      address_id: addressId,
      payment_method: paymentMethod,
      total_cents: totalCents,
      status: 'pending',
    });

    if (!orderResponse.success) {
      throw new Error(orderResponse.message || 'Failed to create order');
    }

    const order = orderResponse.data;

    // Create order items
    const itemsResult = await createOrderItems(order.id, items);

    if (!itemsResult) {
      throw new Error('Failed to create order items');
    }

    return order;
  } catch (error) {
    console.error('Error creating order:', error);
    return null;
  }
}

/**
 * Create order items
 * @param {number} orderId - Order ID
 * @param {Array} items - Order items
 * @returns {Promise<boolean>} - Success status
 */
async function createOrderItems(orderId, items) {
  try {
    const promises = items.map((item) =>
      API.request('/order-items', {
        method: 'POST',
        body: {
          order_id: orderId,
          product_id: item.id,
          product_name: item.name,
          quantity: item.quantity,
          price_cents: item.price_cents,
        },
      })
    );

    const responses = await Promise.all(promises);
    return responses.every((response) => response.success);
  } catch (error) {
    console.error('Error creating order items:', error);
    return false;
  }
}

/**
 * Fetch user orders
 * @param {number} userId - User ID
 * @returns {Promise<Array>} - Array of orders
 */
export async function fetchUserOrders(userId) {
  try {
    const response = await API.orders.getByUser(userId);

    if (response.success && response.data) {
      return response.data?.items || response.data || [];
    }

    return [];
  } catch (error) {
    console.error('Error fetching user orders:', error);
    return [];
  }
}

/**
 * Fetch order details
 * @param {number} orderId - Order ID
 * @returns {Promise<Object|null>} - Order details or null
 */
export async function fetchOrderDetails(orderId) {
  try {
    const response = await API.orders.get(orderId);

    if (response.success && response.data) {
      return response.data;
    }

    return null;
  } catch (error) {
    console.error('Error fetching order details:', error);
    return null;
  }
}

/**
 * Fetch order items
 * @param {number} orderId - Order ID
 * @returns {Promise<Array>} - Order items
 */
export async function fetchOrderItems(orderId) {
  try {
    const response = await API.orders.getItems(orderId);

    if (response.success && response.data) {
      return response.data?.items || response.data || [];
    }

    return [];
  } catch (error) {
    console.error('Error fetching order items:', error);
    return [];
  }
}

/**
 * Cancel order
 * @param {number} orderId - Order ID
 * @returns {Promise<boolean>} - Success status
 */
export async function cancelOrder(orderId) {
  try {
    const response = await API.request(`/orders/${orderId}`, {
      method: 'PUT',
      body: { status: 'cancelled' },
    });

    return response.success;
  } catch (error) {
    console.error('Error cancelling order:', error);
    return false;
  }
}

export default {
  createOrder,
  fetchUserOrders,
  fetchOrderDetails,
  fetchOrderItems,
  cancelOrder,
};
