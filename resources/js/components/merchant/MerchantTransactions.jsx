import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import Swal from 'sweetalert2';
import { toast } from 'react-toastify';
import { useNavigate, useSearchParams } from 'react-router-dom';
import TransactionFilters from './TransactionFilters';
import TransactionStatistics from './TransactionStatistics';
import TransactionActions from './TransactionActions';

const MerchantTransactions = ({ merchantId: propMerchantId, initialType = null }) => {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    
    // Get merchantId from props, window config, data attribute, or localStorage
    const merchantId = propMerchantId || 
                       window.merchantTransactionsConfig?.merchantId || 
                       window.merchantAppConfig?.merchantId ||
                       document.getElementById('merchant-app-root')?.getAttribute('data-merchant-id') ||
                       JSON.parse(localStorage.getItem('user_data'))?.merchant_id;
    
    // Get API token from multiple sources
    const getApiToken = () => {
        return window.merchantTransactionsConfig?.apiToken ||
               window.merchantAppConfig?.apiToken ||
               document.getElementById('merchant-app-root')?.getAttribute('data-api-token') ||
               localStorage.getItem('jwt_token');
    };

    const [transactions, setTransactions] = useState([]);
    const [loading, setLoading] = useState(false);
    const [totalRows, setTotalRows] = useState(0);
    const [perPage, setPerPage] = useState(25);
    const [currentPage, setCurrentPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [statistics, setStatistics] = useState(null);

    // Configure axios with token
    useEffect(() => {
        const token = getApiToken();
        if (token) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        }
    }, []);
    
    // Get type from URL query parameter
    const urlType = searchParams.get('type') || initialType || '';
    
    // Filter states
    const [filters, setFilters] = useState({
        search: '',
        status: '',
        payment_type: '',
        terminal_id: '',
        start_date: '',
        end_date: '',
        type: urlType
    });
    
    // Update filters when URL type changes
    useEffect(() => {
        const newType = searchParams.get('type') || initialType || '';
        setFilters(prev => ({ ...prev, type: newType }));
    }, [searchParams, initialType]);
    
    const [showFilters, setShowFilters] = useState(false);

    // Fetch transactions data
    const fetchTransactions = useCallback(async (page = 1, size = perPage) => {
        setLoading(true);
        try {
            const token = getApiToken();
            const response = await axios.get(`/api/v1/merchant/transactions/data`, {
                params: {
                    merchant_id: merchantId,
                    page: page,
                    per_page: size,
                    ...filters
                },
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            setTransactions(response.data.data);
            setTotalRows(response.data.total);
            setLastPage(response.data.last_page);
        } catch (error) {
            console.error('Error fetching transactions:', error);
            toast.error('Failed to load transactions');
        } finally {
            setLoading(false);
        }
    }, [merchantId, perPage, filters]);

    // Fetch statistics
    const fetchStatistics = useCallback(async () => {
        try {
            const token = getApiToken();
            const response = await axios.get(`/api/v1/merchant/transactions/statistics`, {
                params: {
                    merchant_id: merchantId,
                    type: filters.type
                },
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            setStatistics(response.data);
        } catch (error) {
            console.error('Error fetching statistics:', error);
        }
    }, [merchantId, filters.type]);

    useEffect(() => {
        fetchTransactions(currentPage, perPage);
        fetchStatistics();
    }, [fetchTransactions, fetchStatistics, currentPage, perPage]);

    // Handle page change
    const handlePageChange = (page) => {
        if (page >= 1 && page <= lastPage) {
            setCurrentPage(page);
        }
    };

    // Handle rows per page change
    const handlePerRowsChange = (e) => {
        setPerPage(parseInt(e.target.value));
        setCurrentPage(1);
    };

    // Handle filter change
    const handleFilterChange = (newFilters) => {
        setFilters(prev => ({ ...prev, ...newFilters }));
        setCurrentPage(1); // Reset to first page on filter change
    };

    // Clear all filters
    const clearFilters = () => {
        setFilters({
            search: '',
            status: '',
            payment_type: '',
            terminal_id: '',
            start_date: '',
            end_date: '',
            type: initialType || ''
        });
        setCurrentPage(1);
    };

    // Handle export
    const handleExport = async () => {
        const result = await Swal.fire({
            title: 'Export Transactions',
            text: 'Export transactions with current filters applied?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Export',
            cancelButtonText: 'Cancel'
        });

        if (result.isConfirmed) {
            try {
                const token = getApiToken();
                const params = new URLSearchParams({
                    merchant_id: merchantId,
                    ...filters
                }).toString();

                const response = await axios.get(`/api/v1/merchant/transactions/export?${params}`, {
                    responseType: 'blob',
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });

                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `transactions_${new Date().toISOString().slice(0, 10)}.csv`);
                document.body.appendChild(link);
                link.click();
                link.remove();

                toast.success('Export completed successfully');
            } catch (error) {
                console.error('Export error:', error);
                toast.error('Failed to export transactions');
            }
        }
    };

    // Handle view transaction
    const handleView = (transaction) => {
        navigate(`/merchant/transactions/${transaction.id}`);
    };

    // Handle void transaction
    const handleVoid = async (transaction) => {
        const { value: reason } = await Swal.fire({
            title: 'Void Transaction',
            input: 'textarea',
            inputLabel: 'Reason for void',
            inputPlaceholder: 'Enter reason...',
            inputAttributes: {
                'aria-label': 'Enter reason for voiding this transaction'
            },
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Void',
            cancelButtonText: 'Cancel',
            inputValidator: (value) => {
                if (!value) {
                    return 'You need to provide a reason!';
                }
            }
        });

        if (reason) {
            try {
                const token = getApiToken();
                await axios.post(`/api/v1/merchant/transactions/${transaction.id}/void`, 
                    { reason },
                    {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    }
                );
                toast.success('Transaction voided successfully');
                fetchTransactions(currentPage, perPage);
                fetchStatistics();
            } catch (error) {
                console.error('Void error:', error);
                toast.error('Failed to void transaction');
            }
        }
    };

    // Handle refund transaction
    const handleRefund = async (transaction) => {
        const { value: formValues } = await Swal.fire({
            title: 'Refund Transaction',
            html:
                `<input id="swal-input1" class="swal2-input" type="number" placeholder="Amount" max="${transaction.refundable_amount || transaction.amount}" step="0.01">` +
                '<textarea id="swal-input2" class="swal2-textarea" placeholder="Reason for refund"></textarea>',
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Refund',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const amount = document.getElementById('swal-input1').value;
                const reason = document.getElementById('swal-input2').value;
                
                if (!amount || amount <= 0) {
                    Swal.showValidationMessage('Please enter a valid amount');
                    return false;
                }
                
                if (!reason) {
                    Swal.showValidationMessage('Please enter a reason');
                    return false;
                }
                
                if (parseFloat(amount) > (transaction.refundable_amount || transaction.amount)) {
                    Swal.showValidationMessage('Amount exceeds refundable amount');
                    return false;
                }
                
                return { amount, reason };
            }
        });

        if (formValues) {
            try {
                const token = getApiToken();
                await axios.post(`/api/v1/merchant/transactions/${transaction.id}/refund`, 
                    formValues,
                    {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    }
                );
                toast.success('Refund initiated successfully');
                fetchTransactions(currentPage, perPage);
                fetchStatistics();
            } catch (error) {
                console.error('Refund error:', error);
                toast.error('Failed to process refund');
            }
        }
    };

    // Handle send receipt
    const handleSendReceipt = async (transaction) => {
        const { value: formValues } = await Swal.fire({
            title: 'Send Receipt',
            html:
                '<input id="swal-email" class="swal2-input" type="email" placeholder="Email address">' +
                '<textarea id="swal-message" class="swal2-textarea" placeholder="Optional message"></textarea>',
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Send',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const email = document.getElementById('swal-email').value;
                const message = document.getElementById('swal-message').value;
                
                if (!email) {
                    Swal.showValidationMessage('Please enter an email address');
                    return false;
                }
                
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    Swal.showValidationMessage('Please enter a valid email address');
                    return false;
                }
                
                return { email, message };
            }
        });

        if (formValues) {
            try {
                const token = getApiToken();
                await axios.post(`/api/v1/merchant/transactions/${transaction.id}/send-receipt`, 
                    formValues,
                    {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    }
                );
                toast.success('Receipt sent successfully');
            } catch (error) {
                console.error('Send receipt error:', error);
                toast.error('Failed to send receipt');
            }
        }
    };

    // Get status badge color
    const getStatusColor = (status) => {
        const statusColors = {
            'APPROVED': 'success',
            'DECLINED': 'danger',
            'PENDING': 'warning',
            'CAPTURED': 'info',
            'VOIDED': 'dark',
            'REFUNDED': 'secondary'
        };
        return statusColors[status] || 'secondary';
    };

    // Generate pagination numbers
    const getPaginationNumbers = () => {
        const pages = [];
        const maxVisible = 5;
        
        if (lastPage <= maxVisible) {
            for (let i = 1; i <= lastPage; i++) {
                pages.push(i);
            }
        } else {
            if (currentPage <= 3) {
                for (let i = 1; i <= 4; i++) pages.push(i);
                pages.push('...');
                pages.push(lastPage);
            } else if (currentPage >= lastPage - 2) {
                pages.push(1);
                pages.push('...');
                for (let i = lastPage - 3; i <= lastPage; i++) pages.push(i);
            } else {
                pages.push(1);
                pages.push('...');
                for (let i = currentPage - 1; i <= currentPage + 1; i++) pages.push(i);
                pages.push('...');
                pages.push(lastPage);
            }
        }
        
        return pages;
    };

    return (
        <>
            <style>{`
                .skeleton {
                    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                    background-size: 200% 100%;
                    animation: skeleton-loading 1.5s ease-in-out infinite;
                    border-radius: 4px;
                }
                
                @keyframes skeleton-loading {
                    0% {
                        background-position: 200% 0;
                    }
                    100% {
                        background-position: -200% 0;
                    }
                }
                
                .skeleton-text {
                    display: inline-block;
                }
                
                .skeleton-badge {
                    display: inline-block;
                }
                
                .skeleton-button {
                    display: inline-block;
                }
            `}</style>
            <div className="post d-flex flex-column-fluid">
                <div className="container-xxl">
                    {/* Toolbar - Top */}
                    <div className="d-flex flex-wrap flex-stack mb-6">
                    <h3 className="fw-bolder my-2">
                        Transactions
                        <span className="fs-6 text-gray-400 fw-bold ms-1">({totalRows} total)</span>
                    </h3>
                    
                    <div className="d-flex align-items-center gap-2 my-2">
                        {/* Filter Button */}
                        <button
                            className="btn btn-sm btn-flex btn-secondary fw-bold"
                            onClick={() => setShowFilters(!showFilters)}
                        >
                            <i className="ki-duotone ki-filter fs-6 text-muted me-1">
                                <span className="path1"></span>
                                <span className="path2"></span>
                            </i>
                            Filter
                        </button>

                        {/* Refresh Button */}
                        <button
                            className="btn btn-sm btn-flex btn-light fw-bold"
                            onClick={() => fetchTransactions(currentPage, perPage)}
                            disabled={loading}
                        >
                            <i className="ki-duotone ki-arrows-circle fs-6 text-muted me-1">
                                <span className="path1"></span>
                                <span className="path2"></span>
                            </i>
                            Refresh
                        </button>

                        {/* Export Button */}
                        <button
                            className="btn btn-sm fw-bold btn-success"
                            onClick={handleExport}
                            disabled={loading}
                        >
                            <i className="ki-duotone ki-file-down fs-3">
                                <span className="path1"></span>
                                <span className="path2"></span>
                            </i>
                            Export
                        </button>
                    </div>
                </div>

                {/* Filters */}
                {showFilters && (
                    <TransactionFilters
                        filters={filters}
                        onFilterChange={handleFilterChange}
                        onClearFilters={clearFilters}
                    />
                )}

                {/* Type Alert */}
                {filters.type && (
                    <div className="row g-5 g-xl-8 mb-5">
                        <div className="col-md-12">
                            <div className="alert alert-info d-flex align-items-center p-5">
                                <i className="ki-duotone ki-information fs-2hx text-info me-4">
                                    <span className="path1"></span>
                                    <span className="path2"></span>
                                    <span className="path3"></span>
                                </i>
                                <div className="d-flex flex-column">
                                    <h4 className="mb-1 text-capitalize">{filters.type} Transactions</h4>
                                    <span>Showing transactions with type: {filters.type}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Statistics - After Toolbar */}
                {!filters.type && (
                    loading && !statistics ? (
                        // Skeleton Loading for Statistics
                        <div className="row gy-5 g-xl-10 mb-5">
                            {[1, 2, 3].map((item) => (
                                <div key={item} className="col-xl-4 mb-xl-10">
                                    <div className="card card-flush h-xl-100">
                                        <div className="card-header pt-5">
                                            <h3 className="card-title align-items-start flex-column">
                                                <div className="skeleton" style={{width: '180px', height: '20px', marginBottom: '8px'}}></div>
                                                <div className="skeleton" style={{width: '140px', height: '14px'}}></div>
                                            </h3>
                                        </div>
                                        <div className="card-body pt-2 row">
                                            <div className="mb-2 col-6">
                                                <div className="skeleton" style={{width: '100px', height: '48px'}}></div>
                                            </div>
                                            <div className="mb-2 col-6 d-flex justify-content-center align-items-center">
                                                <div className="skeleton" style={{width: '120px', height: '36px'}}></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : statistics ? (
                        <TransactionStatistics statistics={statistics} />
                    ) : null
                )}

                {/* Table */}
                <div className="card">
                    <div className="card-body pt-0">
                        <div className="table-responsive">
                            <table className="table align-middle table-row-dashed fs-7 gy-5" id="transactions-table">
                                <thead>
                                    <tr className="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                        <th className="text-dark">Transaction ID</th>
                                        <th className="text-dark">Card Number</th>
                                        <th className="text-dark">Amount</th>
                                        <th className="text-dark">Batch No</th>
                                        <th className="text-dark">SDK</th>
                                        <th className="text-dark">Created Time</th>
                                        <th className="text-dark">Payment Channel</th>
                                        <th className="text-dark">Status</th>
                                        <th className="text-end text-dark">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {loading ? (
                                        // Skeleton Loading Rows
                                        [...Array(perPage)].map((_, index) => (
                                            <tr key={`skeleton-${index}`}>
                                                <td>
                                                    <div className="skeleton skeleton-text" style={{width: '120px', height: '16px'}}></div>
                                                </td>
                                                <td>
                                                    <div className="skeleton skeleton-text" style={{width: '150px', height: '16px'}}></div>
                                                </td>
                                                <td>
                                                    <div className="skeleton skeleton-text" style={{width: '80px', height: '16px'}}></div>
                                                </td>
                                                <td>
                                                    <div className="skeleton skeleton-text" style={{width: '60px', height: '16px'}}></div>
                                                </td>
                                                <td>
                                                    <div className="skeleton skeleton-text" style={{width: '60px', height: '16px'}}></div>
                                                </td>
                                                <td>
                                                    <div className="skeleton skeleton-text" style={{width: '140px', height: '16px'}}></div>
                                                </td>
                                                <td>
                                                    <div className="skeleton skeleton-text" style={{width: '80px', height: '16px'}}></div>
                                                </td>
                                                <td>
                                                    <div className="skeleton skeleton-badge" style={{width: '80px', height: '24px', borderRadius: '6px'}}></div>
                                                </td>
                                                <td className="text-end">
                                                    <div className="skeleton skeleton-button" style={{width: '70px', height: '32px', borderRadius: '6px', marginLeft: 'auto'}}></div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : transactions.length === 0 ? (
                                        <tr>
                                            <td colSpan="9" className="text-center py-5">
                                                <div className="text-gray-500">
                                                    <i className="ki-duotone ki-file fs-3x mb-3">
                                                        <span className="path1"></span>
                                                        <span className="path2"></span>
                                                    </i>
                                                    <p className="fw-bold">No transactions found</p>
                                                </div>
                                            </td>
                                        </tr>
                                    ) : (
                                        transactions.map((transaction) => (
                                            <tr key={transaction.id}>
                                                <td>{transaction.transaction_id || 'N/A'}</td>
                                                <td>
                                                    {transaction.card_number 
                                                        ? `**** **** **** ${transaction.card_number.slice(-4)}` 
                                                        : 'N/A'}
                                                </td>
                                                <td>
                                                    {transaction.currency?.symbol || '$'} {parseFloat(transaction.amount).toFixed(2)}
                                                </td>
                                                <td>{transaction.batch_no || 'N/A'}</td>
                                                <td>{transaction.sdk_id || 'N/A'}</td>
                                                <td>{new Date(transaction.created_at).toLocaleString()}</td>
                                                <td>
                                                    <span className="badge badge-light-primary">
                                                        {transaction.payment_type || 'N/A'}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span className={`badge badge-light-${getStatusColor(transaction.status)}`}>
                                                        {transaction.status}
                                                    </span>
                                                </td>
                                                <td className="text-end">
                                                    <TransactionActions
                                                        transaction={transaction}
                                                        onView={handleView}
                                                    />
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {!loading && transactions.length > 0 && (
                            <div className="row mt-5">
                                <div className="col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start">
                                    <div className="dataTables_length">
                                        <label className="d-flex align-items-center">
                                            <span className="me-2">Show</span>
                                            <select 
                                                className="form-select form-select-sm" 
                                                value={perPage}
                                                onChange={handlePerRowsChange}
                                                style={{ width: '75px' }}
                                            >
                                                <option value="10">10</option>
                                                <option value="25">25</option>
                                                <option value="50">50</option>
                                                <option value="100">100</option>
                                            </select>
                                            <span className="ms-2">entries</span>
                                        </label>
                                    </div>
                                    <div className="ms-5">
                                        <span className="text-muted">
                                            Showing {((currentPage - 1) * perPage) + 1} to {Math.min(currentPage * perPage, totalRows)} of {totalRows} entries
                                        </span>
                                    </div>
                                </div>
                                <div className="col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end">
                                    <div className="dataTables_paginate">
                                        <ul className="pagination">
                                            <li className={`page-item ${currentPage === 1 ? 'disabled' : ''}`}>
                                                <button 
                                                    className="page-link" 
                                                    onClick={() => handlePageChange(currentPage - 1)}
                                                    disabled={currentPage === 1}
                                                >
                                                    Previous
                                                </button>
                                            </li>
                                            {getPaginationNumbers().map((page, index) => (
                                                page === '...' ? (
                                                    <li key={`ellipsis-${index}`} className="page-item disabled">
                                                        <span className="page-link">...</span>
                                                    </li>
                                                ) : (
                                                    <li key={page} className={`page-item ${currentPage === page ? 'active' : ''}`}>
                                                        <button 
                                                            className="page-link" 
                                                            onClick={() => handlePageChange(page)}
                                                        >
                                                            {page}
                                                        </button>
                                                    </li>
                                                )
                                            ))}
                                            <li className={`page-item ${currentPage === lastPage ? 'disabled' : ''}`}>
                                                <button 
                                                    className="page-link" 
                                                    onClick={() => handlePageChange(currentPage + 1)}
                                                    disabled={currentPage === lastPage}
                                                >
                                                    Next
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
                {/* End Table Card */}
            </div>
            {/* End Container */}
        </div>
        {/* End Post */}
        </>
    );
};

export default MerchantTransactions;

