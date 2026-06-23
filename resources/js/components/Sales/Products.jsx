import React, { useState, useEffect, useRef } from 'react';
import { get, del, post, getToken } from '../../utils/api';
import { API_ENDPOINTS } from '../../utils/constants';

export default function Products() {
    const [products, setProducts] = useState([]);
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
        category_id: '',
        brand_id: '',
        warehouse_id: '',
        tax_id: '',
        tag_id: '',
        status: '',
        date_from: '',
        date_to: '',
    });
    
    // Filter options
    const [categories, setCategories] = useState([]);
    const [brands, setBrands] = useState([]);
    const [warehouses, setWarehouses] = useState([]);
    const [taxes, setTaxes] = useState([]);
    const [tags, setTags] = useState([]);
    const [loadingOptions, setLoadingOptions] = useState(false);
    
    const [showImportModal, setShowImportModal] = useState(false);
    const [showPreviewModal, setShowPreviewModal] = useState(false);
    const [importing, setImporting] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importPreviewData, setImportPreviewData] = useState(null);
    const fileInputRef = useRef(null);
    
    useEffect(() => {
        fetchProducts();
        fetchFilterOptions();
    }, []);

    // Real-time filtering - fetch when filters change
    useEffect(() => {
        const timer = setTimeout(() => {
            if (!loading) {
                setCurrentPage(1); // Reset to page 1 when filters change
                fetchProducts(1);
            }
        }, 500); // Debounce for 500ms

        return () => clearTimeout(timer);
    }, [filters.category_id, filters.brand_id, filters.warehouse_id, filters.tax_id, filters.tag_id, filters.status, filters.date_from, filters.date_to]);

    // Fetch when page changes
    useEffect(() => {
        if (currentPage > 1) {
            fetchProducts(currentPage);
        }
    }, [currentPage]);

    // Real-time search - fetch when search term changes
    useEffect(() => {
        const timer = setTimeout(() => {
            if (!loading && searchTerm !== undefined) {
                setCurrentPage(1);
                fetchProducts(1);
            }
        }, 500); // Debounce for 500ms

        return () => clearTimeout(timer);
    }, [searchTerm]);

    const fetchProducts = async (page = 1) => {
        try {
            setLoading(true);
            
            const token = getToken();
            if (!token) {
                console.warn('No API token found. Please login again.');
                setError('Authentication required. Please login again.');
                setLoading(false);
                return;
            }
            
            const params = {
                page: page,
                per_page: perPage,
                search: searchTerm || undefined,
                ...filters
            };
            
            const response = await get(API_ENDPOINTS.PRODUCTS.LIST, { params });
            
            if (response.data.success !== false) {
                const productsData = response.data.data?.products || [];
                const products = Array.isArray(productsData) ? productsData : [];
                setProducts(products);
                
                // Update pagination info
                if (response.data.data?.pagination) {
                    const pagination = response.data.data.pagination;
                    setTotalPages(pagination.last_page || 1);
                    setTotalRecords(pagination.total || 0);
                    setCurrentPage(pagination.current_page || 1);
                }
            } else {
                setError(response.data.message || 'Failed to load products');
            }
            setLoading(false);
        } catch (err) {
            console.error('Error fetching products:', err);
            setError('Failed to load products');
            setLoading(false);
        }
    };

    const fetchFilterOptions = async () => {
        try {
            setLoadingOptions(true);
            const token = getToken();
            
            if (!token) {
                console.error('No token found');
                return;
            }

            const response = await get(API_ENDPOINTS.PRODUCTS.SELECT_OPTIONS);
            
            if (response.data.success !== false) {
                const data = response.data.data;
                setCategories(data.categories || []);
                setBrands(data.brands || []);
                setWarehouses(data.warehouses || []);
                setTaxes(data.taxes || []);
                setTags(data.tags || []);
            }
        } catch (error) {
            console.error('Error fetching filter options:', error);
        } finally {
            setLoadingOptions(false);
        }
    };

    const handleDelete = async (productId) => {
        if (!confirm('Are you sure you want to delete this product?')) {
            return;
        }

        try {
            const token = getToken();
            if (!token) {
                alert('Authentication required. Please login again.');
                return;
            }
            
            const response = await del(API_ENDPOINTS.PRODUCTS.DELETE(productId));
            
            if (response.data.success !== false) {
                alert('Product deleted successfully!');
                fetchProducts(currentPage);
            } else {
                alert(response.data.message || 'Failed to delete product');
            }
        } catch (err) {
            console.error('Error deleting product:', err);
            alert('Failed to delete product');
        }
    };

    const handleExport = async () => {
        try {
            const token = getToken();
            if (!token) {
                alert('Authentication required. Please login again.');
                return;
            }

            // Make authenticated request to download export
            const response = await fetch(API_ENDPOINTS.PRODUCTS.EXPORT, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to export products');
            }

            // Get the blob from response
            const blob = await response.blob();
            
            // Create a download link
            const downloadUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = downloadUrl;
            a.download = `products_export_${new Date().toISOString().slice(0, 10)}.xlsx`;
            document.body.appendChild(a);
            a.click();
            
            // Cleanup
            window.URL.revokeObjectURL(downloadUrl);
            document.body.removeChild(a);
        } catch (err) {
            console.error('Error exporting products:', err);
            alert('Failed to export products');
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
            const response = await fetch(API_ENDPOINTS.PRODUCTS.EXPORT_TEMPLATE, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
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
            a.download = 'products_import_template.xlsx';
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
            formData.append('file', file);

            const response = await post(API_ENDPOINTS.PRODUCTS.IMPORT_PREVIEW, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                }
            });

            if (response.data.success !== false) {
                console.log(response.data.data);
                setImportPreviewData(response.data.data);
                // Close upload modal and open preview modal
                setShowImportModal(false);
                setShowPreviewModal(true);
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

        const validCount = importPreviewData?.summary?.valid_count || 0;
        
        if (!confirm(`Are you sure you want to import ${validCount} valid product(s)?`)) {
            return;
        }

        try {
            setImporting(true);
            const formData = new FormData();
            formData.append('file', importFile);

            const response = await post(API_ENDPOINTS.PRODUCTS.IMPORT, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                }
            });

            if (response.data.success !== false) {
                const { imported, updated, failed, has_failures, failed_file_download } = response.data.data;
                
                let message = `✅ Import Completed!\n\n` +
                              `📦 ${imported} products imported\n` +
                              `🔄 ${updated} products updated\n`;
                
                if (failed > 0 && has_failures) {
                    message += `❌ ${failed} products failed validation\n\n`;
                    message += `Download the failed products file to fix errors and re-import.`;
                    
                    // Show download link in console
                    console.log('Failed Products File:', failed_file_download);
                } else {
                    message += `✨ Total: ${imported + updated} products processed successfully`;
                }
                
                alert(message);
                
                setShowImportModal(false);
                setShowPreviewModal(false);
                setImportFile(null);
                setImportPreviewData(null);
                fetchProducts(1);
            } else {
                alert('Failed to import: ' + response.data.message);
            }
        } catch (err) {
            console.error('Error importing:', err);
            alert('Failed to import products');
        } finally {
            setImporting(false);
        }
    };

    const resetFilters = () => {
        setFilters({
            category_id: '',
            brand_id: '',
            warehouse_id: '',
            tax_id: '',
            tag_id: '',
            status: '',
            date_from: '',
            date_to: '',
        });
        setSearchTerm('');
        setCurrentPage(1);
    };

    const getStatusBadge = (status) => {
        const statusColors = {
            'published': 'success',
            'draft': 'warning',
            'scheduled': 'info',
            'inactive': 'danger'
        };
        return statusColors[status] || 'secondary';
    };

    return (
        <>
            {/* Breadcrumbs */}
            <div className="d-flex flex-column flex-column-fluid">
                <div id="kt_app_toolbar" className="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" className="app-container container-xxl d-flex flex-stack">
                        <div className="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                            <h1 className="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                                Products Management
                            </h1>
                            <ul className="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li className="breadcrumb-item text-muted">
                                    <a href="/merchant/sales/dashboard" className="text-muted text-hover-primary">Home</a>
                                </li>
                                <li className="breadcrumb-item">
                                    <span className="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li className="breadcrumb-item text-muted">Product Management</li>
                                <li className="breadcrumb-item">
                                    <span className="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li className="breadcrumb-item text-muted">Products</li>
                            </ul>
                        </div>
                        <div className="d-flex align-items-center gap-2 gap-lg-3">
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
                            <button onClick={handleExport} className="btn btn-sm btn-light-primary">
                                <i className="ki-duotone ki-exit-up fs-2">
                                    <span className="path1"></span>
                                    <span className="path2"></span>
                                </i>
                                Export
                            </button>
                            <button onClick={() => setShowImportModal(true)} className="btn btn-sm btn-light-success">
                                <i className="ki-duotone ki-exit-down fs-2">
                                    <span className="path1"></span>
                                    <span className="path2"></span>
                                </i>
                                Import
                            </button>
                        </div>
                    </div>
                </div>

                {/* Main Content */}
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
                                            <label className="form-label">Category</label>
                                            <select 
                                                className="form-select form-select-sm"
                                                value={filters.category_id}
                                                onChange={(e) => setFilters({...filters, category_id: e.target.value})}
                                                disabled={loadingOptions}
                                            >
                                                <option value="">All Categories</option>
                                                {categories.map(category => (
                                                    <option key={category.id} value={category.id}>
                                                        {category.name?.en || category.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="col-md-3">
                                            <label className="form-label">Brand</label>
                                            <select 
                                                className="form-select form-select-sm"
                                                value={filters.brand_id}
                                                onChange={(e) => setFilters({...filters, brand_id: e.target.value})}
                                                disabled={loadingOptions}
                                            >
                                                <option value="">All Brands</option>
                                                {brands.map(brand => (
                                                    <option key={brand.id} value={brand.id}>
                                                        {brand.name?.en || brand.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="col-md-3">
                                            <label className="form-label">Tag</label>
                                            <select 
                                                className="form-select form-select-sm"
                                                value={filters.tag_id}
                                                onChange={(e) => setFilters({...filters, tag_id: e.target.value})}
                                                disabled={loadingOptions}
                                            >
                                                <option value="">All Tags</option>
                                                {tags.map(tag => (
                                                    <option key={tag.id} value={tag.id}>
                                                        {tag.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="col-md-3">
                                            <label className="form-label">Status</label>
                                            <select 
                                                className="form-select form-select-sm"
                                                value={filters.status}
                                                onChange={(e) => setFilters({...filters, status: e.target.value})}
                                            >
                                                <option value="">All Status</option>
                                                <option value="published">Published</option>
                                                <option value="draft">Draft</option>
                                                <option value="inactive">Inactive</option>
                                                <option value="scheduled">Scheduled</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div className="row g-3 mt-2">
                                        <div className="col-md-3">
                                            <label className="form-label">Warehouse</label>
                                            <select 
                                                className="form-select form-select-sm"
                                                value={filters.warehouse_id}
                                                onChange={(e) => setFilters({...filters, warehouse_id: e.target.value})}
                                                disabled={loadingOptions}
                                            >
                                                <option value="">All Warehouses</option>
                                                {warehouses.map(warehouse => (
                                                    <option key={warehouse.id} value={warehouse.id}>
                                                        {warehouse.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="col-md-3">
                                            <label className="form-label">Tax Rate</label>
                                            <select 
                                                className="form-select form-select-sm"
                                                value={filters.tax_id}
                                                onChange={(e) => setFilters({...filters, tax_id: e.target.value})}
                                                disabled={loadingOptions}
                                            >
                                                <option value="">All Tax Rates</option>
                                                {taxes.map(tax => (
                                                    <option key={tax.id} value={tax.id}>
                                                        {tax.name} ({tax.rate}%)
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
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
                                    </div>
                                    <div className="row g-3 mt-2">
                                        <div className="col-md-10 d-flex align-items-end">
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
                                            placeholder="Search products..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                        />
                                    </div>
                                </div>

                                <div className="card-toolbar">
                                    <div className="d-flex justify-content-end" data-kt-customer-table-toolbar="base">
                                        <a 
                                            href="/merchant/sales/products/create" 
                                            className="btn btn-primary"
                                        >
                                            <i className="ki-duotone ki-plus fs-2"></i>
                                            Add Product
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {/* Card Body */}
                            <div className="card-body py-4">
                                <div className="table-responsive">
                                    <table className="table align-middle table-row-dashed fs-6 gy-5">
                                        <thead>
                                            <tr className="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                                <th className="min-w-50px">ID</th>
                                                <th className="min-w-100px">Image</th>
                                                <th className="min-w-125px">Name</th>
                                                <th className="min-w-125px">SKU</th>
                                                <th className="min-w-100px">Price</th>
                                                <th className="min-w-100px">Quantity</th>
                                                <th className="min-w-100px">Status</th>
                                                <th className="text-end min-w-100px">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="text-gray-600 fw-semibold">
                                            {loading ? (
                                                <tr>
                                                    <td colSpan="8" className="text-center py-10">
                                                        <div className="d-flex flex-column align-items-center">
                                                            <div className="spinner-border text-primary mb-3" role="status">
                                                                <span className="visually-hidden">Loading...</span>
                                                            </div>
                                                            <span className="text-muted">Loading products...</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ) : products.length === 0 ? (
                                                <tr>
                                                    <td colSpan="8" className="text-center py-10">
                                                        <div className="d-flex flex-column align-items-center">
                                                            <i className="ki-duotone ki-file-deleted fs-5x text-muted mb-4">
                                                                <span className="path1"></span>
                                                                <span className="path2"></span>
                                                            </i>
                                                            <span className="fs-5 text-muted">No products found</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ) : (
                                                products.map(product => (
                                                    <tr key={product.id}>
                                                        <td>{product.id}</td>
                                                        <td>
                                                            <div className="symbol symbol-50px">
                                                                {product.thumbnail ? (
                                                                    <img src={product.thumbnail} alt={product.product_name} />
                                                                ) : (
                                                                    <div className="symbol-label fs-2 fw-semibold text-primary bg-light-primary">
                                                                        {product.product_name?.charAt(0).toUpperCase()}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div className="d-flex flex-column">
                                                                <span className="text-gray-800 text-hover-primary mb-1">
                                                                    {product.product_name}
                                                                </span>
                                                                {product.brand && (
                                                                    <span className="text-muted fs-7">
                                                                        {product.brand.name}
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </td>
                                                        <td>{product.sku}</td>
                                                        <td>${parseFloat(product.sale_price || 0).toFixed(2)}</td>
                                                        <td>
                                                            <span className={`badge badge-light-${product.quantity > 0 ? 'success' : 'danger'}`}>
                                                                {product.quantity || 0}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span className={`badge badge-light-${getStatusBadge(product.product_status)}`}>
                                                                {product.product_status || 'draft'}
                                                            </span>
                                                        </td>
                                                        <td className="text-end">
                                                            <a
                                                                href={`/merchant/sales/products/${product.id}/edit`}
                                                                className="btn btn-light btn-active-light-primary btn-sm me-2"
                                                            >
                                                                Edit
                                                            </a>
                                                            <button
                                                                className="btn btn-light btn-active-light-danger btn-sm"
                                                                onClick={() => handleDelete(product.id)}
                                                            >
                                                                Delete
                                                            </button>
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
                                            Showing {((currentPage - 1) * perPage) + 1} to {Math.min(currentPage * perPage, totalRecords)} of {totalRecords} products
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
            </div>

            {/* Upload Modal */}
            {showImportModal && (
                <div className="modal fade show d-block" tabIndex="-1" style={{backgroundColor: 'rgba(0,0,0,0.5)'}}>
                    <div className="modal-dialog modal-dialog-centered modal-lg">
                        <div className="modal-content">
                            <div className="modal-header">
                                <h5 className="modal-title">Import Products</h5>
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
                                <div className="alert alert-info d-flex align-items-start p-4 mb-5">
                                    <i className="ki-duotone ki-information fs-2 text-info me-3">
                                        <span className="path1"></span>
                                        <span className="path2"></span>
                                        <span className="path3"></span>
                                    </i>
                                    <div className="d-flex flex-column">
                                        <h5 className="mb-2 text-dark">Import Instructions</h5>
                                        <div className="fs-6">
                                            1. Download the template (includes reference sheets for categories, brands, taxes, etc.)<br/>
                                            2. Fill in your product data using exact names from reference sheets<br/>
                                            3. Upload and preview before importing
                                        </div>
                                    </div>
                                </div>

                                {/* Download Template Button */}
                                <div className="mb-5">
                                    <button 
                                        onClick={handleDownloadTemplate}
                                        className="btn btn-primary w-100"
                                    >
                                        <i className="ki-duotone ki-file-down fs-2">
                                            <span className="path1"></span>
                                            <span className="path2"></span>
                                        </i>
                                        Download Import Template
                                    </button>
                                    <div className="text-muted text-center mt-2 fs-7">
                                        Includes reference sheets with categories, brands, and more
                                    </div>
                                </div>

                                {/* File Upload */}
                                <div className="mb-5">
                                    <label className="form-label">Select File (Excel)</label>
                                    <input 
                                        ref={fileInputRef}
                                        type="file" 
                                        className="form-control"
                                        accept=".xlsx,.xls"
                                        onChange={handleFileSelect}
                                        disabled={importing}
                                    />
                                </div>

                                {/* Preview Loading */}
                                {importing && (
                                    <div className="text-center py-5">
                                        <div className="spinner-border text-primary" role="status">
                                            <span className="visually-hidden">Loading...</span>
                                        </div>
                                        <p className="mt-3">Processing file...</p>
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
                                    disabled={importing}
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Preview Modal */}
            {showPreviewModal && importPreviewData && (
                <div className="modal fade show d-block" tabIndex="-1" style={{backgroundColor: 'rgba(0,0,0,0.5)'}}>
                    <div className="modal-dialog modal-dialog-centered modal-xl">
                        <div className="modal-content">
                            <div className="modal-header">
                                <h5 className="modal-title">
                                    📋 Preview Import Data - Validation Results
                                </h5>
                                <button 
                                    type="button" 
                                    className="btn-close" 
                                    onClick={() => {
                                        setShowPreviewModal(false);
                                        setImportFile(null);
                                        setImportPreviewData(null);
                                    }}
                                ></button>
                            </div>
                            <div className="modal-body">
                                {/* Summary Section */}
                                {importPreviewData.summary && (
                                    <div className={`alert ${
                                        importPreviewData.summary.total_rows === 0 ? 'alert-info' :
                                        importPreviewData.summary.can_import ? 'alert-success' : 'alert-warning'
                                    } mb-4`}>
                                        <div className="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h6 className="mb-0">
                                                    <i className="ki-duotone ki-chart-simple fs-2 me-2">
                                                        <span className="path1"></span>
                                                        <span className="path2"></span>
                                                        <span className="path3"></span>
                                                        <span className="path4"></span>
                                                    </i>
                                                    Summary: {importPreviewData.products?.length || 0} rows found, {importPreviewData.summary.total_rows} actual products
                                                </h6>
                                            </div>
                                            <div className="d-flex gap-2">
                                                <span className="badge badge-success">
                                                    ✅ Valid: {importPreviewData.summary.valid_count}
                                                </span>
                                                {importPreviewData.summary.invalid_count > 0 && (
                                                    <span className="badge badge-danger">
                                                        ❌ Invalid: {importPreviewData.summary.invalid_count}
                                                    </span>
                                                )}
                                                {importPreviewData.products?.filter(p => ['sample', 'instruction', 'empty'].includes(p.row_type)).length > 0 && (
                                                    <span className="badge badge-secondary">
                                                        ⊘ Skipped: {importPreviewData.products.filter(p => ['sample', 'instruction', 'empty'].includes(p.row_type)).length}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                        <hr className="my-3" />
                                        <div>
                                            {importPreviewData.summary.total_rows === 0 ? (
                                                <p className="mb-0">
                                                    <i className="ki-duotone ki-information-2 fs-2 me-2">
                                                        <span className="path1"></span>
                                                        <span className="path2"></span>
                                                        <span className="path3"></span>
                                                    </i>
                                                    No actual products found. This file contains only sample data or instructions. Please add your own products to the Excel template and upload again.
                                                </p>
                                            ) : importPreviewData.summary.can_import ? (
                                                <p className="mb-0 text-success fw-bold">
                                                    <i className="ki-duotone ki-check-circle fs-2 me-2">
                                                        <span className="path1"></span>
                                                        <span className="path2"></span>
                                                    </i>
                                                    All {importPreviewData.summary.total_rows} products are valid and ready to import!
                                                </p>
                                            ) : (
                                                <p className="mb-0 text-danger fw-bold">
                                                    <i className="ki-duotone ki-cross-circle fs-2 me-2">
                                                        <span className="path1"></span>
                                                        <span className="path2"></span>
                                                    </i>
                                                    {importPreviewData.summary.invalid_count} of {importPreviewData.summary.total_rows} products have errors. Please review below.
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                )}
                                
                                {/* Legend */}
                                <div className="alert alert-light py-2 mb-4">
                                    <div className="d-flex justify-content-around align-items-center">
                                        <div className="small"><span className="badge badge-success">✅</span> Valid - Will be imported</div>
                                        <div className="small"><span className="badge badge-danger">❌</span> Invalid - Has errors</div>
                                        <div className="small"><span className="badge badge-warning">📝</span> Sample data - Skipped</div>
                                        <div className="small"><span className="badge badge-info">ℹ️</span> Instruction - Skipped</div>
                                        <div className="small"><span className="badge badge-secondary">⊘</span> Empty - Skipped</div>
                                    </div>
                                </div>
                                <div className="table-responsive" style={{maxHeight: '500px', overflowY: 'auto'}}>
                                    <table className="table table-row-bordered table-hover align-middle">
                                        <thead className="table-light sticky-top">
                                            <tr className="fw-bold text-gray-700">
                                                <th className="min-w-200px">Product Name</th>
                                                <th className="min-w-100px">SKU</th>
                                                <th className="min-w-80px">Type</th>
                                                <th className="min-w-80px">Status</th>
                                                <th className="min-w-80px">Base Price</th>
                                                <th className="min-w-80px">Sale Price</th>
                                                <th className="min-w-60px">Qty</th>
                                                <th className="min-w-100px">Brand</th>
                                                <th className="min-w-100px text-center">Is Valid?</th>
                                                <th className="min-w-250px">Validation Errors</th>
                                            </tr>
                                        </thead>
                                        <tbody className="fw-semibold text-gray-600">
                                            {importPreviewData.products?.map((row, idx) => {
                                                // Determine row background based on row type
                                                let rowBgClass = '';
                                                if (row.row_type === 'instruction') {
                                                    rowBgClass = 'bg-light-info';
                                                } else if (row.row_type === 'sample') {
                                                    rowBgClass = 'bg-light-warning';
                                                } else if (row.row_type === 'empty') {
                                                    rowBgClass = 'bg-light-secondary';
                                                } else if (!row.is_valid) {
                                                    rowBgClass = 'bg-light-danger';
                                                }
                                                
                                                return (
                                                    <tr key={idx} className={rowBgClass}>
                                                        <td>
                                                            <div className="d-flex align-items-center">
                                                                <div className="symbol symbol-40px me-3">
                                                                    <div className={`symbol-label fs-3 fw-semibold ${
                                                                        row.is_valid && row.will_be_imported ? 'text-success bg-light-success' :
                                                                        row.row_type === 'product' && !row.is_valid ? 'text-danger bg-light-danger' :
                                                                        'text-secondary bg-light-secondary'
                                                                    }`}>
                                                                        {row.product_name?.charAt(0).toUpperCase() || 'P'}
                                                                    </div>
                                                                </div>
                                                                <div className="d-flex flex-column">
                                                                    <span className="text-gray-800 fw-bold">
                                                                        {row.product_name || <span className="text-muted fst-italic">Empty</span>}
                                                                    </span>
                                                                    <span className="text-muted fs-7">
                                                                        Row {row.row_number}
                                                                        {row.row_type !== 'product' && (
                                                                            <span className="ms-2 badge badge-light-secondary badge-sm">
                                                                                {row.row_type}
                                                                            </span>
                                                                        )}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span className="badge badge-light-primary">{row.sku || '-'}</span>
                                                        </td>
                                                        <td className="small">{row.product_type || '-'}</td>
                                                        <td>
                                                            <span className={`badge badge-light-${
                                                                row.product_status === 'published' ? 'success' :
                                                                row.product_status === 'draft' ? 'warning' : 
                                                                row.product_status === 'inactive' ? 'danger' : 'info'
                                                            }`}>
                                                                {row.product_status || '-'}
                                                            </span>
                                                        </td>
                                                        <td className="small">${parseFloat(row.base_price || 0).toFixed(2)}</td>
                                                        <td className="text-success fw-bold small">${parseFloat(row.sale_price || 0).toFixed(2)}</td>
                                                        <td className="text-center">
                                                            <span className={`badge badge-${row.quantity > 0 ? 'success' : 'secondary'}`}>
                                                                {row.quantity || 0}
                                                            </span>
                                                        </td>
                                                        <td className="small">{row.brand || '-'}</td>
                                                        
                                                        {/* Is Valid Column */}
                                                        <td className="text-center">
                                                            {row.is_valid && row.will_be_imported ? (
                                                                <div>
                                                                    <i className="ki-duotone ki-check-circle fs-2x text-success">
                                                                        <span className="path1"></span>
                                                                        <span className="path2"></span>
                                                                    </i>
                                                                    <div className="fs-8 text-success mt-1 fw-bold">✓ VALID</div>
                                                                </div>
                                                            ) : row.row_type === 'product' && !row.is_valid ? (
                                                                <div>
                                                                    <i className="ki-duotone ki-cross-circle fs-2x text-danger">
                                                                        <span className="path1"></span>
                                                                        <span className="path2"></span>
                                                                    </i>
                                                                    <div className="fs-8 text-danger mt-1 fw-bold">✕ INVALID</div>
                                                                </div>
                                                            ) : (
                                                                <div>
                                                                    <i className="ki-duotone ki-information-2 fs-2x text-secondary">
                                                                        <span className="path1"></span>
                                                                        <span className="path2"></span>
                                                                        <span className="path3"></span>
                                                                    </i>
                                                                    <div className="fs-8 text-muted mt-1">SKIP</div>
                                                                </div>
                                                            )}
                                                        </td>
                                                        
                                                        {/* Validation Errors Column */}
                                                        <td>
                                                            {row.is_valid && row.will_be_imported ? (
                                                                <div className="d-flex align-items-center">
                                                                    <i className="ki-duotone ki-check-circle fs-2 text-success me-2">
                                                                        <span className="path1"></span>
                                                                        <span className="path2"></span>
                                                                    </i>
                                                                    <span className="text-success fw-bold">Ready to import</span>
                                                                </div>
                                                            ) : row.validation_errors && row.validation_errors.length > 0 ? (
                                                                <div>
                                                                    {row.validation_errors.map((error, errorIdx) => (
                                                                        <div 
                                                                            key={errorIdx}
                                                                            className={`alert py-2 px-3 mb-2 ${
                                                                                row.row_type === 'product' ? 'alert-danger' : 'alert-danger'
                                                                            }`}
                                                                            style={{ fontSize: '12px', marginBottom: '5px' }}
                                                                        >
                                                                            <i className={`ki-duotone ${
                                                                                row.row_type === 'product' ? 'ki-cross-circle' : 'ki-information-2'
                                                                            } fs-3 me-2`}>
                                                                                <span className="path1"></span>
                                                                                <span className="path2"></span>
                                                                                {row.row_type !== 'product' && <span className="path3"></span>}
                                                                            </i>
                                                                            {error}
                                                                        </div>
                                                                    ))}
                                                                </div>
                                                            ) : (
                                                                <span className="text-muted fst-italic">-</span>
                                                            )}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div className="modal-footer">
                                <div className="d-flex justify-content-between w-100 align-items-center">
                                    <div>
                                        {importPreviewData.summary?.total_rows === 0 ? (
                                            <span className="text-info small">
                                                <i className="ki-duotone ki-information fs-3 me-1">
                                                    <span className="path1"></span>
                                                    <span className="path2"></span>
                                                    <span className="path3"></span>
                                                </i>
                                                No products to import. Add your products to the Excel file.
                                            </span>
                                        ) : !importPreviewData.summary?.can_import ? (
                                            <span className="text-danger small">
                                                <i className="ki-duotone ki-cross-circle fs-3 me-1">
                                                    <span className="path1"></span>
                                                    <span className="path2"></span>
                                                </i>
                                                Fix {importPreviewData.summary.invalid_count} invalid product(s) before importing
                                            </span>
                                        ) : null}
                                    </div>
                                    <div>
                                        <button 
                                            type="button" 
                                            className="btn btn-light me-2" 
                                            onClick={() => {
                                                setShowPreviewModal(false);
                                                setImportFile(null);
                                                setImportPreviewData(null);
                                            }}
                                            disabled={importing}
                                        >
                                            {importPreviewData.summary?.total_rows === 0 ? 'Close' : 'Cancel'}
                                        </button>
                                        <button 
                                            type="button" 
                                            className={`btn ${
                                                importPreviewData.summary?.can_import && importPreviewData.summary?.total_rows > 0 
                                                    ? 'btn-success' 
                                                    : 'btn-secondary'
                                            }`}
                                            onClick={handleConfirmImport}
                                            disabled={!importPreviewData.summary?.can_import || importing || importPreviewData.summary?.total_rows === 0}
                                        >
                                            {importing ? (
                                                <>
                                                    <span className="spinner-border spinner-border-sm me-2"></span>
                                                    Importing...
                                                </>
                                            ) : importPreviewData.summary?.total_rows === 0 ? (
                                                <>
                                                    <i className="ki-duotone ki-cross-circle fs-2 me-1">
                                                        <span className="path1"></span>
                                                        <span className="path2"></span>
                                                    </i>
                                                    No Products to Import
                                                </>
                                            ) : (
                                                <>
                                                    <i className="ki-duotone ki-check fs-2 me-1">
                                                        <span className="path1"></span>
                                                        <span className="path2"></span>
                                                    </i>
                                                    Confirm & Import {importPreviewData.summary?.valid_count || 0} Product{importPreviewData.summary?.valid_count !== 1 ? 's' : ''}
                                                </>
                                            )}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
