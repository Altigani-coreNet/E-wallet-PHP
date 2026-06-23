// Get the POS API base URL from environment variables
// In your .env file, add: VITE_POS_API_URL=http://localhost:8000
export const POS_API_BASE = import.meta.env.POS_API_URL || 'http://localhost:8002';
export const SOFTPOS_API_BASE = import.meta.env.SOFTPOS_API_URL || 'http://localhost:8001';
export const POS_API_V_2 = `${POS_API_BASE}/api/v1`;

// API Version Constants (for POS system compatibility)
export const API_V_1 = '/api/v1';
export const API_V_2 = '/api/v1';

// Default API version to use
export const DEFAULT_API_VERSION = API_V_2;

// API endpoints
export const API_ENDPOINTS = {
    DASHBOARD: {
        STATISTICS: `${POS_API_V_2}/api/v1/dashboard/statistics`,
        LATEST_SALES: `${POS_API_V_2}/api/v1/dashboard/latest-sales`,
        LATEST_PURCHASES: `${POS_API_V_2}/api/v1/dashboard/latest-purchases`,
        SALES_CHART: `${POS_API_V_2}/api/dashboard/v1/sales-chart`,
        PURCHASES_CHART: `${POS_API_V_2}/api/v1/dashboard/purchases-chart`,
    },
    CUSTOMERS: {
        LIST: `${POS_API_V_2}/customers`,
        SEARCH: `${POS_API_V_2}/customer/search`,
        DETAILS: (id) => `${POS_API_V_2}/customers/${id}`,
        CREATE: `${POS_API_V_2}/customers`,
        UPDATE: (id) => `${POS_API_V_2}/customers/${id}`,
        DELETE: (id) => `${POS_API_V_2}/customers/${id}`,
        GROUPS: `${POS_API_V_2}/customer/groups`,
        EXPORT: `${POS_API_V_2}/customers/export`,
        EXPORT_TEMPLATE: `${POS_API_V_2}/customers/export-template`,
        IMPORT_PREVIEW: `${POS_API_V_2}/customers/import-preview`,
        IMPORT: `${POS_API_V_2}/customers/import`,
    },
    PRODUCTS: {
        LIST: `${POS_API_BASE}/api/v1/products`,
        DETAILS: (id) => `${POS_API_BASE}/api/v1/products/${id}`,
        CREATE: `${POS_API_BASE}/api/v1/products`,
        UPDATE: (id) => `${POS_API_BASE}/api/v1/products/${id}`,
        DELETE: (id) => `${POS_API_BASE}/api/v1/products/${id}`,
        SELECT_OPTIONS: `${POS_API_BASE}/api/v1/products/select-options`,
        UPLOAD: `${POS_API_BASE}/api/v1/products/upload`,
        EXPORT: `${POS_API_BASE}/api/v1/products/export`,
        EXPORT_TEMPLATE: `${POS_API_BASE}/api/v1/products/export-template`,
        IMPORT: `${POS_API_BASE}/api/v1/products/import`,
        IMPORT_PREVIEW: `${POS_API_BASE}/api/v1/products/import-preview`,
    },
    REPORTS: {
        PURCHASES: `${POS_API_V_2}/reports/purchases`,
        PURCHASES_SUMMARY: `${POS_API_V_2}/reports/purchases/summary`,
        SALES: `${POS_API_V_2}/reports/sales`,
        SALES_SUMMARY: `${POS_API_V_2}/reports/sales/summary`,
        PRODUCTS: `${POS_API_V_2}/reports/products`,
        PRODUCTS_SUMMARY: `${POS_API_V_2}/reports/products/summary`,
        EXPENSES: `${POS_API_V_2}/reports/expenses`,
        EXPENSES_SUMMARY: `${POS_API_V_2}/reports/expenses/summary`,
    }
};