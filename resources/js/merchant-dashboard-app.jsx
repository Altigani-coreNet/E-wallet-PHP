/**
 * Merchant Dashboard React Application Entry Point
 * 
 * This file initializes the React Merchant Dashboard component.
 * It looks for a root element with id 'merchant-dashboard-root'
 * and mounts the MerchantDashboard component with configuration.
 */

import React from 'react';
import ReactDOM from 'react-dom/client';
import MerchantDashboard from './components/merchant/MerchantDashboard';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

// Initialize the merchant dashboard
document.addEventListener('DOMContentLoaded', () => {
    const rootElement = document.getElementById('merchant-dashboard-root');
    
    if (rootElement) {
        // Get configuration from data attributes or window object
        const merchantId = rootElement.getAttribute('data-merchant-id') || 
                          window.merchantAppConfig?.merchantId ||
                          null;
                          
        const apiToken = rootElement.getAttribute('data-api-token') || 
                        window.merchantAppConfig?.apiToken ||
                        localStorage.getItem('jwt_token');
        
        // Log configuration for debugging (remove in production)
        console.log('Merchant Dashboard Initializing...', {
            merchantId,
            hasToken: !!apiToken,
            apiBaseUrl: window.merchantAppConfig?.apiBaseUrl || '/api/v2/merchant',
            rootElement: rootElement
        });
        
        // Create React root and render dashboard
        const root = ReactDOM.createRoot(rootElement);
        
        root.render(
            <React.StrictMode>
                <MerchantDashboard merchantId={merchantId} />
                <ToastContainer 
                    position="top-right"
                    autoClose={3000}
                    hideProgressBar={false}
                    newestOnTop={false}
                    closeOnClick
                    rtl={false}
                    pauseOnFocusLoss
                    draggable
                    pauseOnHover
                    theme="light"
                />
            </React.StrictMode>
        );
        
        console.log('Merchant Dashboard rendered successfully');
    } else {
        console.warn('Merchant Dashboard root element not found. Looking for #merchant-dashboard-root');
    }
});

// Export for potential use in other modules
export default MerchantDashboard;

