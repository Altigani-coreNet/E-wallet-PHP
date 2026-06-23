import { apiGet, apiPost, apiPut, apiDelete } from '../utils/apiUtils';

// Base URL for AuthService API - update this with your AuthService URL
const AUTH_SERVICE_BASE_URL = import.meta.env.VITE_AUTH_SERVICE_URL || 'http://localhost:8000';

/**
 * Get all branches with pagination and filters
 * @param {Object} params - Query parameters (page, per_page, search, status, date_from, date_to)
 * @returns {Promise} - API response
 */
export const getBranches = async (params = {}) => {
    try {
        const queryParams = {
            page: params.page || 1,
            per_page: params.per_page || 15,
            ...params
        };

        const response = await apiGet(`${AUTH_SERVICE_BASE_URL}/api/softpos/branches`, queryParams);
        return response;
    } catch (error) {
        console.error('Error in getBranches:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch branches'
        };
    }
};

/**
 * Get a single branch by ID
 * @param {string} branchId - Branch ID
 * @returns {Promise} - API response
 */
export const getBranch = async (branchId) => {
    try {
        const response = await apiGet(`${AUTH_SERVICE_BASE_URL}/api/softpos/branches/${branchId}`);
        return response;
    } catch (error) {
        console.error('Error in getBranch:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch branch'
        };
    }
};

/**
 * Create a new branch
 * @param {Object} branchData - Branch data to create
 * @returns {Promise} - API response
 */
export const createBranch = async (branchData) => {
    try {
        const response = await apiPost(`${AUTH_SERVICE_BASE_URL}/api/softpos/branches`, branchData);
        return response;
    } catch (error) {
        console.error('Error in createBranch:', error);
        return {
            success: false,
            error: error.message || 'Failed to create branch'
        };
    }
};

/**
 * Update an existing branch
 * @param {string} branchId - Branch ID
 * @param {Object} branchData - Updated branch data
 * @returns {Promise} - API response
 */
export const updateBranch = async (branchId, branchData) => {
    try {
        const response = await apiPut(`${AUTH_SERVICE_BASE_URL}/api/softpos/branches/${branchId}`, branchData);
        return response;
    } catch (error) {
        console.error('Error in updateBranch:', error);
        return {
            success: false,
            error: error.message || 'Failed to update branch'
        };
    }
};

/**
 * Delete a branch
 * @param {string} branchId - Branch ID
 * @returns {Promise} - API response
 */
export const deleteBranch = async (branchId) => {
    try {
        const response = await apiDelete(`${AUTH_SERVICE_BASE_URL}/api/softpos/branches/${branchId}`);
        return response;
    } catch (error) {
        console.error('Error in deleteBranch:', error);
        return {
            success: false,
            error: error.message || 'Failed to delete branch'
        };
    }
};

/**
 * Bulk delete branches
 * @param {Array} branchIds - Array of branch IDs
 * @returns {Promise} - API response
 */
export const bulkDeleteBranches = async (branchIds) => {
    try {
        const response = await apiDelete(`${AUTH_SERVICE_BASE_URL}/api/softpos/branches/bulk-delete`, {
            ids: branchIds
        });
        return response;
    } catch (error) {
        console.error('Error in bulkDeleteBranches:', error);
        return {
            success: false,
            error: error.message || 'Failed to delete branches'
        };
    }
};

/**
 * Get branches for select dropdown
 * @param {string} search - Search query
 * @returns {Promise} - API response
 */
export const getBranchesForSelect = async (search = '') => {
    try {
        const response = await apiGet(`${AUTH_SERVICE_BASE_URL}/api/softpos/branches/select`, { search });
        return response;
    } catch (error) {
        console.error('Error in getBranchesForSelect:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch branches'
        };
    }
};

/**
 * Get branches by IDs (for displaying in terminals list)
 * @param {Array} branchIds - Array of branch IDs
 * @returns {Promise} - API response with branch details
 */
export const getBranchesByIds = async (branchIds = []) => {
    try {
        if (!branchIds || branchIds.length === 0) {
            return {
                success: true,
                data: []
            };
        }

        // Filter out null/undefined values and get unique IDs
        const uniqueIds = [...new Set(branchIds.filter(id => id != null))];
        
        if (uniqueIds.length === 0) {
            return {
                success: true,
                data: []
            };
        }

        const response = await apiPost(`${AUTH_SERVICE_BASE_URL}/api/softpos/branches/by-ids`, {
            ids: uniqueIds
        });
        return response;
    } catch (error) {
        console.error('Error in getBranchesByIds:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch branches',
            data: []
        };
    }
};

/**
 * Import branches from file
 * @param {File} file - File to import
 * @returns {Promise} - API response
 */
export const importBranches = async (file) => {
    try {
        const formData = new FormData();
        formData.append('import_file', file);

        const token = document.getElementById('merchant-app-root')?.dataset?.apiToken;
        
        const response = await fetch(`${AUTH_SERVICE_BASE_URL}/api/softpos/branches/import`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
            },
            body: formData
        });

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error in importBranches:', error);
        return {
            success: false,
            error: error.message || 'Failed to import branches'
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
        formData.append('file', file);

        const token = document.getElementById('merchant-app-root')?.dataset?.apiToken;
        
        const response = await fetch(`${AUTH_SERVICE_BASE_URL}/api/softpos/branches/import-preview`, {
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
 * Export branches template
 * @returns {Promise} - Download file
 */
export const exportTemplate = async () => {
    try {
        const token = document.getElementById('merchant-app-root')?.dataset?.apiToken;
        
        const response = await fetch(`${AUTH_SERVICE_BASE_URL}/api/softpos/branches/export-template`, {
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
            a.download = 'branches_import_template.csv';
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
 * Export filtered branches
 * @param {Object} filters - Filter parameters
 * @returns {Promise} - Download file
 */
export const exportBranches = async (filters = {}) => {
    try {
        const token = document.getElementById('merchant-app-root')?.dataset?.apiToken;
        
        const queryParams = new URLSearchParams(filters).toString();
        const url = `${AUTH_SERVICE_BASE_URL}/api/softpos/branches/export${queryParams ? '?' + queryParams : ''}`;
        
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
            a.download = `branches_export_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(downloadUrl);
            document.body.removeChild(a);
            
            return { success: true };
        } else {
            return { success: false, error: 'Failed to export data' };
        }
    } catch (error) {
        console.error('Error in exportBranches:', error);
        return {
            success: false,
            error: error.message || 'Failed to export branches'
        };
    }
};


