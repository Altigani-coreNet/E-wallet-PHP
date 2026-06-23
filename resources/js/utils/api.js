import axios from 'axios';
import { POS_API_V_2 } from './constants';

/**
 * Store authentication token in localStorage
 * @param {string} token - The authentication token
 */
export const setToken = (token) => {
    if (token) {
        localStorage.setItem('sales_api_token', token);
    }
};

/**
 * Get authentication token from localStorage
 * @returns {string|null} The stored token or null
 */
export const getToken = () => {
    return localStorage.getItem('sales_api_token');
};

/**
 * Remove authentication token from localStorage
 */
export const removeToken = () => {
    localStorage.removeItem('sales_api_token');
};

/**
 * Get default headers with authentication
 * @returns {object} Headers object
 */
const getHeaders = () => {
    const token = getToken();
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    return headers;
};

/**
 * Make a GET request
 * @param {string} url - The API endpoint URL
 * @param {object} config - Additional axios config
 * @returns {Promise} Axios response promise
 */
export const get = async (url, config = {}) => {
    try {
        const response = await axios.get(url, {
            ...config,
            headers: {
                ...getHeaders(),
                ...(config.headers || {})
            }
        });
        return response;
    } catch (error) {
        handleError(error);
        throw error;
    }
};

/**
 * Make a POST request
 * @param {string} url - The API endpoint URL
 * @param {object} data - The request body data
 * @param {object} config - Additional axios config
 * @returns {Promise} Axios response promise
 */
export const post = async (url, data = {}, config = {}) => {
    try {
        const response = await axios.post(url, data, {
            ...config,
            headers: {
                ...getHeaders(),
                ...(config.headers || {})
            }
        });
        return response;
    } catch (error) {
        handleError(error);
        throw error;
    }
};

/**
 * Make a PUT request
 * @param {string} url - The API endpoint URL
 * @param {object} data - The request body data
 * @param {object} config - Additional axios config
 * @returns {Promise} Axios response promise
 */
export const put = async (url, data = {}, config = {}) => {
    try {
        const response = await axios.put(url, data, {
            ...config,
            headers: {
                ...getHeaders(),
                ...(config.headers || {})
            }
        });
        return response;
    } catch (error) {
        handleError(error);
        throw error;
    }
};

/**
 * Make a DELETE request
 * @param {string} url - The API endpoint URL
 * @param {object} config - Additional axios config
 * @returns {Promise} Axios response promise
 */
export const del = async (url, config = {}) => {
    try {
        const response = await axios.delete(url, {
            ...config,
            headers: {
                ...getHeaders(),
                ...(config.headers || {})
            }
        });
        return response;
    } catch (error) {
        handleError(error);
        throw error;
    }
};

/**
 * Make a PATCH request
 * @param {string} url - The API endpoint URL
 * @param {object} data - The request body data
 * @param {object} config - Additional axios config
 * @returns {Promise} Axios response promise
 */
export const patch = async (url, data = {}, config = {}) => {
    try {
        const response = await axios.patch(url, data, {
            ...config,
            headers: {
                ...getHeaders(),
                ...(config.headers || {})
            }
        });
        return response;
    } catch (error) {
        handleError(error);
        throw error;
    }
};

/**
 * Upload a file using multipart/form-data
 * @param {string} url - The API endpoint URL
 * @param {FormData} formData - FormData object with file and other data
 * @param {object} config - Additional axios config
 * @returns {Promise} Axios response promise
 */
export const uploadFile = async (url, formData, config = {}) => {
    try {
        const token = getToken();
        const response = await axios.post(url, formData, {
            ...config,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Authorization': token ? `Bearer ${token}` : undefined,
                'Content-Type': 'multipart/form-data',
                ...(config.headers || {})
            }
        });
        return response;
    } catch (error) {
        handleError(error);
        throw error;
    }
};

/**
 * Handle API errors
 * @param {Error} error - The error object
 */
const handleError = (error) => {
    if (error.response) {
        // Server responded with error status
        console.error('API Error:', error.response.status, error.response.data);
        
        // Handle unauthorized errors
        if (error.response.status === 401) {
            console.warn('Unauthorized - Token may be invalid or expired');
            // You can dispatch an event or redirect to login here
            // window.dispatchEvent(new CustomEvent('unauthorized'));
        }
    } else if (error.request) {
        // Request made but no response
        console.error('Network Error: No response received', error.request);
    } else {
        // Error in request setup
        console.error('Request Error:', error.message);
    }
};

// Export as default object for convenience
export default {
    get,
    post,
    put,
    del,
    patch,
    uploadFile,
    setToken,
    getToken,
    removeToken
};

