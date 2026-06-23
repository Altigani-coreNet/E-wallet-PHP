import React, { useState, useEffect } from 'react';
import { get } from '../../utils/api';
import { API_ENDPOINTS } from '../../utils/constants';

export default function ExpensesReport() {
    const [expenses, setExpenses] = useState([]);
    const [summary, setSummary] = useState({
        total_expenses: 0,
        total_amount: 0,
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
    });

    useEffect(() => {
        fetchExpenses();
        fetchSummary();
    }, [filters.start_date, filters.end_date, pagination.current_page]);

    const fetchExpenses = async () => {
        try {
            setLoading(true);
            setError(null);
            const response = await get(API_ENDPOINTS.REPORTS.EXPENSES, {
                params: {
                    ...filters,
                    page: pagination.current_page,
                    per_page: pagination.per_page,
                }
            });

            if (response.status) {
                const expensesData = response.data.data.data || [];
                const paginationData = response.data.data.pagination || pagination;
                
                setExpenses(expensesData);
                setPagination(paginationData);
            } else {
                setError(response.data.message || 'Failed to load expenses');
            }
            setLoading(false);
        } catch (err) {
            console.error('Error fetching expenses:', err);
            setError(err.response?.data?.message || 'Failed to load expenses');
            setLoading(false);
        }
    };

    const fetchSummary = async () => {
        try {
            const response = await get(API_ENDPOINTS.REPORTS.EXPENSES_SUMMARY, {
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
        });
        setPagination(prev => ({ ...prev, current_page: 1 }));
    };

    const handlePageChange = (page) => {
        setPagination(prev => ({ ...prev, current_page: page }));
    };

    const exportToCSV = () => {
        const headers = ['Date', 'Category', 'Warehouse', 'Account', 'Amount', 'Reference', 'Note'];
        const rows = expenses.map(e => [
            e.created_at,
            e.expense_category,
            e.warehouse,
            e.account,
            e.amount,
            e.reference_no,
            e.note
        ]);

        const csvContent = [
            headers.join(','),
            ...rows.map(row => row.join(','))
        ].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `expenses-report-${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
    };

    return (
        <div className="expenses-report">
            {/* Summary Cards */}
            {loading && expenses.length === 0 ? (
                <div className="row mb-4">
                    {[...Array(2)].map((_, index) => (
                        <div className="col-md-6" key={`skeleton-card-${index}`}>
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
                    <div className="col-md-6">
                        <div className="card">
                            <div className="card-body">
                                <h6 className="text-muted mb-2">Total Expenses</h6>
                                <h4 className="mb-0">{summary.total_expenses}</h4>
                            </div>
                        </div>
                    </div>
                    <div className="col-md-6">
                        <div className="card">
                            <div className="card-body">
                                <h6 className="text-muted mb-2">Total Amount</h6>
                                <h4 className="mb-0 text-danger">${summary.total_amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Filters */}
            <div className="card mb-4">
                <div className="card-body">
                    <div className="row g-3">
                        <div className="col-md-5">
                            <label className="form-label">Start Date</label>
                            <input
                                type="date"
                                name="start_date"
                                className="form-control"
                                value={filters.start_date}
                                onChange={handleFilterChange}
                            />
                        </div>
                        <div className="col-md-5">
                            <label className="form-label">End Date</label>
                            <input
                                type="date"
                                name="end_date"
                                className="form-control"
                                value={filters.end_date}
                                onChange={handleFilterChange}
                            />
                        </div>
                        <div className="col-md-2 d-flex align-items-end">
                            <button className="btn btn-secondary w-100" onClick={handleClearFilters}>
                                <i className="bx bx-x me-1"></i> Clear
                            </button>
                        </div>
                        <div className="col-12">
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
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Warehouse</th>
                                    <th>Account</th>
                                    <th>Amount</th>
                                    <th>Reference</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                {loading && expenses.length === 0 ? (
                                    // Skeleton rows
                                    [...Array(5)].map((_, index) => (
                                        <tr key={`skeleton-${index}`}>
                                            <td><span className="placeholder col-9"></span></td>
                                            <td><span className="placeholder col-10"></span></td>
                                            <td><span className="placeholder col-10"></span></td>
                                            <td><span className="placeholder col-10"></span></td>
                                            <td><span className="placeholder col-7"></span></td>
                                            <td><span className="placeholder col-8"></span></td>
                                            <td><span className="placeholder col-11"></span></td>
                                        </tr>
                                    ))
                                ) : expenses.length > 0 ? (
                                    expenses.map(expense => (
                                        <tr key={expense.id}>
                                            <td>{expense.created_at}</td>
                                            <td>{expense.expense_category}</td>
                                            <td>{expense.warehouse}</td>
                                            <td>{expense.account}</td>
                                            <td>${expense.amount.toFixed(2)}</td>
                                            <td>{expense.reference_no}</td>
                                            <td>{expense.note}</td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="7" className="text-center py-4">
                                            No expenses found
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

