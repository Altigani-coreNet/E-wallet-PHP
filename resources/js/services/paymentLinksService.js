import { apiGet, apiPost, apiPut, apiDelete } from '../utils/apiUtils';

// Base URL for API - SoftPos API
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8001';
const API_PREFIX = '/api/v1/merchant';

/**
 * Get all payment links with pagination and filters
 * @param {Object} params - Query parameters (page, per_page, search, customer, from_date, to_date)
 * @returns {Promise} - API response
 */
export const getPaymentLinks = async (params = {}) => {
    try {
        const queryParams = {
            page: params.page || 1,
            per_page: params.per_page || 15,
            ...params
        };

        const response = await apiGet(`${API_BASE_URL}${API_PREFIX}/payment-links`, queryParams);
        return response;
    } catch (error) {
        console.error('Error in getPaymentLinks:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch payment links'
        };
    }
};

/**
 * Get a single payment link by ID
 * @param {string} paymentLinkId - Payment link ID
 * @returns {Promise} - API response
 */
export const getPaymentLink = async (paymentLinkId) => {
    try {
        const response = await apiGet(`${API_BASE_URL}${API_PREFIX}/payment-links/${paymentLinkId}`);
        return response;
    } catch (error) {
        console.error('Error in getPaymentLink:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch payment link'
        };
    }
};

/**
 * Create a new payment link
 * @param {Object} paymentLinkData - Payment link data to create
 * @returns {Promise} - API response
 */
export const createPaymentLink = async (paymentLinkData) => {
    try {
        const response = await apiPost(`${API_BASE_URL}${API_PREFIX}/payment-links`, paymentLinkData);
        return response;
    } catch (error) {
        console.error('Error in createPaymentLink:', error);
        return {
            success: false,
            error: error.message || 'Failed to create payment link'
        };
    }
};

/**
 * Update an existing payment link
 * @param {string} paymentLinkId - Payment link ID
 * @param {Object} paymentLinkData - Updated payment link data
 * @returns {Promise} - API response
 */
export const updatePaymentLink = async (paymentLinkId, paymentLinkData) => {
    try {
        const response = await apiPut(`${API_BASE_URL}${API_PREFIX}/payment-links/${paymentLinkId}`, paymentLinkData);
        return response;
    } catch (error) {
        console.error('Error in updatePaymentLink:', error);
        return {
            success: false,
            error: error.message || 'Failed to update payment link'
        };
    }
};

/**
 * Delete a payment link
 * @param {string} paymentLinkId - Payment link ID
 * @returns {Promise} - API response
 */
export const deletePaymentLink = async (paymentLinkId) => {
    try {
        const response = await apiDelete(`${API_BASE_URL}${API_PREFIX}/payment-links/${paymentLinkId}`);
        return response;
    } catch (error) {
        console.error('Error in deletePaymentLink:', error);
        return {
            success: false,
            error: error.message || 'Failed to delete payment link'
        };
    }
};

/**
 * Bulk delete payment links
 * @param {Array} paymentLinkIds - Array of payment link IDs
 * @returns {Promise} - API response
 */
export const bulkDeletePaymentLinks = async (paymentLinkIds) => {
    try {
        const response = await apiPost(`${API_BASE_URL}${API_PREFIX}/payment-links/bulk-delete`, {
            ids: paymentLinkIds
        });
        return response;
    } catch (error) {
        console.error('Error in bulkDeletePaymentLinks:', error);
        return {
            success: false,
            error: error.message || 'Failed to delete payment links'
        };
    }
};

/**
 * Update payment link scheduled date
 * @param {string} paymentLinkId - Payment link ID
 * @param {string} scheduledDate - New scheduled date
 * @returns {Promise} - API response
 */
export const updatePaymentLinkDate = async (paymentLinkId, scheduledDate) => {
    try {
        const response = await apiPost(`${API_BASE_URL}${API_PREFIX}/payment-links/${paymentLinkId}/update-date`, {
            scheduled_date: scheduledDate
        });
        return response;
    } catch (error) {
        console.error('Error in updatePaymentLinkDate:', error);
        return {
            success: false,
            error: error.message || 'Failed to update payment link date'
        };
    }
};

/**
 * Send payment link via email/WhatsApp/SMS
 * @param {string} paymentLinkId - Payment link ID
 * @param {Object} sendOptions - Send options {send_email, send_whatsapp, send_sms}
 * @returns {Promise} - API response
 */
export const sendPaymentLink = async (paymentLinkId, sendOptions) => {
    try {
        const response = await apiPost(`${API_BASE_URL}${API_PREFIX}/payment-links/${paymentLinkId}/send`, sendOptions);
        return response;
    } catch (error) {
        console.error('Error in sendPaymentLink:', error);
        return {
            success: false,
            error: error.message || 'Failed to send payment link'
        };
    }
};

/**
 * Get payment link statistics
 * @returns {Promise} - API response
 */
export const getPaymentLinkStatistics = async () => {
    try {
        const response = await apiGet(`${API_BASE_URL}${API_PREFIX}/payment-links/statistics`);
        return response;
    } catch (error) {
        console.error('Error in getPaymentLinkStatistics:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch payment link statistics'
        };
    }
};

/**
 * Export payment links
 * @param {Object} filters - Filter parameters
 * @returns {Promise} - Download file
 */
export const exportPaymentLinks = async (filters = {}) => {
    try {
        const token = document.getElementById('merchant-app-root')?.dataset?.apiToken;
        
        const queryParams = new URLSearchParams(filters).toString();
        const url = `${API_BASE_URL}${API_PREFIX}/payment-links/export${queryParams ? '?' + queryParams : ''}`;
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
            }
        });

        if (response.ok) {
            const blob = await response.blob();
            const downloadUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = downloadUrl;
            a.download = `payment_links_export_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(downloadUrl);
            document.body.removeChild(a);
            
            return { success: true };
        } else {
            return { success: false, error: 'Failed to export data' };
        }
    } catch (error) {
        console.error('Error in exportPaymentLinks:', error);
        return {
            success: false,
            error: error.message || 'Failed to export payment links'
        };
    }
};

