import { apiGet } from '../utils/apiUtils';

// Base URL for AuthService API
const AUTH_SERVICE_BASE_URL = import.meta.env.VITE_AUTH_SERVICE_URL || 'http://localhost:8000';

/**
 * Get all service fees with pagination and filters
 * @param {Object} params - Query parameters (page, per_page, search, type, date_from, date_to)
 * @returns {Promise} - API response
 */
export const getServiceFees = async (params = {}) => {
    try {
        const queryParams = {
            page: params.page || 1,
            per_page: params.per_page || 15,
            ...params
        };

        const response = await apiGet(`${AUTH_SERVICE_BASE_URL}/api/softpos/service-fees`, queryParams);
        return response;
    } catch (error) {
        console.error('Error in getServiceFees:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch service fees'
        };
    }
};

/**
 * Get a single service fee by ID
 * @param {number} serviceFeeId - Service fee ID
 * @returns {Promise} - API response
 */
export const getServiceFee = async (serviceFeeId) => {
    try {
        const response = await apiGet(`${AUTH_SERVICE_BASE_URL}/api/softpos/service-fees/${serviceFeeId}`);
        return response;
    } catch (error) {
        console.error('Error in getServiceFee:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch service fee'
        };
    }
};

/**
 * Get available service fee types
 * @returns {Promise} - API response
 */
export const getServiceFeeTypes = async () => {
    try {
        const response = await apiGet(`${AUTH_SERVICE_BASE_URL}/api/softpos/service-fees/types`);
        return response;
    } catch (error) {
        console.error('Error in getServiceFeeTypes:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch service fee types'
        };
    }
};

