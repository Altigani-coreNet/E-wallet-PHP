import { apiGet } from '../utils/apiUtils';

// Base URL for AuthService API
const AUTH_SERVICE_BASE_URL = import.meta.env.VITE_AUTH_SERVICE_URL || 'http://localhost:8000';

/**
 * Get contract terms and merchant information
 * @returns {Promise} - API response
 */
export const getContractTerms = async () => {
    try {
        const response = await apiGet(`${AUTH_SERVICE_BASE_URL}/api/softpos/contracts`);
        return response;
    } catch (error) {
        console.error('Error in getContractTerms:', error);
        return {
            success: false,
            error: error.message || 'Failed to fetch contract terms'
        };
    }
};

/**
 * Download contract PDF
 * @returns {Promise} - Download file
 */
export const downloadContractPDF = async () => {
    try {
        const token = document.getElementById('merchant-app-root')?.dataset?.apiToken;
        
        const response = await fetch(`${AUTH_SERVICE_BASE_URL}/api/softpos/contracts/download`, {
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
            a.download = `merchant_agreement_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            return { success: true };
        } else {
            return { success: false, error: 'Failed to download contract' };
        }
    } catch (error) {
        console.error('Error in downloadContractPDF:', error);
        return {
            success: false,
            error: error.message || 'Failed to download contract'
        };
    }
};

