/**
 * AuthService API Client
 * Handles all API calls to the AuthService backend
 */

export const AUTH_SERVICE_BASE_URL = 'http://localhost:8000/api/softpos'; // Update this to your AuthService URL

// Get CSRF token from meta tag
const getCsrfToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]');
    return token ? token.getAttribute('content') : '';
};

// Get auth token from localStorage or session
const getAuthToken = () => {
    return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token') || '';
};

// Base fetch with authentication
const authenticatedFetch = async (url, options = {}) => {
    const headers = {
        'Accept': 'application/json',
        // 'X-CSRF-TOKEN': getCsrfToken(),
        ...options.headers,
    };

    // Add Authorization header if token exists
    const token = getAuthToken();
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    // Don't add Content-Type for FormData (browser will set it automatically with boundary)
    if (!(options.body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, {
        ...options,
        headers,
        credentials: 'include',
    });

    // Check if response is ok
    if (!response.ok) {
        const error = await response.json().catch(() => ({ message: 'Network error' }));
        throw new Error(error.message || `HTTP error! status: ${response.status}`);
    }

    return response.json();
};

/**
 * Profile API
 */
export const profileAPI = {
    /**
     * Get user info with merchant data and profile completion
     */
    getUserInfo: async () => {
        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/profile/me`, {
            method: 'GET',
        });
    },

    /**
     * Get current user profile (alias for getUserInfo)
     */
    getMe: async () => {
        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/profile/me`, {
            method: 'GET',
        });
    },

    /**
     * Get profile completion percentage
     */
    getProfileCompletion: async () => {
        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/profile/completion`, {
            method: 'GET',
        });
    },

    /**
     * Update user profile
     */
    updateProfile: async (data) => {
        const formData = new FormData();
        
        Object.keys(data).forEach(key => {
            if (data[key] !== null && data[key] !== undefined) {
                if (key === 'profile_image' && data[key] instanceof File) {
                    formData.append(key, data[key]);
                } else {
                    formData.append(key, data[key]);
                }
            }
        });

        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/profile/update`, {
            method: 'POST',
            body: formData,
        });
    },

    /**
     * Change password
     */
    changePassword: async (data) => {
        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/profile/change-password`, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    },

    /**
     * Upload profile image
     */
    uploadProfileImage: async (file) => {
        const formData = new FormData();
        formData.append('profile_image', file);

        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/profile/upload-image`, {
            method: 'POST',
            body: formData,
        });
    },

    /**
     * Delete profile image
     */
    deleteProfileImage: async () => {
        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/profile/delete-image`, {
            method: 'DELETE',
        });
    },
};

/**
 * Authentication API
 */
export const authAPI = {
    /**
     * Login
     */
    login: async (email, password) => {
        const response = await authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/login`, {
            method: 'POST',
            body: JSON.stringify({ email, password }),
        });

        // Store token if returned
        if (response.data && response.data.token) {
            localStorage.setItem('auth_token', response.data.token);
        }

        return response;
    },

    /**
     * Logout
     */
    logout: async () => {
        const response = await authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/logout`, {
            method: 'POST',
        });

        // Clear token
        localStorage.removeItem('auth_token');
        sessionStorage.removeItem('auth_token');

        return response;
    },

    /**
     * Get current user
     */
    getCurrentUser: async () => {
        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/profile`, {
            method: 'GET',
        });
    },
};

/**
 * User Management API
 */
export const userAPI = {
    /**
     * Get all users
     */
    getUsers: async (params = {}) => {
        const queryString = new URLSearchParams(params).toString();
        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/users?${queryString}`, {
            method: 'GET',
        });
    },

    /**
     * Get user by ID
     */
    getUser: async (id) => {
        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/users/${id}`, {
            method: 'GET',
        });
    },

    /**
     * Create user
     */
    createUser: async (data) => {
        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/users`, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    },

    /**
     * Update user
     */
    updateUser: async (id, data) => {
        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/users/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    },

    /**
     * Delete user
     */
    deleteUser: async (id) => {
        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/users/${id}`, {
            method: 'DELETE',
        });
    },
};

/**
 * Role Management API
 */
export const roleAPI = {
    /**
     * Get all roles
     */
    getRoles: async (params = {}) => {
        const queryString = new URLSearchParams(params).toString();
        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/roles?${queryString}`, {
            method: 'GET',
        });
    },

    /**
     * Get role by ID
     */
    getRole: async (id) => {
        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/roles/${id}`, {
            method: 'GET',
        });
    },

    /**
     * Create role
     */
    createRole: async (data) => {
        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/roles`, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    },

    /**
     * Update role
     */
    updateRole: async (id, data) => {
        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/roles/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    },

    /**
     * Delete role
     */
    deleteRole: async (id) => {
        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/roles/${id}`, {
            method: 'DELETE',
        });
    },

    /**
     * Get all permissions
     */
    getPermissions: async () => {
        return authenticatedFetch(`${AUTH_SERVICE_BASE_URL}/permissions`, {
            method: 'GET',
        });
    },
};

export default {
    profileAPI,
    authAPI,
    userAPI,
    roleAPI,
};

