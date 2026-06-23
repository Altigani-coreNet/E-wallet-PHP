import React, { useState, useEffect } from 'react';
import { get } from '../../utils/api';
import { API_ENDPOINTS } from '../../utils/constants';

export default function ProductsReport() {
    const [products, setProducts] = useState([]);
    const [summary, setSummary] = useState({
        total_purchase_amount: 0,
        total_sale_amount: 0,
    });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [pagination, setPagination] = useState({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
    });

    // Filter states
    const [filters, setFilters] = useState({
        start_date: '',
        end_date: '',
        status: '',
    });

    useEffect(() => {
        fetchProducts();
        fetchSummary();
    }, [filters.start_date, filters.end_date, filters.status, pagination.current_page]);

    const fetchProducts = async () => {
        try {
            setLoading(true);
            setError(null);
            
            const response = await get(API_ENDPOINTS.REPORTS.PRODUCTS, {
                params: {
                    ...filters,
                    page: pagination.current_page,
                    per_page: pagination.per_page,
                }
            });

            if (response.status) {
                const productsData = response.data.data.data || [];
                const paginationData = response.data.data.pagination || pagination;
                
                setProducts(productsData);
                setPagination(paginationData);
            } else {
                setError(response.data.message || 'Failed to load products');
            }
            setLoading(false);
        } catch (err) {
            console.error('Error fetching products:', err);
            setError(err.response?.data?.message || 'Failed to load products');
            setLoading(false);
        }
    };

    const fetchSummary = async () => {
        try {
            const response = await get(API_ENDPOINTS.REPORTS.PRODUCTS_SUMMARY, {
                params: filters
            });

            if (response.data.status) {
                setSummary(response.data.data);
            }
        } catch (err) {
            console.error('Error fetching summary:', err);
        }
    };

    const handleFilterChange = (e) => {
        const { name, value } = e.target;
        setFilters(prev => ({ ...prev, [name]: value }));
        setPagination(prev => ({ ...prev, current_page: 1 }));
    };

    const handleClearFilters = () => {
        setFilters({
            start_date: '',
            end_date: '',
            status: '',
        });
        setPagination(prev => ({ ...prev, current_page: 1 }));
    };

    const handlePageChange = (page) => {
        setPagination(prev => ({ ...prev, current_page: page }));
    };

    const exportToCSV = () => {
        const headers = ['Product Name', 'SKU', 'Purchase Qty', 'Purchase Amount', 'Sale Qty', 'Sale Amount', 'Returns'];
        const rows = products.map(p => [
            p.product_name,
            p.sku,
            p.total_purchase_qty,
            p.total_purchase_price,
            p.total_sale_qty,
            p.total_sale_price,
            p.total_sale_returns
        ]);

        const csvContent = [
            headers.join(','),
            ...rows.map(row => row.join(','))
        ].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `products-report-${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
    };

    return (
        <div className="products-report">
            {/* Summary Cards */}
            {loading && products.length === 0 ? (
                <div className="row mb-4">
                    <div className="col-md-6">
                        <div className="card">
                            <div className="card-body">
                                <div className="placeholder-glow">
                                    <span className="placeholder col-6"></span>
                                    <h4 className="mb-0 mt-2">
                                        <span className="placeholder col-4"></span>
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="col-md-6">
                        <div className="card">
                            <div className="card-body">
                                <div className="placeholder-glow">
                                    <span className="placeholder col-6"></span>
                                    <h4 className="mb-0 mt-2">
                                        <span className="placeholder col-4"></span>
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            ) : (
                <div className="row mb-4">
                    <div className="col-md-6">
                        <div className="card">
                            <div className="card-body">
                                <h6 className="text-muted mb-2">Total Purchase Amount</h6>
                                <h4 className="mb-0 text-primary">${summary.total_purchase_amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</h4>
                            </div>
                        </div>
                    </div>
                    <div className="col-md-6">
                        <div className="card">
                            <div className="card-body">
                                <h6 className="text-muted mb-2">Total Sale Amount</h6>
                                <h4 className="mb-0 text-success">${summary.total_sale_amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Filters */}
            <div className="card mb-4">
                <div className="card-body">
                    <div className="row g-3">
                        <div className="col-md-4">
                            <label className="form-label">Start Date</label>
                            <input
                                type="date"
                                name="start_date"
                                className="form-control"
                                value={filters.start_date}
                                onChange={handleFilterChange}
                            />
                        </div>
                        <div className="col-md-4">
                            <label className="form-label">End Date</label>
                            <input
                                type="date"
                                name="end_date"
                                className="form-control"
                                value={filters.end_date}
                                onChange={handleFilterChange}
                            />
                        </div>
                        <div className="col-md-4">
                            <label className="form-label">Status</label>
                            <select
                                name="status"
                                className="form-select"
                                value={filters.status}
                                onChange={handleFilterChange}
                            >
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div className="col-12">
                            <button className="btn btn-secondary me-2" onClick={handleClearFilters}>
                                <i className="bx bx-x me-1"></i> Clear Filters
                            </button>
                            <button className="btn btn-success" onClick={exportToCSV}>
                                <i className="bx bx-download me-1"></i> Export CSV
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Data Table */}
            <div className="card">
                <div className="card-body">
                    {error && (
                        <div className="alert alert-danger" role="alert">
                            {error}
                        </div>
                    )}
                    
                    <div className="table-responsive">
                        <table className="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>SKU</th>
                                    <th>Purchase Qty</th>
                                    <th>Purchase Amount</th>
                                    <th>Sale Qty</th>
                                    <th>Sale Amount</th>
                                    <th>Returns</th>
                                </tr>
                            </thead>
                            <tbody>
                                {loading && products.length === 0 ? (
                                    // Skeleton rows
                                    [...Array(5)].map((_, index) => (
                                        <tr key={`skeleton-${index}`}>
                                            <td><span className="placeholder col-10"></span></td>
                                            <td><span className="placeholder col-8"></span></td>
                                            <td><span className="placeholder col-6"></span></td>
                                            <td><span className="placeholder col-7"></span></td>
                                            <td><span className="placeholder col-6"></span></td>
                                            <td><span className="placeholder col-7"></span></td>
                                            <td><span className="placeholder col-6"></span></td>
                                        </tr>
                                    ))
                                ) : products.length > 0 ? (
                                    products.map(product => (
                                        <tr key={product.id}>
                                            <td>{product.product_name}</td>
                                            <td>{product.sku}</td>
                                            <td>{product.total_purchase_qty.toFixed(2)}</td>
                                            <td>${product.total_purchase_price.toFixed(2)}</td>
                                            <td>{product.total_sale_qty.toFixed(2)}</td>
                                            <td>${product.total_sale_price.toFixed(2)}</td>
                                            <td>{product.total_sale_returns.toFixed(2)}</td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="7" className="text-center py-4">
                                            No products found
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {pagination.last_page > 1 && (
                        <nav className="mt-4">
                            <ul className="pagination justify-content-center">
                                <li className={`page-item ${pagination.current_page === 1 ? 'disabled' : ''}`}>
                                    <button
                                        className="page-link"
                                        onClick={() => handlePageChange(pagination.current_page - 1)}
                                        disabled={pagination.current_page === 1}
                                    >
                                        Previous
                                    </button>
                                </li>
                                {[...Array(pagination.last_page)].map((_, index) => (
                                    <li
                                        key={index + 1}
                                        className={`page-item ${pagination.current_page === index + 1 ? 'active' : ''}`}
                                    >
                                        <button
                                            className="page-link"
                                            onClick={() => handlePageChange(index + 1)}
                                        >
                                            {index + 1}
                                        </button>
                                    </li>
                                ))}
                                <li className={`page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}`}>
                                    <button
                                        className="page-link"
                                        onClick={() => handlePageChange(pagination.current_page + 1)}
                                        disabled={pagination.current_page === pagination.last_page}
                                    >
                                        Next
                                    </button>
                                </li>
                            </ul>
                        </nav>
                    )}
                </div>
            </div>
        </div>
    );
}

