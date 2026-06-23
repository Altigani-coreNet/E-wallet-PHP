import React, { useState, useEffect } from 'react';
import { API_ENDPOINTS } from '../../utils/constants';
import { get, getToken } from '../../utils/api';

export default function Dashboard() {
    const [statistics, setStatistics] = useState({
        total_sales: '0.00',
        total_expenses: '0.00',
        total_customers: 0,
        total_orders: 0
    });
    const [latestSales, setLatestSales] = useState([]);
    const [latestPurchases, setLatestPurchases] = useState([]);
    const [loading, setLoading] = useState(true);

    // Fetch dashboard data
    useEffect(() => {
        fetchDashboardData();
    }, []);

    const fetchDashboardData = async () => {
        try {
            setLoading(true);

            // Check if token exists
            const token = getToken();
            if (!token) {
                console.warn('No API token found. Please login again.');
                return;
            }

            // Fetch all data in parallel using the API helper
            const [statsRes, salesRes, purchasesRes] = await Promise.all([
                get(API_ENDPOINTS.DASHBOARD.STATISTICS),
                get(`${API_ENDPOINTS.DASHBOARD.LATEST_SALES}?limit=7`),
                get(`${API_ENDPOINTS.DASHBOARD.LATEST_PURCHASES}?limit=7`)
            ]);

            if (statsRes.data.success) {
                setStatistics(statsRes.data.data);
            }

            if (salesRes.data.success) {
                setLatestSales(salesRes.data.data);
            }

            if (purchasesRes.data.success) {
                setLatestPurchases(purchasesRes.data.data);
            }

        } catch (error) {
            console.error('Error fetching dashboard data:', error);
        } finally {
            setLoading(false);
        }
    };

    const getStatusBadge = (statusBadge) => {
        return statusBadge || 'badge-light-secondary';
    };

    // Skeleton Loader Component
    const SkeletonLoader = () => (
        <>
            {/* Toolbar Skeleton */}
            <div id="kt_app_toolbar" className="app-toolbar py-3 py-lg-6">
                <div id="kt_app_toolbar_container" className="app-container container-xxl d-flex flex-stack">
                    <div className="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                        <div className="placeholder-glow">
                            <span className="placeholder col-4 bg-secondary" style={{ height: '28px', borderRadius: '6px' }}></span>
                        </div>
                        <div className="placeholder-glow mt-2">
                            <span className="placeholder col-6 bg-secondary" style={{ height: '16px', borderRadius: '4px' }}></span>
                        </div>
                    </div>
                    <div className="d-flex align-items-center gap-2">
                        <div className="placeholder-glow">
                            <span className="placeholder bg-secondary" style={{ width: '80px', height: '36px', borderRadius: '6px' }}></span>
                        </div>
                        <div className="placeholder-glow">
                            <span className="placeholder bg-secondary" style={{ width: '80px', height: '36px', borderRadius: '6px' }}></span>
                        </div>
                        <div className="placeholder-glow">
                            <span className="placeholder bg-secondary" style={{ width: '80px', height: '36px', borderRadius: '6px' }}></span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Content Skeleton */}
            <div id="kt_app_content" className="app-content flex-column-fluid">
                <div id="kt_app_content_container" className="app-container container-xxl">
                    {/* Statistics Row Skeleton */}
                    <div className="row gx-5 gx-xl-10 mb-xl-10">
                        {/* Left Column */}
                        <div className="col-xl-6 mb-5 mb-xl-10">
                            <div className="row g-lg-5 g-xl-10">
                                {/* Sales Card Skeleton */}
                                <div className="col-md-6 col-xl-6 mb-5 mb-xl-10">
                                    <div className="card overflow-hidden h-md-50 mb-5 mb-xl-10 bg-light-primary">
                                        <div className="card-body d-flex justify-content-between flex-column px-9 pb-0">
                                            <div className="mb-4">
                                                <div className="placeholder-glow">
                                                    <span className="placeholder col-6 bg-secondary" style={{ height: '32px', borderRadius: '6px' }}></span>
                                                </div>
                                                <div className="placeholder-glow mt-2">
                                                    <span className="placeholder col-8 bg-secondary" style={{ height: '16px', borderRadius: '4px' }}></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Customer Card Skeleton */}
                                    <div className="card h-md-50">
                                        <div className="card-body d-flex flex-column">
                                            <div className="d-flex flex-stack">
                                                <div className="placeholder-glow flex-grow-1">
                                                    <span className="placeholder col-5 bg-secondary" style={{ height: '40px', borderRadius: '6px' }}></span>
                                                    <span className="placeholder col-7 bg-secondary mt-2 d-block" style={{ height: '16px', borderRadius: '4px' }}></span>
                                                </div>
                                                <div className="placeholder-glow">
                                                    <span className="placeholder bg-secondary rounded-circle" style={{ width: '50px', height: '50px' }}></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Expenses Card Skeleton */}
                                <div className="col-md-6 col-xl-6 mb-md-5 mb-xl-10">
                                    <div className="card overflow-hidden h-md-50 mb-5 mb-xl-10 bg-light-success">
                                        <div className="card-body d-flex justify-content-between flex-column px-9 pb-0">
                                            <div className="mb-4">
                                                <div className="placeholder-glow">
                                                    <span className="placeholder col-6 bg-secondary" style={{ height: '32px', borderRadius: '6px' }}></span>
                                                </div>
                                                <div className="placeholder-glow mt-2">
                                                    <span className="placeholder col-8 bg-secondary" style={{ height: '16px', borderRadius: '4px' }}></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Orders Card Skeleton */}
                                    <div className="card h-md-50">
                                        <div className="card-body d-flex flex-column">
                                            <div className="d-flex flex-stack">
                                                <div className="placeholder-glow flex-grow-1">
                                                    <span className="placeholder col-5 bg-secondary" style={{ height: '40px', borderRadius: '6px' }}></span>
                                                    <span className="placeholder col-7 bg-secondary mt-2 d-block" style={{ height: '16px', borderRadius: '4px' }}></span>
                                                </div>
                                                <div className="placeholder-glow">
                                                    <span className="placeholder bg-secondary rounded-circle" style={{ width: '50px', height: '50px' }}></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Chart Skeleton */}
                        <div className="col-lg-12 col-xl-12 col-xxl-6 mb-5 mb-xl-0">
                            <div className="card card-flush h-md-100">
                                <div className="card-header pt-7">
                                    <div className="placeholder-glow">
                                        <span className="placeholder col-4 bg-secondary" style={{ height: '24px', borderRadius: '6px' }}></span>
                                        <span className="placeholder col-3 bg-secondary mt-2 d-block" style={{ height: '14px', borderRadius: '4px' }}></span>
                                    </div>
                                </div>
                                <div className="card-body">
                                    <div className="placeholder-glow">
                                        <span className="placeholder col-12 bg-secondary" style={{ height: '200px', borderRadius: '8px' }}></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Tables Row Skeleton */}
                    <div className="row gy-5 g-xl-10">
                        {/* Sales Table Skeleton */}
                        <div className="col-xl-6 mb-xl-10">
                            <div className="card card-flush h-xl-100">
                                <div className="card-header pt-7">
                                    <div className="placeholder-glow">
                                        <span className="placeholder col-4 bg-secondary" style={{ height: '20px', borderRadius: '6px' }}></span>
                                        <span className="placeholder col-5 bg-secondary mt-2 d-block" style={{ height: '14px', borderRadius: '4px' }}></span>
                                    </div>
                                </div>
                                <div className="card-body pt-2">
                                    {[...Array(5)].map((_, idx) => (
                                        <div key={idx} className="d-flex align-items-center mb-4">
                                            <div className="placeholder-glow flex-grow-1">
                                                <span className="placeholder col-12 bg-secondary" style={{ height: '40px', borderRadius: '6px' }}></span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Purchases Table Skeleton */}
                        <div className="col-xl-6 mb-5 mb-xl-10">
                            <div className="card card-flush h-xl-100">
                                <div className="card-header pt-7">
                                    <div className="placeholder-glow">
                                        <span className="placeholder col-4 bg-secondary" style={{ height: '20px', borderRadius: '6px' }}></span>
                                        <span className="placeholder col-5 bg-secondary mt-2 d-block" style={{ height: '14px', borderRadius: '4px' }}></span>
                                    </div>
                                </div>
                                <div className="card-body pt-2">
                                    {[...Array(5)].map((_, idx) => (
                                        <div key={idx} className="d-flex align-items-center mb-4">
                                            <div className="placeholder-glow flex-grow-1">
                                                <span className="placeholder col-12 bg-secondary" style={{ height: '40px', borderRadius: '6px' }}></span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );

    if (loading) {
        return <SkeletonLoader />;
    }

    return (
        <>
            {/* Toolbar */}
            <div id="kt_app_toolbar" className="app-toolbar py-3 py-lg-6">
                <div id="kt_app_toolbar_container" className="app-container container-xxl d-flex flex-stack">
                    {/* Page title */}
                    <div className="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                        {/* Title */}
                        <h1 className="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                            Sales Dashboard
                        </h1>
                        
                        {/* Breadcrumb */}
                        <ul className="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                            {/* Home */}
                            <li className="breadcrumb-item text-muted">
                                <a href="/merchant/dashboard" className="text-muted text-hover-primary">
                                    Home
                                </a>
                            </li>
                            {/* Separator */}
                            <li className="breadcrumb-item">
                                <span className="bullet bg-gray-500 w-5px h-2px"></span>
                            </li>
                            {/* Sales */}
                            <li className="breadcrumb-item text-muted">
                                Sales
                            </li>
                            {/* Separator */}
                            <li className="breadcrumb-item">
                                <span className="bullet bg-gray-500 w-5px h-2px"></span>
                            </li>
                            {/* Current Page */}
                            <li className="breadcrumb-item text-gray-900">
                                Dashboard
                            </li>
                        </ul>
                    </div>
                    
                    {/* Toolbar Actions */}
                    <div className="d-flex align-items-center gap-2 gap-lg-3">
                        {/* Refresh Button */}
                        <button 
                            onClick={fetchDashboardData}
                            className="btn btn-sm btn-flex btn-light btn-active-primary fw-bold"
                        >
                            <i className="ki-duotone ki-arrows-circle fs-3">
                                <span className="path1"></span>
                                <span className="path2"></span>
                            </i>
                            Refresh
                        </button>
                        
                        {/* Filter Dropdown */}
                        <div className="m-0">
                            <a href="#" className="btn btn-sm btn-flex btn-light btn-active-primary fw-bold" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                <i className="ki-duotone ki-filter fs-6 text-muted me-1">
                                    <span className="path1"></span>
                                    <span className="path2"></span>
                                </i>
                                Filter
                            </a>
                            <div className="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true">
                                <div className="px-7 py-5">
                                    <div className="fs-5 text-dark fw-bold">Filter Options</div>
                                </div>
                                <div className="separator border-gray-200"></div>
                                <div className="px-7 py-5">
                                    <div className="mb-10">
                                        <label className="form-label fw-semibold">Date Range:</label>
                                        <div>
                                            <select className="form-select form-select-solid" data-kt-select2="true" data-placeholder="Select option" data-allow-clear="true">
                                                <option></option>
                                                <option value="1" selected>Today</option>
                                                <option value="2">Yesterday</option>
                                                <option value="3">Last 7 Days</option>
                                                <option value="4">Last 30 Days</option>
                                                <option value="5">This Month</option>
                                                <option value="6">Last Month</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div className="d-flex justify-content-end">
                                        <button type="reset" className="btn btn-sm btn-light btn-active-light-primary me-2" data-kt-menu-dismiss="true">Reset</button>
                                        <button type="submit" className="btn btn-sm btn-primary" data-kt-menu-dismiss="true">Apply</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {/* Export Button */}
                        <a href="#" className="btn btn-sm btn-flex btn-primary fw-bold">
                            <i className="ki-duotone ki-exit-up fs-3">
                                <span className="path1"></span>
                                <span className="path2"></span>
                            </i>
                            Export
                        </a>
                    </div>
                </div>
            </div>

            {/* Content */}
            <div id="kt_app_content" className="app-content flex-column-fluid">
                <div id="kt_app_content_container" className="app-container container-xxl">
            {/* Statistics Row */}
            <div className="row gx-5 gx-xl-10 mb-xl-10">
                {/* Total Sales & Total Expenses Column */}
                <div className="col-xl-6 mb-5 mb-xl-10">
                    <div className="row g-lg-5 g-xl-10">
                        {/* Total Sales Card */}
                        <div className="col-md-6 col-xl-6 mb-5 mb-xl-10">
                            <div className="card overflow-hidden h-md-50 mb-5 mb-xl-10 bg-light-primary">
                                <div className="card-body d-flex justify-content-between flex-column px-0 pb-0">
                                    <div className="mb-4 px-9">
                                        <div className="d-flex align-items-center mb-2">
                                            <span className="fw-bold text-gray-800 me-2 ls-n2" style={{ fontSize: '24px' }}>
                                                ${statistics.total_sales}
                                            </span>
                                        </div>
                                        <span className="fs-6 fw-semibold text-gray-500">Total Sales</span>
                                    </div>
                                    <div id="kt_card_widget_12_chart" className="min-h-auto" style={{ height: '125px' }}></div>
                                </div>
                            </div>

                            {/* Customer Card */}
                            <div className="card h-md-50">
                                <div className="card-body d-flex flex-column">
                                    <div className="d-flex flex-stack mb-4">
                                        <div className="d-flex flex-column">
                                            <span className="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">
                                                {statistics.total_customers}
                                            </span>
                                            <span className="text-gray-500 pt-1 fw-semibold fs-6">Total Customers</span>
                                        </div>
                                        <div className="symbol symbol-50px">
                                            <span className="symbol-label bg-light-primary">
                                                <i className="ki-duotone ki-people fs-2x text-primary">
                                                    <span className="path1"></span>
                                                    <span className="path2"></span>
                                                    <span className="path3"></span>
                                                    <span className="path4"></span>
                                                </i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Total Expenses Card */}
                        <div className="col-md-6 col-xl-6 mb-md-5 mb-xl-10">
                            <div className="card overflow-hidden h-md-50 mb-5 mb-xl-10 bg-light-success">
                                <div className="card-body d-flex justify-content-between flex-column px-0 pb-0">
                                    <div className="mb-4 px-9">
                                        <div className="d-flex align-items-center mb-2">
                                            <span className="fw-bold text-gray-800 me-2 ls-n2" style={{ fontSize: '24px' }}>
                                                ${statistics.total_expenses}
                                            </span>
                                        </div>
                                        <span className="fs-6 fw-semibold text-gray-500">Total Expenses</span>
                                    </div>
                                    <div id="kt_card_widget_13_chart" className="min-h-auto" style={{ height: '125px' }}></div>
                                </div>
                            </div>

                            {/* Orders Card */}
                            <div className="card h-md-50">
                                <div className="card-body d-flex flex-column">
                                    <div className="d-flex flex-stack mb-4">
                                        <div className="d-flex flex-column">
                                            <span className="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">
                                                {statistics.total_orders}
                                            </span>
                                            <span className="text-gray-500 pt-1 fw-semibold fs-6">Total Orders</span>
                                        </div>
                                        <div className="symbol symbol-50px">
                                            <span className="symbol-label bg-light-success">
                                                <i className="ki-duotone ki-basket fs-2x text-success">
                                                    <span className="path1"></span>
                                                    <span className="path2"></span>
                                                    <span className="path3"></span>
                                                    <span className="path4"></span>
                                                </i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Sales Chart Placeholder */}
                <div className="col-lg-12 col-xl-12 col-xxl-6 mb-5 mb-xl-0">
                    <div className="card card-flush h-md-100">
                        <div className="card-header pt-7">
                            <h3 className="card-title align-items-start flex-column">
                                <span className="card-label fw-bold text-gray-800">Sales Overview</span>
                                <span className="text-gray-500 mt-1 fw-semibold fs-6">Revenue trends</span>
                            </h3>
                        </div>
                        <div className="card-body">
                            <div className="alert alert-info">
                                <i className="ki-duotone ki-information-5 fs-2x me-2">
                                    <span className="path1"></span>
                                    <span className="path2"></span>
                                    <span className="path3"></span>
                                </i>
                                Sales chart will be displayed here
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Latest Sales & Purchases Tables */}
            <div className="row gy-5 g-xl-10">
                {/* Latest Sales Table */}
                <div className="col-xl-6 mb-xl-10">
                    <div className="card card-flush h-xl-100">
                        <div className="card-header pt-7">
                            <h3 className="card-title align-items-start flex-column">
                                <span className="card-label fw-bold text-gray-800">Latest Sales</span>
                                <span className="text-gray-500 mt-1 fw-semibold fs-6">Recent sales activity</span>
                            </h3>
                        </div>
                        <div className="card-body pt-2">
                            <div className="table-responsive">
                                <table className="table align-middle table-row-dashed fs-6 gy-3">
                                    <thead>
                                        <tr className="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                            <th className="min-w-100px">Order ID</th>
                                            <th className="text-end min-w-100px">Created</th>
                                            <th className="text-end min-w-125px">Customer</th>
                                            <th className="text-end min-w-100px">Total</th>
                                            <th className="text-end min-w-50px">Status</th>
                                            <th className="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody className="fw-bold text-gray-600">
                                        {latestSales.length > 0 ? (
                                            latestSales.map((sale) => (
                                                <tr key={sale.id}>
                                                    <td>
                                                        <a href={`/sales/${sale.id}`} className="text-gray-800 text-hover-primary">
                                                            {sale.reference_no}
                                                        </a>
                                                    </td>
                                                    <td className="text-end">
                                                        {new Date(sale.created_at).toLocaleDateString()}
                                                    </td>
                                                    <td className="text-end">
                                                        <a href="#" className="text-gray-600 text-hover-primary">
                                                            {sale.customer?.name || 'Walk-in Customer'}
                                                        </a>
                                                    </td>
                                                    <td className="text-end">
                                                        ${parseFloat(sale.grand_total).toFixed(2)}
                                                    </td>
                                                    <td className="text-end">
                                                        <span className={`badge py-3 px-4 fs-7 ${getStatusBadge(sale.status_badge)}`}>
                                                            {sale.status_text}
                                                        </span>
                                                    </td>
                                                    <td className="text-end">
                                                        <a href={`/sales/${sale.id}`} className="btn btn-sm btn-icon btn-light btn-active-light-primary">
                                                            <i className="ki-duotone ki-eye fs-3 me-2">
                                                                <span className="path1"></span>
                                                                <span className="path2"></span>
                                                                <span className="path3"></span>
                                                            </i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan="6" className="text-center py-5">
                                                    <div className="text-gray-500">No sales found</div>
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Latest Purchases Table */}
                <div className="col-xl-6 mb-5 mb-xl-10">
                    <div className="card card-flush h-xl-100">
                        <div className="card-header pt-7">
                            <h3 className="card-title align-items-start flex-column">
                                <span className="card-label fw-bold text-gray-900">Latest Purchases</span>
                                <span className="text-gray-500 mt-1 fw-semibold fs-6">Recent purchase transactions</span>
                            </h3>
                            <div className="card-toolbar">
                                <a href="/purchases" className="btn btn-light btn-sm">View All</a>
                            </div>
                        </div>
                        <div className="card-body">
                            <div className="table-responsive">
                                <table className="table align-middle table-row-dashed fs-6 gy-3">
                                    <thead>
                                        <tr className="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                            <th className="min-w-100px">Purchase ID</th>
                                            <th className="min-w-150px">Supplier</th>
                                            <th className="text-end min-w-100px">Date</th>
                                            <th className="text-end min-w-100px">Total</th>
                                            <th className="text-end min-w-100px">Status</th>
                                            <th className="text-end min-w-50px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody className="fw-bold text-gray-600">
                                        {latestPurchases.length > 0 ? (
                                            latestPurchases.map((purchase) => (
                                                <tr key={purchase.id}>
                                                    <td>
                                                        <span className="text-gray-900 fw-bold">#{purchase.id}</span>
                                                    </td>
                                                    <td>
                                                        <span className="text-gray-900">{purchase.supplier?.name || 'N/A'}</span>
                                                    </td>
                                                    <td className="text-end">
                                                        {new Date(purchase.created_at).toLocaleDateString()}
                                                    </td>
                                                    <td className="text-end">
                                                        <span className="text-gray-900 fw-bold">
                                                            ${parseFloat(purchase.grand_total).toFixed(2)}
                                                        </span>
                                                    </td>
                                                    <td className="text-end">
                                                        {purchase.status == 1 ? (
                                                            <span className="badge py-3 px-4 fs-7 badge-light-success">Active</span>
                                                        ) : (
                                                            <span className="badge py-3 px-4 fs-7 badge-light-warning">Inactive</span>
                                                        )}
                                                    </td>
                                                    <td className="text-end">
                                                        <a href={`/purchases/${purchase.id}`} className="btn btn-sm btn-light">View</a>
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan="6" className="text-center py-4">
                                                    <div className="text-muted">No recent purchases found</div>
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                </div>
            </div>
        </>
    );
}
