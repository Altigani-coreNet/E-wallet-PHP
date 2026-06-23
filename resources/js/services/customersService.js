import { apiGet, apiPost, apiPut, apiDelete } from '../utils/apiUtils';

// Base URL for AuthService API
const AUTH_SERVICE_BASE_URL = import.meta.env.VITE_AUTH_SERVICE_URL || 'http://localhost:8000';

/**
 * Get customers by IDs (for displaying in payment links list)
 * @param {Array} customerIds - Array of customer IDs
 * @returns {Promise} - API response with customer details
 */
export const getCustomersByIds = async (customerIds = []) => {
    try {
        if (!customerIds || customerIds.length === 0) {
            return {
                success: true,
                data: []
            };
        }

        // Filter out null/undefined values and get unique IDs
        const uniqueIds = [...new Set(customerIds.filter(id => id != null))];
        
        if (uniqueIds.length === 0) {
            return {
                success: true,
                data: []
            };
        }

        const response = await apiPost(`${AUTH_SERVICE_BASE_URL}/api/softpos/customers/by-ids`, {
            ids: uniqueIds
        });
        return response;
    } catch (error) {
        console.error('Error in getCustomersByIds:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch customers',
            data: []
        };
    }
};

/**
 * Get customers for select dropdown
 * @param {string} search - Search query
 * @returns {Promise} - API response
 */
export const getCustomersForSelect = async (search = '') => {
    try {
        const response = await apiGet(`${AUTH_SERVICE_BASE_URL}/api/softpos/customers/select`, { search });
        return response;
    } catch (error) {
        console.error('Error in getCustomersForSelect:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch customers'
        };
    }
};

/**
 * Get all customers with pagination
 * @param {Object} params - Query parameters
 * @returns {Promise} - API response
 */
export const getCustomers = async (params = {}) => {
    try {
        const queryParams = {
            page: params.page || 1,
            per_page: params.per_page || 15,
            ...params
        };

        const response = await apiGet(`${AUTH_SERVICE_BASE_URL}/api/softpos/customers`, queryParams);
        return response;
    } catch (error) {
        console.error('Error in getCustomers:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch customers'
        };
    }
};

