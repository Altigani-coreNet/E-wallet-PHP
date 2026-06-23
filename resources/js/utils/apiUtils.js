import axios from 'axios';
import { getToken as getApiToken } from './api';

// Get token from DOM element or fallback to api.js
const getToken = () => {
    // Try to get token from users-app, roles-app, or pos-app
    return document.getElementById('users-app')?.dataset?.token || 
           document.getElementById('user-create-app')?.dataset?.token || 
           document.getElementById('user-edit-app')?.dataset?.token || 
           document.getElementById('roles-app')?.dataset?.token || 
           document.getElementById('pos-app')?.dataset?.token || 
           document.getElementById('sales-app')?.dataset?.token || 
           getApiToken() ||
           '';
};

// API utility function
export const apiRequest = async (url, method = 'GET', data = null, params = null, customHeaders = {}) => {
    try {
        const token = getToken();
        
        const config = {
            url,
            method,
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...customHeaders
            }
        };

        // Add data for POST, PUT, PATCH requests
        if (data && ['POST', 'PUT', 'PATCH'].includes(method.toUpperCase())) {
            config.data = data;
        }

        // Add query parameters
        if (params) {
            config.params = params;
        }

        const response = await axios(config);
        
        return {
            success: true,
            data: response.data,
            status: response.status
        };
    } catch (error) {
        console.error('❌ API Request Error:', {
            url: url,
            status: error.response?.status,
            statusText: error.response?.statusText,
            data: error.response?.data,
            message: error.message
        });
        
        // Extract error message and validation errors
        let errorMessage = error.message;
        let validationErrors = null;
        
        if (error.response?.data) {
            // Check for validation errors (422 status)
            if (error.response.status === 422 && error.response.data.data) {
                validationErrors = error.response.data.data;
                errorMessage = validationErrors; // Pass the whole validation object
            } else if (typeof error.response.data === 'string') {
                errorMessage = error.response.data;
            } else if (error.response.data.message) {
                errorMessage = error.response.data.message;
            } else if (error.response.data.error) {
                errorMessage = error.response.data.error;
            } else if (error.response.data.data && typeof error.response.data.data === 'object') {
                // Laravel validation errors format
                validationErrors = error.response.data.data;
                errorMessage = validationErrors;
            }
        }
        
        return {
            success: false,
            error: errorMessage,
            details: error.response?.data,
            validationErrors: validationErrors,
            status: error.response?.status || 500
        };
    }
};


// Specific API methods for convenience
export const apiGet = (url, params = null, headers = {}) => apiRequest(url, 'GET', null, params, headers);
export const apiPost = (url, data, params = null, headers = {}) => apiRequest(url, 'POST', data, params, headers);
export const apiPut = (url, data, params = null, headers = {}) => apiRequest(url, 'PUT', data, params, headers);
export const apiDelete = (url, params = null, headers = {}) => apiRequest(url, 'DELETE', null, params, headers);

