import { apiGet, apiPost, apiPut, apiDelete } from '../utils/apiUtils';

// Base URL for AuthService API - update this with your AuthService URL
export const AUTH_SERVICE_BASE_URL = import.meta.env.VITE_AUTH_SERVICE_URL || 'http://localhost:8000';

/**
 * Get all users with pagination and filters
 * @param {Object} params - Query parameters (page, per_page, search, status, sort_by, sort_direction)
 * @returns {Promise} - API response
 */
export const getUsers = async (params = {}) => {
    try {
        const queryParams = {
            page: params.page || 1,
            per_page: params.per_page || 10,
            ...params
        };

        // Debug: Log the token being used
        const token = document.getElementById('users-app')?.dataset?.token;
        console.log('🔑 Using access token:', token ? 'Token found (' + token.substring(0, 20) + '...)' : 'No token found');
        console.log('📡 API URL:', `${AUTH_SERVICE_BASE_URL}/api/softpos/users`);

        const response = await apiGet(`${AUTH_SERVICE_BASE_URL}/api/softpos/users`, queryParams);
        return response;
    } catch (error) {
        console.error('Error in getUsers:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch users'
        };
    }
};

/**
 * Get a single user by ID
 * @param {string} userId - User ID
 * @returns {Promise} - API response
 */
export const getUser = async (userId) => {
    try {
        const response = await apiGet(`${AUTH_SERVICE_BASE_URL}/api/softpos/users/${userId}`);
        return response;
    } catch (error) {
        console.error('Error in getUser:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch user'
        };
    }
};

/**
 * Create a new user
 * @param {Object} userData - User data to create
 * @returns {Promise} - API response
 */
export const createUser = async (userData) => {
    try {
        const response = await apiPost(`${AUTH_SERVICE_BASE_URL}/api/softpos/users`, userData);
        return response;
    } catch (error) {
        console.error('Error in createUser:', error);
        return {
            success: false,
            error: error.message || 'Failed to create user'
        };
    }
};

/**
 * Update an existing user
 * @param {string} userId - User ID
 * @param {Object} userData - Updated user data
 * @returns {Promise} - API response
 */
export const updateUser = async (userId, userData) => {
    try {
        const response = await apiPut(`${AUTH_SERVICE_BASE_URL}/api/softpos/users/${userId}`, userData);
        return response;
    } catch (error) {
        console.error('Error in updateUser:', error);
        return {
            success: false,
            error: error.message || 'Failed to update user'
        };
    }
};

/**
 * Delete a user
 * @param {string} userId - User ID
 * @returns {Promise} - API response
 */
export const deleteUser = async (userId) => {
    try {
        const response = await apiDelete(`${AUTH_SERVICE_BASE_URL}/api/softpos/users/${userId}`);
        return response;
    } catch (error) {
        console.error('Error in deleteUser:', error);
        return {
            success: false,
            error: error.message || 'Failed to delete user'
        };
    }
};

/**
 * Get users for select dropdown
 * @param {string} search - Search term
 * @returns {Promise} - API response
 */
export const getUsersForSelect = async (search = '') => {
    try {
        const response = await apiGet(`${AUTH_SERVICE_BASE_URL}/api/softpos/users/select/dropdown`, { search });
        return response;
    } catch (error) {
        console.error('Error in getUsersForSelect:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch users for select'
        };
    }
};

/**
 * Change user status
 * @param {string} userId - User ID
 * @param {number} status - Status (0 or 1)
 * @returns {Promise} - API response
 */
export const changeUserStatus = async (userId, status) => {
    try {
        const response = await apiPut(`${AUTH_SERVICE_BASE_URL}/api/softpos/users/${userId}`, { status });
        return response;
    } catch (error) {
        console.error('Error in changeUserStatus:', error);
        return {
            success: false,
            error: error.message || 'Failed to change user status'
        };
    }
};



// export AUTH_SERVICE_BASE_URL

