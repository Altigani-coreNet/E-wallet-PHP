import React, { useState, useEffect } from 'react';
import { get } from '../../utils/api';
import { API_ENDPOINTS } from '../../utils/constants';

export default function PurchaseReport() {
    const [purchases, setPurchases] = useState([]);
    const [summary, setSummary] = useState({
        total_purchases: 0,
        total_items: 0,
        total_paid: 0,
        total_amount: 0,
        total_due: 0,
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
        from_date: '',
        to_date: '',
        supplier_id: '',
        warehouse_id: '',
    });

    useEffect(() => {
        fetchPurchases();
        fetchSummary();
    }, [filters.from_date, filters.to_date, filters.supplier_id, filters.warehouse_id, pagination.current_page]);

    const fetchPurchases = async () => {
        try {
            setLoading(true);
            setError(null);
            const response = await get(API_ENDPOINTS.REPORTS.PURCHASES, {
                params: {
                    ...filters,
                    page: pagination.current_page,
                    per_page: pagination.per_page,
                }
            });

            if (response.status) {
                const purchasesData = response.data.data.data || [];
                const paginationData = response.data.data.pagination || pagination;
                
                setPurchases(purchasesData);
                setPagination(paginationData);
            } else {
                setError(response.data.message || 'Failed to load purchases');
            }
            setLoading(false);
        } catch (err) {
            console.error('Error fetching purchases:', err);
            setError(err.response?.data?.message || 'Failed to load purchases');
            setLoading(false);
        }
    };

    const fetchSummary = async () => {
        try {
            const response = await get(API_ENDPOINTS.REPORTS.PURCHASES_SUMMARY, {
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
            from_date: '',
            to_date: '',
            supplier_id: '',
            warehouse_id: '',
        });
        setPagination(prev => ({ ...prev, current_page: 1 }));
    };

    const handlePageChange = (page) => {
        setPagination(prev => ({ ...prev, current_page: page }));
    };

    const exportToCSV = () => {
        const headers = ['Reference', 'Date', 'Supplier', 'Warehouse', 'Total', 'Paid', 'Due', 'Status'];
        const rows = purchases.map(p => [
            p.reference_no,
            p.purchase_date,
            p.supplier,
            p.warehouse,
            p.grand_total,
            p.paid_amount,
            p.due,
            p.payment_status
        ]);

        const csvContent = [
            headers.join(','),
            ...rows.map(row => row.join(','))
        ].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `purchase-report-${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
    };

    const getPaymentStatusBadge = (status) => {
        const badges = {
            paid: 'badge bg-success',
            unpaid: 'badge bg-danger',
            partial: 'badge bg-warning'
        };
        return badges[status] || 'badge bg-secondary';
    };

    return (
        <div className="purchase-report">
            {/* Summary Cards */}
            {loading && purchases.length === 0 ? (
                <div className="row mb-4">
                    {[...Array(4)].map((_, index) => (
                        <div className="col-md-3" key={`skeleton-card-${index}`}>
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
                    ))}
                </div>
            ) : (
                <div className="row mb-4">
                    <div className="col-md-3">
                        <div className="card">
                            <div className="card-body">
                                <h6 className="text-muted mb-2">Total Purchases</h6>
                                <h4 className="mb-0">{summary.total_purchases}</h4>
                            </div>
                        </div>
                    </div>
                    <div className="col-md-3">
                        <div className="card">
                            <div className="card-body">
                                <h6 className="text-muted mb-2">Total Items</h6>
                                <h4 className="mb-0">{summary.total_items.toLocaleString()}</h4>
                            </div>
                        </div>
                    </div>
                    <div className="col-md-3">
                        <div className="card">
                            <div className="card-body">
                                <h6 className="text-muted mb-2">Total Paid</h6>
                                <h4 className="mb-0 text-success">${summary.total_paid.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</h4>
                            </div>
                        </div>
                    </div>
                    <div className="col-md-3">
                        <div className="card">
                            <div className="card-body">
                                <h6 className="text-muted mb-2">Total Due</h6>
                                <h4 className="mb-0 text-danger">${summary.total_due.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Filters */}
            <div className="card mb-4">
                <div className="card-body">
                    <div className="row g-3">
                        <div className="col-md-3">
                            <label className="form-label">From Date</label>
                            <input
                                type="date"
                                name="from_date"
                                className="form-control"
                                value={filters.from_date}
                                onChange={handleFilterChange}
                            />
                        </div>
                        <div className="col-md-3">
                            <label className="form-label">To Date</label>
                            <input
                                type="date"
                                name="to_date"
                                className="form-control"
                                value={filters.to_date}
                                onChange={handleFilterChange}
                            />
                        </div>
                        <div className="col-md-3">
                            <label className="form-label">Supplier</label>
                            <input
                                type="text"
                                name="supplier_id"
                                className="form-control"
                                placeholder="Supplier ID"
                                value={filters.supplier_id}
                                onChange={handleFilterChange}
                            />
                        </div>
                        <div className="col-md-3">
                            <label className="form-label">Warehouse</label>
                            <input
                                type="text"
                                name="warehouse_id"
                                className="form-control"
                                placeholder="Warehouse ID"
                                value={filters.warehouse_id}
                                onChange={handleFilterChange}
                            />
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
                                    <th>Reference</th>
                                    <th>Date</th>
                                    <th>Supplier</th>
                                    <th>Warehouse</th>
                                    <th>Total</th>
                                    <th>Paid</th>
                                    <th>Due</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {loading && purchases.length === 0 ? (
                                    // Skeleton rows
                                    [...Array(5)].map((_, index) => (
                                        <tr key={`skeleton-${index}`}>
                                            <td><span className="placeholder col-8"></span></td>
                                            <td><span className="placeholder col-9"></span></td>
                                            <td><span className="placeholder col-10"></span></td>
                                            <td><span className="placeholder col-10"></span></td>
                                            <td><span className="placeholder col-7"></span></td>
                                            <td><span className="placeholder col-7"></span></td>
                                            <td><span className="placeholder col-7"></span></td>
                                            <td><span className="placeholder col-6"></span></td>
                                        </tr>
                                    ))
                                ) : purchases.length > 0 ? (
                                    purchases.map(purchase => (
                                        <tr key={purchase.id}>
                                            <td>{purchase.reference_no}</td>
                                            <td>{purchase.purchase_date}</td>
                                            <td>{purchase.supplier}</td>
                                            <td>{purchase.warehouse}</td>
                                            <td>${purchase.grand_total.toFixed(2)}</td>
                                            <td>${purchase.paid_amount.toFixed(2)}</td>
                                            <td>${purchase.due.toFixed(2)}</td>
                                            <td>
                                                <span className={getPaymentStatusBadge(purchase.payment_status)}>
                                                    {purchase.payment_status}
                                                </span>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="8" className="text-center py-4">
                                            No purchases found
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

