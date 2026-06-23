import React, { useState, useEffect, useRef } from 'react';
import { get, del, post, getToken } from '../../utils/api';
import { API_ENDPOINTS } from '../../utils/constants';

export default function Customers() {
    const [customers, setCustomers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [searchTerm, setSearchTerm] = useState('');
    
    // Pagination states
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [totalRecords, setTotalRecords] = useState(0);
    const [perPage, setPerPage] = useState(10);
    
    // Filter states
    const [showFilters, setShowFilters] = useState(false);
    const [filters, setFilters] = useState({
        country_id: '',
        date_from: '',
        date_to: '',
    });
    
    // Countries for dropdown
    const [countries, setCountries] = useState([]);
    const [loadingCountries, setLoadingCountries] = useState(false);
    
    // Import states
    const [showImportModal, setShowImportModal] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importPreviewData, setImportPreviewData] = useState(null);
    const [importing, setImporting] = useState(false);
    const fileInputRef = useRef(null);

    useEffect(() => {
        fetchCustomers();
        fetchCountries();
    }, []);

    // Real-time filtering - fetch when filters change
    useEffect(() => {
        const timer = setTimeout(() => {
            if (!loading) {
                setCurrentPage(1); // Reset to page 1 when filters change
                fetchCustomers(1);
            }
        }, 500); // Debounce for 500ms

        return () => clearTimeout(timer);
    }, [filters.country_id, filters.date_from, filters.date_to]);

    // Fetch when page changes
    useEffect(() => {
        if (currentPage > 1) {
            fetchCustomers(currentPage);
        }
    }, [currentPage]);

    // Reinitialize KTMenu after customers are loaded (for dropdown menus)
    useEffect(() => {
        if (!loading && customers.length > 0) {
            // Reinitialize Metronic menu components
            if (typeof KTMenu !== 'undefined' && typeof KTMenu.createInstances === 'function') {
                setTimeout(() => {
                    KTMenu.createInstances();
                }, 100);
            }
        }
    }, [loading, customers]);

    const fetchCountries = async () => {
        try {
            setLoadingCountries(true);
            const token = getToken();
            
            if (!token) {
                console.error('No token found');
                return;
            }

            const response = await fetch('http://localhost:8000/api/softpos/countries', {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (data.success) {
                setCountries(data.data || []);
            } else {
                console.error('Failed to fetch countries:', data.message);
            }
        } catch (error) {
            console.error('Error fetching countries:', error);
        } finally {
            setLoadingCountries(false);
        }
    };

    const fetchCustomers = async (page = 1) => {
        try {
            setLoading(true);
            
            // Check if token exists
            const token = getToken();
            if (!token) {
                console.warn('No API token found. Please login again.');
                setError('Authentication required. Please login again.');
                setLoading(false);
                return;
            }
            
            // Using the new CRUD API endpoint with pagination
            const response = await get(API_ENDPOINTS.CUSTOMERS.LIST, {
                params: {
                    page: page,
                    per_page: perPage,
                    search: searchTerm || undefined,
                    ...filters
                }
            });
            
            // Handle API response format
            if (response.data.success !== false) {
                // Extract customers from the response
                const customersData = response.data.data?.customers || [];
                setCustomers(Array.isArray(customersData) ? customersData : []);
                
                // Update pagination info
                if (response.data.data?.pagination) {
                    const pagination = response.data.data.pagination;
                    setTotalPages(pagination.last_page || 1);
                    setTotalRecords(pagination.total || 0);
                    setCurrentPage(pagination.current_page || 1);
                }
            } else {
                setError(response.data.message || 'Failed to load customers');
            }
            setLoading(false);
        } catch (err) {
            console.error('Error fetching customers:', err);
            setError('Failed to load customers');
            setLoading(false);
        }
    };

    const handleDelete = async (customerId) => {
        if (!confirm('Are you sure you want to delete this customer?')) {
            return;
        }

        try {
            // Check if token exists
            const token = getToken();
            if (!token) {
                alert('Authentication required. Please login again.');
                return;
            }
            
            // Using the new CRUD DELETE endpoint
            const response = await del(API_ENDPOINTS.CUSTOMERS.DELETE(customerId));
            
            if (response.data.success !== false) {
                // Show success message
                alert('Customer deleted successfully!');
            // Refresh the list
            fetchCustomers();
            } else {
                alert(response.data.message || 'Failed to delete customer');
            }
        } catch (err) {
            console.error('Error deleting customer:', err);
            alert('Failed to delete customer');
        }
    };

    const handleExport = async () => {
        try {
            const token = getToken();
            if (!token) {
                alert('Authentication required. Please login again.');
                return;
            }

            // Build query string with filters
            const params = new URLSearchParams();
            if (filters.country_id) params.append('country_id', filters.country_id);
            if (filters.date_from) params.append('date_from', filters.date_from);
            if (filters.date_to) params.append('date_to', filters.date_to);
            if (searchTerm) params.append('search', searchTerm);

            const url = `${API_ENDPOINTS.CUSTOMERS.EXPORT}?${params.toString()}`;
            
            // Make authenticated request to download export
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'text/csv',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to export customers');
            }

            // Get the blob from response
            const blob = await response.blob();
            
            // Create a download link
            const downloadUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = downloadUrl;
            a.download = `customers_export_${new Date().toISOString().slice(0, 10)}.csv`;
            document.body.appendChild(a);
            a.click();
            
            // Cleanup
            window.URL.revokeObjectURL(downloadUrl);
            document.body.removeChild(a);
        } catch (error) {
            console.error('Error exporting customers:', error);
            alert('Failed to export customers. Please try again.');
        }
    };

    const handleDownloadTemplate = async () => {
        try {
            const token = getToken();
            if (!token) {
                alert('Authentication required. Please login again.');
                return;
            }

            // Make authenticated request to download template
            const response = await fetch(API_ENDPOINTS.CUSTOMERS.EXPORT_TEMPLATE, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'text/csv',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to download template');
            }

            // Get the blob from response
            const blob = await response.blob();
            
            // Create a download link
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'customers_import_template.csv';
            document.body.appendChild(a);
            a.click();
            
            // Cleanup
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } catch (error) {
            console.error('Error downloading template:', error);
            alert('Failed to download template. Please try again.');
        }
    };

    const handleFileSelect = (e) => {
        const file = e.target.files[0];
        if (file) {
            setImportFile(file);
            handleImportPreview(file);
        }
    };

    const handleImportPreview = async (file) => {
        try {
            setImporting(true);
            const formData = new FormData();
            formData.append('import_file', file);

            const response = await post(API_ENDPOINTS.CUSTOMERS.IMPORT_PREVIEW, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                }
            });

            if (response.data.success !== false) {
                setImportPreviewData(response.data.data);
            } else {
                alert('Failed to preview import: ' + response.data.message);
                setImportPreviewData(null);
            }
        } catch (err) {
            console.error('Error previewing import:', err);
            alert('Failed to preview import file');
            setImportPreviewData(null);
        } finally {
            setImporting(false);
        }
    };

    const handleConfirmImport = async () => {
        if (!importFile) {
            alert('Please select a file first');
            return;
        }

        if (!confirm('Are you sure you want to import these customers?')) {
            return;
        }

        try {
            setImporting(true);
            const formData = new FormData();
            formData.append('import_file', importFile);

            const response = await post(API_ENDPOINTS.CUSTOMERS.IMPORT, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                }
            });

            if (response.data.success !== false) {
                const { imported_count, skipped_count, errors } = response.data.data;
                let message = `Import completed!\nImported: ${imported_count}\nSkipped: ${skipped_count}`;
                
                if (errors && errors.length > 0) {
                    message += '\n\nErrors:\n' + errors.slice(0, 5).join('\n');
                    if (errors.length > 5) {
                        message += `\n... and ${errors.length - 5} more errors`;
                    }
                }
                
                alert(message);
                setShowImportModal(false);
                setImportFile(null);
                setImportPreviewData(null);
                fetchCustomers();
            } else {
                alert('Failed to import: ' + response.data.message);
            }
        } catch (err) {
            console.error('Error importing:', err);
            alert('Failed to import customers');
        } finally {
            setImporting(false);
        }
    };

    const resetFilters = () => {
        setFilters({
            country_id: '',
            date_from: '',
            date_to: '',
        });
        setSearchTerm('');
        setCurrentPage(1);
    };

    // Real-time search - fetch when search term changes
    useEffect(() => {
        const timer = setTimeout(() => {
            if (!loading && searchTerm !== undefined) {
                setCurrentPage(1);
                fetchCustomers(1);
            }
        }, 500); // Debounce for 500ms

        return () => clearTimeout(timer);
    }, [searchTerm]);

        return (
        <>
            {/* Toolbar */}
            <div id="kt_app_toolbar" className="app-toolbar py-3 py-lg-6">
                <div id="kt_app_toolbar_container" className="app-container container-xxl d-flex flex-stack">
                    {/* Page title */}
                    <div className="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                        {/* Title */}
                        <h1 className="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                            Customers Management
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
                                Customers
                            </li>
                        </ul>
                    </div>
                    
                    {/* Toolbar Actions */}
                    <div className="d-flex align-items-center gap-2 gap-lg-3">
                        {/* Filter Button */}
                        <button 
                            onClick={() => setShowFilters(!showFilters)}
                            className="btn btn-sm btn-flex btn-light btn-active-primary fw-bold"
                        >
                            <i className="ki-duotone ki-filter fs-6 text-muted me-1">
                                <span className="path1"></span>
                                <span className="path2"></span>
                            </i>
                            Filter
                        </button>
                        
                        {/* Export Button */}
                        <button 
                            onClick={handleExport}
                            className="btn btn-sm btn-flex btn-light btn-active-primary fw-bold"
                        >
                            <i className="ki-duotone ki-exit-up fs-3">
                                <span className="path1"></span>
                                <span className="path2"></span>
                            </i>
                            Export
                        </button>

                        {/* Import Button */}
                        <button 
                            onClick={() => setShowImportModal(true)}
                            className="btn btn-sm btn-flex btn-light btn-active-primary fw-bold"
                        >
                            <i className="ki-duotone ki-exit-down fs-3">
                                <span className="path1"></span>
                                <span className="path2"></span>
                            </i>
                            Import
                        </button>
                        
                        {/* Add Customer Button */}
                        <a 
                            href="/merchant/sales/customers/create" 
                            className="btn btn-sm btn-flex btn-primary fw-bold"
                        >
                            <i className="ki-duotone ki-plus fs-2"></i>
                            Add Customer
                        </a>
                    </div>
                </div>
            </div>

            {/* Content */}
            <div id="kt_app_content" className="app-content flex-column-fluid">
                <div id="kt_app_content_container" className="app-container container-xxl">
                    
                    {/* Error Alert */}
                    {error && (
                        <div className="alert alert-danger alert-dismissible fade show mb-5" role="alert">
                <i className="ki-duotone ki-information fs-2x me-3">
                    <span className="path1"></span>
                    <span className="path2"></span>
                    <span className="path3"></span>
                </i>
                            <strong>Error:</strong> {error}
                            <button 
                                type="button" 
                                className="btn-close" 
                                onClick={() => setError(null)}
                                aria-label="Close"
                            ></button>
                        </div>
                    )}
                    
                    {/* Filter Panel */}
                    {showFilters && (
                        <div className="card mb-5">
                            <div className="card-body">
                                <div className="row g-3">
                                    <div className="col-md-3">
                                        <label className="form-label">Date From</label>
                                        <input 
                                            type="date" 
                                            className="form-control form-control-sm"
                                            value={filters.date_from}
                                            onChange={(e) => setFilters({...filters, date_from: e.target.value})}
                                        />
                                    </div>
                                    <div className="col-md-3">
                                        <label className="form-label">Date To</label>
                                        <input 
                                            type="date" 
                                            className="form-control form-control-sm"
                                            value={filters.date_to}
                                            onChange={(e) => setFilters({...filters, date_to: e.target.value})}
                                        />
                                    </div>
                                    <div className="col-md-3">
                                        <label className="form-label">Country</label>
                                        <select 
                                            className="form-select form-select-sm"
                                            value={filters.country_id}
                                            onChange={(e) => setFilters({...filters, country_id: e.target.value})}
                                            disabled={loadingCountries}
                                        >
                                            <option value="">All Countries</option>
                                            {countries.map(country => (
                                                <option key={country.id} value={country.id}>
                                                    {country.name}
                                                </option>
                                            ))}
                                        </select>
                                        {loadingCountries && (
                                            <div className="spinner-border spinner-border-sm mt-1" role="status">
                                                <span className="visually-hidden">Loading...</span>
                                            </div>
                                        )}
                                    </div>
                                    <div className="col-md-3 d-flex align-items-end">
                                        <button onClick={resetFilters} className="btn btn-sm btn-light-primary w-100">
                                            <i className="ki-duotone ki-arrows-circle fs-3">
                                                <span className="path1"></span>
                                                <span className="path2"></span>
                                            </i>
                                            Reset Filters
                                        </button>
                                    </div>
                                </div>
                                <div className="text-muted fs-7 mt-3">
                                    <i className="ki-duotone ki-information fs-5 text-primary me-1">
                    <span className="path1"></span>
                    <span className="path2"></span>
                    <span className="path3"></span>
                </i>
                                    Filters apply automatically as you type
                                </div>
                            </div>
            </div>
                    )}

                    {/* Customers Card */}
        <div className="card">
            {/* Card Header */}
            <div className="card-header border-0 pt-6">
                <div className="card-title">
                    {/* Search */}
                    <div className="d-flex align-items-center position-relative my-1">
                        <i className="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        <input 
                            type="text" 
                            className="form-control form-control-solid w-250px ps-13" 
                            placeholder="Search customers..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                        />
                    </div>
                    {/* Loading indicator */}
                    {loading && (
                        <div className="ms-3 d-flex align-items-center">
                            <div className="spinner-border spinner-border-sm text-primary me-2" role="status">
                                <span className="visually-hidden">Loading...</span>
                </div>
                            <span className="text-muted fs-7">Updating...</span>
                    </div>
                    )}
                </div>
            </div>

            {/* Card Body */}
            <div className="card-body py-4">
                <div className="table-responsive">
                    <table className="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr className="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th className="min-w-125px">Customer</th>
                                <th className="min-w-125px">Email</th>
                                <th className="min-w-125px">Phone</th>
                                            <th className="min-w-100px">Company</th>
                                <th className="text-end min-w-100px">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="text-gray-600 fw-semibold">
                                        {loading ? (
                                            <tr>
                                                <td colSpan="5" className="text-center py-10">
                                                    <div className="d-flex flex-column align-items-center">
                                                        <div className="spinner-border text-primary mb-3" role="status">
                                                            <span className="visually-hidden">Loading...</span>
                                                        </div>
                                                        <span className="text-muted">Loading customers...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        ) : customers.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className="text-center py-10">
                                        <div className="d-flex flex-column align-items-center">
                                            <i className="ki-duotone ki-file-deleted fs-5x text-muted mb-4">
                                                <span className="path1"></span>
                                                <span className="path2"></span>
                                            </i>
                                            <span className="fs-5 text-muted">No customers found</span>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                            customers.map(customer => (
                                    <tr key={customer.id}>
                                        <td>
                                            <div className="d-flex align-items-center">
                                                <div className="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                                    <div className="symbol-label">
                                                        <div className="symbol-label fs-3 bg-light-primary text-primary">
                                                            {customer.name?.charAt(0).toUpperCase() || 'C'}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="d-flex flex-column">
                                                    <span className="text-gray-800 text-hover-primary mb-1">
                                                        {customer.name || 'N/A'}
                                                    </span>
                                                    <span className="text-muted fs-7">
                                                        #{customer.id}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{customer.email || 'N/A'}</td>
                                        <td>{customer.phone || 'N/A'}</td>
                                                    <td>{customer.company_name || 'N/A'}</td>
                                        <td className="text-end">
                                            <button
                                                            type="button" 
                                                            className="btn btn-sm btn-light btn-active-light-primary" 
                                                            data-kt-menu-trigger="click" 
                                                            data-kt-menu-placement="bottom-end"
                                                        >
                                                            Actions
                                                            <i className="ki-duotone ki-down fs-5 ms-1"></i>
                                                        </button>
                                                        
                                                        {/* Actions Dropdown Menu */}
                                                        <div className="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
                                                            <div className="menu-item px-3">
                                                                <a 
                                                                    href={`/merchant/sales/customers/${customer.id}`}
                                                                    className="menu-link px-3"
                                                                >
                                                                    View
                                                                </a>
                                                            </div>
                                                            <div className="menu-item px-3">
                                                                <a 
                                                                    href={`/merchant/sales/customers/${customer.id}/edit`}
                                                                    className="menu-link px-3"
                                            >
                                                Edit
                                                                </a>
                                                            </div>
                                                            <div className="menu-item px-3">
                                                                <a 
                                                                    href="#" 
                                                                    className="menu-link px-3 text-danger" 
                                                                    onClick={(e) => {
                                                                        e.preventDefault();
                                                                        handleDelete(customer.id);
                                                                    }}
                                            >
                                                Delete
                                                                </a>
                                                            </div>
                                                        </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                            </div>

                            {/* Pagination */}
                            {!loading && totalPages > 1 && (
                                <div className="d-flex justify-content-between align-items-center flex-wrap mt-5">
                                    {/* Pagination Info */}
                                    <div className="text-muted fs-7">
                                        Showing {((currentPage - 1) * perPage) + 1} to {Math.min(currentPage * perPage, totalRecords)} of {totalRecords} customers
                                    </div>

                                    {/* Pagination Controls */}
                                    <div className="d-flex align-items-center">
                                        {/* Previous Button */}
                                        <button 
                                            className="btn btn-sm btn-light me-2"
                                            onClick={() => setCurrentPage(currentPage - 1)}
                                            disabled={currentPage === 1}
                                        >
                                            <i className="ki-duotone ki-arrow-left fs-3">
                                                <span className="path1"></span>
                                                <span className="path2"></span>
                                            </i>
                                            Previous
                                        </button>

                                        {/* Page Numbers */}
                                        <div className="d-flex align-items-center me-2">
                                            {[...Array(totalPages)].map((_, index) => {
                                                const page = index + 1;
                                                // Show first page, last page, current page, and pages around current
                                                if (
                                                    page === 1 || 
                                                    page === totalPages || 
                                                    (page >= currentPage - 1 && page <= currentPage + 1)
                                                ) {
                                                    return (
                                                        <button
                                                            key={page}
                                                            className={`btn btn-sm ${page === currentPage ? 'btn-primary' : 'btn-light'} me-1`}
                                                            onClick={() => setCurrentPage(page)}
                                                        >
                                                            {page}
                                                        </button>
                                                    );
                                                } else if (
                                                    page === currentPage - 2 || 
                                                    page === currentPage + 2
                                                ) {
                                                    return <span key={page} className="me-1">...</span>;
                                                }
                                                return null;
                                            })}
                                        </div>

                                        {/* Next Button */}
                                        <button 
                                            className="btn btn-sm btn-light"
                                            onClick={() => setCurrentPage(currentPage + 1)}
                                            disabled={currentPage === totalPages}
                                        >
                                            Next
                                            <i className="ki-duotone ki-arrow-right fs-3">
                                                <span className="path1"></span>
                                                <span className="path2"></span>
                                            </i>
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Import Modal */}
            {showImportModal && (
                <div className="modal fade show d-block" tabIndex="-1" style={{backgroundColor: 'rgba(0,0,0,0.5)'}}>
                    <div className="modal-dialog modal-dialog-centered modal-lg">
                        <div className="modal-content">
                            <div className="modal-header">
                                <h5 className="modal-title">Import Customers</h5>
                                <button 
                                    type="button" 
                                    className="btn-close" 
                                    onClick={() => {
                                        setShowImportModal(false);
                                        setImportFile(null);
                                        setImportPreviewData(null);
                                    }}
                                ></button>
                            </div>
                            <div className="modal-body">
                                {/* Instructions */}
                                <div className="alert alert-primary d-flex align-items-center p-5 mb-5">
                                    <i className="ki-duotone ki-information-5 fs-2x text-primary me-4">
                                        <span className="path1"></span>
                                        <span className="path2"></span>
                                        <span className="path3"></span>
                                    </i>
                                    <div className="d-flex flex-column">
                                        <h4 className="mb-1 text-dark">Import Instructions</h4>
                                        <span>
                                            1. Download the sample template below<br/>
                                            2. Fill in your customer data<br/>
                                            3. Upload the file to preview<br/>
                                            4. Click "Confirm Import" to import
                                        </span>
                                    </div>
                                </div>

                                {/* Download Template Button */}
                                <div className="mb-5">
                                    <button 
                                        onClick={handleDownloadTemplate}
                                        className="btn btn-light-primary w-100"
                                    >
                                        <i className="ki-duotone ki-file-down fs-2">
                                            <span className="path1"></span>
                                            <span className="path2"></span>
                                        </i>
                                        Download Sample Template
                                    </button>
                                </div>

                                {/* File Upload */}
                                <div className="mb-5">
                                    <label className="form-label">Select File (CSV or Excel)</label>
                                    <input 
                                        ref={fileInputRef}
                                        type="file" 
                                        className="form-control"
                                        accept=".csv,.xlsx,.xls"
                                        onChange={handleFileSelect}
                                    />
                                </div>

                                {/* Preview Table */}
                                {importing && (
                                    <div className="text-center py-5">
                                        <div className="spinner-border text-primary" role="status">
                                            <span className="visually-hidden">Loading...</span>
                                        </div>
                                        <p className="mt-3">Processing file...</p>
                                    </div>
                                )}

                                {importPreviewData && !importing && (
                                    <div>
                                        <h6 className="mb-3">Preview ({importPreviewData.data?.length || 0} rows)</h6>
                                        
                                        {importPreviewData.errors && importPreviewData.errors.length > 0 && (
                                            <div className="alert alert-warning mb-3">
                                                <strong>Validation Errors:</strong>
                                                <ul className="mb-0 mt-2">
                                                    {importPreviewData.errors.slice(0, 5).map((error, idx) => (
                                                        <li key={idx}>{error}</li>
                                                    ))}
                                                    {importPreviewData.errors.length > 5 && (
                                                        <li>... and {importPreviewData.errors.length - 5} more errors</li>
                                                    )}
                                                </ul>
                                            </div>
                                        )}

                                        <div className="table-responsive" style={{maxHeight: '300px', overflowY: 'auto'}}>
                                            <table className="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Status</th>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Phone</th>
                                                        <th>Company</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {importPreviewData.data?.map((row, idx) => (
                                                        <tr key={idx} className={!row.is_valid ? 'table-danger' : ''}>
                                                            <td>
                                                                {row.is_valid ? (
                                                                    <i className="ki-duotone ki-check fs-2 text-success">
                                                                        <span className="path1"></span>
                                                                        <span className="path2"></span>
                                                                    </i>
                                                                ) : (
                                                                    <i className="ki-duotone ki-cross fs-2 text-danger" title={row.errors}>
                                                                        <span className="path1"></span>
                                                                        <span className="path2"></span>
                                                                    </i>
                                                                )}
                                                            </td>
                                                            <td>{row.name}</td>
                                                            <td>{row.email}</td>
                                                            <td>{row.phone}</td>
                                                            <td>{row.company_name}</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                )}
                            </div>
                            <div className="modal-footer">
                                <button 
                                    type="button" 
                                    className="btn btn-light" 
                                    onClick={() => {
                                        setShowImportModal(false);
                                        setImportFile(null);
                                        setImportPreviewData(null);
                                    }}
                                >
                                    Cancel
                                </button>
                                <button 
                                    type="button" 
                                    className="btn btn-primary"
                                    onClick={handleConfirmImport}
                                    disabled={!importPreviewData || importing}
                                >
                                    {importing ? 'Importing...' : 'Confirm Import'}
                                </button>
                            </div>
                </div>
            </div>
        </div>
            )}
        </>
    );
}
