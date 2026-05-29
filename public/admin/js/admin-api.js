import { API_ROUTES, buildQueryString } from './dashboard.routes.js';
import { apiRequest } from './utils.js';

/**
 * AdminAPI
 * A centralized client for interacting with the backend.
 * This completely abstracts away URLs and raw fetch/apiRequest calls from components.
 */
export const AdminAPI = {};

// Helper to determine HTTP method from action name
function getMethod(action) {
    if (action === 'CREATE') return 'POST';
    if (action === 'UPDATE') return 'PUT';
    if (action === 'DELETE') return 'DELETE';
    return 'GET';
}

// Dynamically generate the API client based on API_ROUTES
Object.entries(API_ROUTES).forEach(([modelKey, endpoints]) => {
    // Convert MODEL_NAME to camelCase (e.g., USER_PREFERENCES -> userPreferences)
    const model = modelKey.toLowerCase().replace(/_([a-z])/g, (g) => g[1].toUpperCase());
    AdminAPI[model] = {};

    Object.entries(endpoints).forEach(([actionKey, pathOrFn]) => {
        // Convert ACTION_NAME to camelCase (e.g., BY_USER -> byUser)
        const action = actionKey.toLowerCase().replace(/_([a-z])/g, (g) => g[1].toUpperCase());
        const method = getMethod(actionKey);

        AdminAPI[model][action] = async (...args) => {
            let url = '';
            let payload = null;

            // Handle URL construction
            if (typeof pathOrFn === 'function') {
                // If it's a function (e.g., UPDATE: (id) => `/products/${id}`), 
                // the first args are used for the URL.
                // Assuming max 1 URL argument for our routes.
                const urlArg = args.shift(); 
                url = pathOrFn(urlArg);
            } else {
                url = pathOrFn;
            }

            // Handle payload / query params
            if (args.length > 0) {
                payload = args.shift();
            }

            const options = { method };

            if (payload) {
                if (method === 'GET' || method === 'DELETE') {
                    // For GET/DELETE, treat payload as query params if it's a plain object
                    if (typeof payload === 'object' && payload !== null && !(payload instanceof FormData)) {
                        url += buildQueryString(payload);
                    }
                } else {
                    // For POST/PUT, treat payload as body
                    options.body = payload;
                }
            }

            return await apiRequest(url, options);
        };
    });
});

window.AdminAPI = AdminAPI; // Expose globally for inline form scripts
