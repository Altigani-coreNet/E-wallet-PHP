import { apiGet, apiPost, apiPut, apiDelete } from '../utils/apiUtils';

// Base URL for AuthService API - update this with your AuthService URL
const AUTH_SERVICE_BASE_URL = import.meta.env.VITE_AUTH_SERVICE_URL || 'http://localhost:8000';

/**
 * Get all roles with pagination and filters
 * @param {Object} params - Query parameters (page, per_page, search, type, parent, sort_by, sort_direction)
 * @returns {Promise} - API response
 */
export const getRoles = async (params = {}) => {
    try {
        const queryParams = {
            page: params.page || 1,
            per_page: params.per_page || 10,
            ...params
        };

        // Debug: Log the token being used
        const token = document.getElementById('roles-app')?.dataset?.token;
        console.log('🔑 Using access token:', token ? 'Token found (' + token.substring(0, 20) + '...)' : 'No token found');
        console.log('📡 API URL:', `${AUTH_SERVICE_BASE_URL}/api/softpos/roles`);

        const response = await apiGet(`${AUTH_SERVICE_BASE_URL}/api/softpos/roles`, queryParams);
        return response;
    } catch (error) {
        console.error('Error in getRoles:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch roles'
        };
    }
};

/**
 * Get a single role by ID
 * @param {number} roleId - Role ID
 * @returns {Promise} - API response
 */
export const getRole = async (roleId) => {
    try {
        const response = await apiGet(`${AUTH_SERVICE_BASE_URL}/api/softpos/roles/${roleId}`);
        return response;
    } catch (error) {
        console.error('Error in getRole:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch role'
        };
    }
};

/**
 * Create a new role
 * @param {Object} roleData - Role data to create
 * @returns {Promise} - API response
 */
export const createRole = async (roleData) => {
    try {
        const response = await apiPost(`${AUTH_SERVICE_BASE_URL}/api/softpos/roles`, roleData);
        return response;
    } catch (error) {
        console.error('Error in createRole:', error);
        return {
            success: false,
            error: error.message || 'Failed to create role'
        };
    }
};

/**
 * Update an existing role
 * @param {number} roleId - Role ID
 * @param {Object} roleData - Updated role data
 * @returns {Promise} - API response
 */
export const updateRole = async (roleId, roleData) => {
    try {
        const response = await apiPut(`${AUTH_SERVICE_BASE_URL}/api/softpos/roles/${roleId}`, roleData);
        return response;
    } catch (error) {
        console.error('Error in updateRole:', error);
        return {
            success: false,
            error: error.message || 'Failed to update role'
        };
    }
};

/**
 * Delete a role
 * @param {number} roleId - Role ID
 * @returns {Promise} - API response
 */
export const deleteRole = async (roleId) => {
    try {
        const response = await apiDelete(`${AUTH_SERVICE_BASE_URL}/api/softpos/roles/${roleId}`);
        return response;
    } catch (error) {
        console.error('Error in deleteRole:', error);
        return {
            success: false,
            error: error.message || 'Failed to delete role'
        };
    }
};

/**
 * Assign permissions to a role
 * @param {number} roleId - Role ID
 * @param {Array} permissionIds - Array of permission IDs
 * @returns {Promise} - API response
 */
export const assignPermissionsToRole = async (roleId, permissionIds) => {
    try {
        const response = await apiPost(
            `${AUTH_SERVICE_BASE_URL}/api/softpos/roles/${roleId}/permissions`,
            { permission_ids: permissionIds }
        );
        return response;
    } catch (error) {
        console.error('Error in assignPermissionsToRole:', error);
        return {
            success: false,
            error: error.message || 'Failed to assign permissions'
        };
    }
};

/**
 * Get all permissions (for role creation/editing)
 * @param {string} module - Module name to filter permissions (e.g., 'pos', 'sales', 'merchant' for all)
 * @returns {Promise} - API response
 */
export const getPermissions = async (module = 'merchant') => {
    try {
        // For role creation, always get merchant permissions (both pos and sales)
        // Call API with module in headers
        const response = await apiGet(`${AUTH_SERVICE_BASE_URL}/api/softpos/permissions`, null, {
            'X-Module': module
        });
        return response;
    } catch (error) {
        console.error('Error in getPermissions:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch permissions'
        };
    }
};

/**
 * Get roles by type (e.g., 'shop', 'admin')
 * @param {string} type - Role type
 * @returns {Promise} - API response
 */
export const getRolesByType = async (type) => {
    try {
        const response = await apiGet(`${AUTH_SERVICE_BASE_URL}/api/softpos/roles`, { type });
        return response;
    } catch (error) {
        console.error('Error in getRolesByType:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch roles by type'
        };
    }
};

