import { apiRequest } from '../../utils.js';
import { API_ROUTES } from '../../dashboard.routes.js';
import { DETAIL_VIEW_API_URL } from '../config.js';

/**
 * Fetches dashboard overview statistics from the API.
 * @returns {Promise<Object>} Dashboard stats object
 */
export async function fetchDashboard() {
  try {
    // Use relative path up to the API router root to bypass XAMPP local domain issues
    const response = await apiRequest(API_ROUTES.ADMIN_VIEWS.DASHBOARD);
    if (response && response.success) {
      return response.data;
    } else {
      return { error: response?.message || 'Failed to fetch dashboard data.' };
    }
  } catch (err) {
    return { error: err.message || 'Network error while fetching dashboard data.' };
  }
}
