import { apiGet, apiPost, apiPut, apiDelete } from '../utils/apiUtils';

// Base URL for AuthService API - update this with your AuthService URL
const AUTH_SERVICE_BASE_URL = import.meta.env.VITE_AUTH_SERVICE_URL || 'http://localhost:8000';
const SOFTPOS_SERVICE_BASE_URL = import.meta.env.VITE_SOFTPOS_SERVICE_URL || 'http://localhost:8001';
/**
 * Get all terminals with pagination and filters
 * @param {Object} params - Query parameters (page, per_page, search, status, date_from, date_to)
 * @returns {Promise} - API response
 */
export const getTerminals = async (params = {}) => {
    try {
        const queryParams = {
            page: params.page || 1,
            per_page: params.per_page || 15,
            ...params
        };

        const response = await apiGet(`${SOFTPOS_SERVICE_BASE_URL}/api/v1/merchant/terminals`, queryParams);
        return response;
    } catch (error) {
        console.error('Error in getTerminals:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch terminals'
        };
    }
};

/**
 * Get a single terminal by ID
 * @param {string} terminalId - Terminal ID
 * @returns {Promise} - API response
 */
export const getTerminal = async (terminalId) => {
    try {
        const response = await apiGet(`${SOFTPOS_SERVICE_BASE_URL}/api/v1/merchant/terminals/${terminalId}`);
        return response;
    } catch (error) {
        console.error('Error in getTerminal:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch terminal'
        };
    }
};

/**
 * Create a new terminal
 * @param {Object} terminalData - Terminal data to create
 * @returns {Promise} - API response
 */
export const createTerminal = async (terminalData) => {
    try {
        const response = await apiPost(`${SOFTPOS_SERVICE_BASE_URL}/api/v1/merchant/terminals`, terminalData);
        return response;
    } catch (error) {
        console.error('Error in createTerminal:', error);
        return {
            success: false,
            error: error.message || 'Failed to create terminal'
        };
    }
};

/**
 * Update an existing terminal
 * @param {string} terminalId - Terminal ID
 * @param {Object} terminalData - Updated terminal data
 * @returns {Promise} - API response
 */
export const updateTerminal = async (terminalId, terminalData) => {
    try {
        const response = await apiPut(`${SOFTPOS_SERVICE_BASE_URL}/api/v1/merchant/terminals/${terminalId}`, terminalData);
        return response;
    } catch (error) {
        console.error('Error in updateTerminal:', error);
        return {
            success: false,
            error: error.message || 'Failed to update terminal'
        };
    }
};

/**
 * Delete a terminal
 * @param {string} terminalId - Terminal ID
 * @returns {Promise} - API response
 */
export const deleteTerminal = async (terminalId) => {
    try {
        const response = await apiDelete(`${SOFTPOS_SERVICE_BASE_URL}/api/v1/merchant/terminals/${terminalId}`);
        return response;
    } catch (error) {
        console.error('Error in deleteTerminal:', error);
        return {
            success: false,
            error: error.message || 'Failed to delete terminal'
        };
    }
};

/**
 * Bulk delete terminals
 * @param {Array} terminalIds - Array of terminal IDs
 * @returns {Promise} - API response
 */
export const bulkDeleteTerminals = async (terminalIds) => {
    try {
        const response = await apiPost(`${SOFTPOS_SERVICE_BASE_URL}/api/v1/merchant/terminals/bulk-delete`, {
            ids: terminalIds
        });
        return response;
    } catch (error) {
        console.error('Error in bulkDeleteTerminals:', error);
        return {
            success: false,
            error: error.message || 'Failed to delete terminals'
        };
    }
};

/**
 * Get terminals for select dropdown
 * @param {string} search - Search query
 * @returns {Promise} - API response
 */
export const getTerminalsForSelect = async (search = '') => {
    try {
        const response = await apiGet(`${SOFTPOS_SERVICE_BASE_URL}/api/v1/merchant/terminals/select`, { search });
        return response;
    } catch (error) {
        console.error('Error in getTerminalsForSelect:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch terminals'
        };
    }
};

/**
 * Get terminals by branch ID
 * @param {string} branchId - Branch ID
 * @returns {Promise} - API response
 */
export const getTerminalsByBranch = async (branchId) => {
    try {
        const response = await apiGet(`${SOFTPOS_SERVICE_BASE_URL}/api/v1/merchant/terminals/by-branch`, { branch_id: branchId });
        return response;
    } catch (error) {
        console.error('Error in getTerminalsByBranch:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch terminals'
        };
    }
};

/**
 * Import terminals from file
 * @param {File} file - File to import
 * @returns {Promise} - API response
 */
export const importTerminals = async (file) => {
    try {
        const formData = new FormData();
        formData.append('import_file', file);

        const token = document.getElementById('merchant-app-root')?.dataset?.apiToken;
        
        const response = await fetch(`${SOFTPOS_SERVICE_BASE_URL}/api/v1/merchant/terminals/import`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
            },
            body: formData
        });

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error in importTerminals:', error);
        return {
            success: false,
            error: error.message || 'Failed to import terminals'
        };
    }
};

/**
 * Preview import data before importing
 * @param {File} file - File to preview
 * @returns {Promise} - API response
 */
export const previewImport = async (file) => {
    try {
        const formData = new FormData();
        formData.append('import_file', file);

        const token = document.getElementById('merchant-app-root')?.dataset?.apiToken;
        
        const response = await fetch(`${SOFTPOS_SERVICE_BASE_URL}/api/v1/merchant/terminals/import-preview`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
            },
            body: formData
        });

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error in previewImport:', error);
        return {
            success: false,
            error: error.message || 'Failed to preview import'
        };
    }
};

/**
 * Export terminals template
 * @returns {Promise} - Download file
 */
export const exportTemplate = async () => {
    try {
        const token = document.getElementById('merchant-app-root')?.dataset?.apiToken;
        
        const response = await fetch(`${SOFTPOS_SERVICE_BASE_URL}/api/v1/merchant/terminals/export-template`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
            }
        });

        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'terminals_import_template.csv';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            return { success: true };
        } else {
            return { success: false, error: 'Failed to download template' };
        }
    } catch (error) {
        console.error('Error in exportTemplate:', error);
        return {
            success: false,
            error: error.message || 'Failed to export template'
        };
    }
};

/**
 * Export filtered terminals
 * @param {Object} filters - Filter parameters
 * @returns {Promise} - Download file
 */
export const exportTerminals = async (filters = {}) => {
    try {
        const token = document.getElementById('merchant-app-root')?.dataset?.apiToken;
        
        const queryParams = new URLSearchParams(filters).toString();
        const url = `${SOFTPOS_SERVICE_BASE_URL}/api/v1/merchant/terminals/export${queryParams ? '?' + queryParams : ''}`;
        
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
            a.download = `terminals_export_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(downloadUrl);
            document.body.removeChild(a);
            
            return { success: true };
        } else {
            return { success: false, error: 'Failed to export data' };
        }
    } catch (error) {
        console.error('Error in exportTerminals:', error);
        return {
            success: false,
            error: error.message || 'Failed to export terminals'
        };
    }
};

