import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { toast } from 'react-toastify';
import { useNavigate } from 'react-router-dom';
import BatchFilters from './BatchFilters';
import BatchStatistics from './BatchStatistics';
import BatchActions from './BatchActions';

const MerchantBatches = () => {
    const navigate = useNavigate();
    
    // Get merchantId from multiple sources
    const merchantId = window.merchantAppConfig?.merchantId ||
                       document.getElementById('merchant-app-root')?.getAttribute('data-merchant-id') ||
                       JSON.parse(localStorage.getItem('user_data'))?.merchant_id;
    
    // Get API base URL
    const getApiBaseUrl = () => {
        return window.merchantAppConfig?.apiBaseUrl || '/api/v1/merchant';
    };
    
    // Get API token from multiple sources
    const getApiToken = () => {
        return window.merchantAppConfig?.apiToken ||
               document.getElementById('merchant-app-root')?.getAttribute('data-api-token') ||
               localStorage.getItem('jwt_token');
    };

    const [batches, setBatches] = useState([]);
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
    
    // Filter states
    const [filters, setFilters] = useState({
        search: '',
        status: ''
    });
    
    const [showFilters, setShowFilters] = useState(false);

    // Fetch batches data
    const fetchBatches = useCallback(async (page = 1, size = perPage) => {
        setLoading(true);
        try {
            const token = getApiToken();
            const baseUrl = getApiBaseUrl();
            const response = await axios.get(`${baseUrl}/batches/data`, {
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

            setBatches(response.data.data);
            setTotalRows(response.data.total);
            setLastPage(response.data.last_page);
        } catch (error) {
            console.error('Error fetching batches:', error);
            toast.error('Failed to load batches');
        } finally {
            setLoading(false);
        }
    }, [merchantId, perPage, filters]);

    // Fetch statistics
    const fetchStatistics = useCallback(async () => {
        try {
            const token = getApiToken();
            const baseUrl = getApiBaseUrl();
            const response = await axios.get(`${baseUrl}/batches/statistics`, {
                params: {
                    merchant_id: merchantId
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
    }, [merchantId]);

    useEffect(() => {
        fetchBatches(currentPage, perPage);
        fetchStatistics();
    }, [fetchBatches, fetchStatistics, currentPage, perPage]);

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
        setCurrentPage(1);
    };

    // Clear all filters
    const clearFilters = () => {
        setFilters({
            search: '',
            status: ''
        });
        setCurrentPage(1);
    };

    // Get status badge color
    const getStatusColor = (status) => {
        const statusColors = {
            'settled': 'success',
            'pending': 'warning',
            'failed': 'danger'
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
                    0% { background-position: 200% 0; }
                    100% { background-position: -200% 0; }
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
                    {/* Toolbar */}
                    <div className="d-flex flex-wrap flex-stack mb-6">
                        <h3 className="fw-bolder my-2">
                            Batches
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
                                onClick={() => fetchBatches(currentPage, perPage)}
                                disabled={loading}
                            >
                                <i className="ki-duotone ki-arrows-circle fs-6 text-muted me-1">
                                    <span className="path1"></span>
                                    <span className="path2"></span>
                                </i>
                                Refresh
                            </button>
                        </div>
                    </div>

                    {/* Filters */}
                    {showFilters && (
                        <BatchFilters
                            filters={filters}
                            onFilterChange={handleFilterChange}
                            onClearFilters={clearFilters}
                        />
                    )}

                    {/* Statistics */}
                    {loading && !statistics ? (
                        <div className="row g-5 g-xl-8 mb-5">
                            {[1, 2, 3, 4].map((item) => (
                                <div key={item} className="col-sm-3">
                                    <div className="card bg-light hoverable card-xl-stretch">
                                        <div className="card-body text-center">
                                            <div className="skeleton" style={{width: '80px', height: '48px', margin: '0 auto 10px'}}></div>
                                            <div className="skeleton" style={{width: '140px', height: '18px', margin: '0 auto'}}></div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : statistics ? (
                        <BatchStatistics statistics={statistics} />
                    ) : null}

                    {/* Table */}
                    <div className="card">
                        <div className="card-body pt-0">
                            <div className="table-responsive">
                                <table className="table align-middle table-row-dashed fs-7 gy-5">
                                    <thead>
                                        <tr className="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                            <th className="text-dark">Batch Number</th>
                                            <th className="text-dark">Merchant</th>
                                            <th className="text-dark">Status</th>
                                            <th className="text-dark">Total Amount</th>
                                            <th className="text-dark">Transaction Count</th>
                                            <th className="text-dark">Created Time</th>
                                            <th className="text-end text-dark">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {loading ? (
                                            [...Array(perPage)].map((_, index) => (
                                                <tr key={`skeleton-${index}`}>
                                                    <td><div className="skeleton skeleton-text" style={{width: '120px', height: '16px'}}></div></td>
                                                    <td><div className="skeleton skeleton-text" style={{width: '150px', height: '16px'}}></div></td>
                                                    <td><div className="skeleton skeleton-badge" style={{width: '80px', height: '24px', borderRadius: '6px'}}></div></td>
                                                    <td><div className="skeleton skeleton-text" style={{width: '90px', height: '16px'}}></div></td>
                                                    <td><div className="skeleton skeleton-text" style={{width: '60px', height: '16px'}}></div></td>
                                                    <td><div className="skeleton skeleton-text" style={{width: '140px', height: '16px'}}></div></td>
                                                    <td className="text-end"><div className="skeleton skeleton-button" style={{width: '70px', height: '32px', borderRadius: '6px', marginLeft: 'auto'}}></div></td>
                                                </tr>
                                            ))
                                        ) : batches.length === 0 ? (
                                            <tr>
                                                <td colSpan="7" className="text-center py-5">
                                                    <div className="text-gray-500">
                                                        <i className="ki-duotone ki-file fs-3x mb-3">
                                                            <span className="path1"></span>
                                                            <span className="path2"></span>
                                                        </i>
                                                        <p className="fw-bold">No batches found</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        ) : (
                                            batches.map((batch) => (
                                                <tr key={batch.id}>
                                                    <td>{batch.batch_number}</td>
                                                    <td>{batch.merchant_name}</td>
                                                    <td>
                                                        <span className={`badge badge-light-${getStatusColor(batch.status)}`}>
                                                            {batch.status.charAt(0).toUpperCase() + batch.status.slice(1)}
                                                        </span>
                                                    </td>
                                                    <td>${parseFloat(batch.total_amount).toFixed(2)}</td>
                                                    <td>{batch.transaction_count}</td>
                                                    <td>{batch.created_at}</td>
                                                    <td className="text-end">
                                                        <BatchActions
                                                            batch={batch}
                                                        />
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            {!loading && batches.length > 0 && (
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
                </div>
            </div>
        </>
    );
};

export default MerchantBatches;

