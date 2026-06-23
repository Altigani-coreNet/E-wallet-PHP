import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { toast } from 'react-toastify';
import DashboardFilters from './DashboardFilters';
import DashboardStatistics from './DashboardStatistics';
import DashboardCharts from './DashboardCharts';
import DashboardLatestTransactions from './DashboardLatestTransactions';
import { SOFTPOS_API_BASE } from '../../utils/constants';
const MerchantDashboard = ({ merchantId: propMerchantId }) => {
    // Get merchantId from props, window config, or localStorage
    const merchantId = propMerchantId || 
                       window.merchantAppConfig?.merchantId ||
                       document.getElementById('merchant-app-root')?.getAttribute('data-merchant-id') ||
                       JSON.parse(localStorage.getItem('user_data'))?.merchant_id;
    
    // Get API base URL
    const getApiBaseUrl = () => {
        return SOFTPOS_API_BASE + '/api/v2/merchant';
    };
    
    // Get API token from multiple sources
    const getApiToken = () => {
        // Try multiple sources for the token
        const sources = {
            windowConfig: window.merchantAppConfig?.apiToken,
            dataAttribute: document.getElementById('merchant-app-root')?.getAttribute('data-api-token'),
            localStorage: localStorage.getItem('jwt_token'),
            sessionStorage: sessionStorage.getItem('jwt_token'),
            merchantDashboardRoot: document.getElementById('merchant-dashboard-root')?.getAttribute('data-api-token')
        };
        
        // Find first available token
        const token = sources.windowConfig || 
                     sources.merchantDashboardRoot ||
                     sources.dataAttribute || 
                     sources.localStorage || 
                     sources.sessionStorage;
        
        // Log token sources for debugging (only on first load)
        if (!token) {
            console.error('Token not found in any source:', {
                windowConfig: !!sources.windowConfig,
                merchantDashboardRoot: !!sources.merchantDashboardRoot,
                dataAttribute: !!sources.dataAttribute,
                localStorage: !!sources.localStorage,
                sessionStorage: !!sources.sessionStorage
            });
        }
        
        return token;
    };

    // State management
    const [dashboardData, setDashboardData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [filtersCollapsed, setFiltersCollapsed] = useState(
        localStorage.getItem('merchantDashboardFiltersCollapsed') === 'true'
    );
    
    // Filter states
    const [filters, setFilters] = useState({
        datetime_from: '',
        datetime_to: '',
        transaction_status: '',
        limit: 10
    });

    // Configure axios with token
    useEffect(() => {
        const token = getApiToken();
        if (token) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        }
    }, []);

    // Fetch dashboard data
    const fetchDashboardData = useCallback(async () => {
        setLoading(true);
        setError(null);
        
        try {
            const token = getApiToken();
            const baseUrl = getApiBaseUrl();
            
            // Validate token exists
            if (!token) {
                const errorMsg = 'Authentication token not found. Please login again.';
                setError(errorMsg);
                toast.error(errorMsg);
                console.error('JWT token missing. Check localStorage or session.');
                setLoading(false);
                return;
            }
            
            const params = {};
            if (filters.datetime_from) params.datetime_from = filters.datetime_from;
            if (filters.datetime_to) params.datetime_to = filters.datetime_to;
            if (filters.transaction_status) params.transaction_status = filters.transaction_status;
            params.limit = filters.limit;

            console.log('Fetching dashboard data...', {
                url: `${baseUrl}/dashboard`,
                hasToken: !!token,
                tokenPrefix: token ? token.substring(0, 20) + '...' : 'none',
                params
            });

            const response = await axios.get(`${baseUrl}/dashboard`, { 
                params,
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            if (response.data.success) {
                setDashboardData(response.data.data);
                console.log('Dashboard data loaded successfully');
            } else {
                setError(response.data.message || 'Failed to load dashboard data');
                toast.error(response.data.message || 'Failed to load dashboard data');
            }
        } catch (err) {
            console.error('Dashboard API Error:', {
                status: err.response?.status,
                statusText: err.response?.statusText,
                message: err.response?.data?.message || err.message,
                url: err.config?.url,
                hasAuthHeader: !!err.config?.headers?.Authorization
            });
            
            const errorMsg = err.response?.data?.message || err.message || 'Error loading dashboard data';
            
            // Check for specific error cases
            if (err.response?.status === 401) {
                setError('Unauthorized. Please login again.');
                toast.error('Session expired. Please login again.');
            } else {
                setError(errorMsg);
                toast.error(errorMsg);
            }
        } finally {
            setLoading(false);
        }
    }, [filters]);

    // Load data on mount and when filters change
    useEffect(() => {
        fetchDashboardData();
    }, [fetchDashboardData]);

    // Handle filter changes
    const handleFilterChange = (newFilters) => {
        setFilters(prev => ({ ...prev, ...newFilters }));
    };

    // Handle apply filters
    const handleApplyFilters = () => {
        fetchDashboardData();
    };

    // Handle clear filters
    const handleClearFilters = () => {
        setFilters({
            datetime_from: '',
            datetime_to: '',
            transaction_status: '',
            limit: 10
        });
    };

    // Toggle filters panel
    const toggleFilters = () => {
        const newState = !filtersCollapsed;
        setFiltersCollapsed(newState);
        localStorage.setItem('merchantDashboardFiltersCollapsed', newState.toString());
    };

    // Count active filters
    const activeFilterCount = () => {
        let count = 0;
        if (filters.datetime_from) count++;
        if (filters.datetime_to) count++;
        if (filters.transaction_status) count++;
        return count;
    };

    // Handle export to Excel
    const handleExportExcel = () => {
        const params = new URLSearchParams();
        if (filters.datetime_from) params.append('datetime_from', filters.datetime_from);
        if (filters.datetime_to) params.append('datetime_to', filters.datetime_to);
        if (filters.transaction_status) params.append('transaction_status', filters.transaction_status);
        
        const exportUrl = `/merchant/dashboard/export?${params.toString()}`;
        window.location.href = exportUrl;
        toast.success('Exporting dashboard data...');
    };

    // Handle print dashboard
    const handlePrintDashboard = () => {
        window.print();
        toast.info('Print dialog opened');
    };

    if (loading && !dashboardData) {
        return (
            <div className="d-flex align-items-center justify-content-center" style={{ minHeight: '400px' }}>
                <div className="text-center">
                    <span className="spinner-border spinner-border-lg text-primary" role="status"></span>
                    <p className="mt-3 text-muted">Loading dashboard...</p>
                </div>
            </div>
        );
    }

    if (error && !dashboardData) {
        return (
            <div className="alert alert-danger m-5" role="alert">
                <h4 className="alert-heading">Error!</h4>
                <p>{error}</p>
                <hr />
                <button className="btn btn-danger" onClick={fetchDashboardData}>
                    Try Again
                </button>
            </div>
        );
    }

    return (
        <div id="kt_app_content_container" className="app-container container-xxl">
            {/* Toolbar Actions */}
            <div className="d-flex align-items-center gap-2 gap-lg-3 mb-5">
                <button 
                    id="filters_button" 
                    className={`btn btn-sm btn-flex fw-bold ${activeFilterCount() > 0 ? 'btn-primary' : 'btn-secondary'}`}
                    onClick={toggleFilters}
                >
                    <i className={`ki-duotone ki-filter fs-6 me-1`} 
                       style={{ 
                           transform: filtersCollapsed ? 'rotate(90deg)' : 'rotate(0deg)',
                           transition: 'transform 0.3s ease'
                       }}>
                        <span className="path1"></span>
                        <span className="path2"></span>
                    </i>
                    {activeFilterCount() > 0 ? `Filters Active (${activeFilterCount()})` : 'Toggle Filters'}
                </button>

                <button 
                    className="btn btn-sm btn-primary" 
                    onClick={handlePrintDashboard}
                >
                    <i className="ki-duotone ki-printer fs-6 me-2">
                        <span className="path1"></span>
                        <span className="path2"></span>
                        <span className="path3"></span>
                        <span className="path4"></span>
                        <span className="path5"></span>
                    </i>
                    Print Dashboard
                </button>
                
                <button 
                    className="btn btn-sm btn-success" 
                    onClick={handleExportExcel}
                    title="Export dashboard data including charts, transactions, and statistics to Excel"
                >
                    <i className="ki-duotone ki-file-down fs-6 me-2">
                        <span className="path1"></span>
                        <span className="path2"></span>
                    </i>
                    Export to Excel
                </button>
            </div>

            {/* Filters Section */}
            <DashboardFilters
                filters={filters}
                onFilterChange={handleFilterChange}
                onApplyFilters={handleApplyFilters}
                onClearFilters={handleClearFilters}
                isCollapsed={filtersCollapsed}
            />

            {/* Statistics Cards */}
            <DashboardStatistics 
                data={dashboardData}
                loading={loading}
            />

            {/* Transaction Charts */}
            <div className="row gy-5 gx-xl-10">
                <div className="col-xl-12 mb-5 mb-xl-10">
                    <DashboardCharts 
                        data={dashboardData}
                        hasRange={!!(filters.datetime_from || filters.datetime_to)}
                        loading={loading}
                    />
                </div>
            </div>

            {/* Latest Transactions Table */}
            <div className="row gy-5 g-xl-10">
                <div className="col-xl-12">
                    <DashboardLatestTransactions 
                        transactions={dashboardData?.latestTransactions || []}
                        limit={filters.limit}
                        onLimitChange={(newLimit) => handleFilterChange({ limit: newLimit })}
                        loading={loading}
                    />
                </div>
            </div>
        </div>
    );
};

export default MerchantDashboard;

