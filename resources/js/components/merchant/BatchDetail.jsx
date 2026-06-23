import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { toast } from 'react-toastify';
import { useNavigate, useParams, Link } from 'react-router-dom';

const BatchDetail = () => {
    const navigate = useNavigate();
    const { id } = useParams();
    const [batch, setBatch] = useState(null);
    const [loading, setLoading] = useState(true);

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

    useEffect(() => {
        const fetchBatch = async () => {
            try {
                const token = getApiToken();
                const baseUrl = getApiBaseUrl();
                const response = await axios.get(`${baseUrl}/batches/${id}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                setBatch(response.data);
            } catch (error) {
                console.error('Error fetching batch details:', error);
                toast.error('Failed to load batch details');
                navigate('/merchant/batches');
            } finally {
                setLoading(false);
            }
        };

        if (id) {
            fetchBatch();
        }
    }, [id, navigate]);

    if (loading) {
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
                `}</style>

                {/* Breadcrumbs Skeleton */}
                <div className="d-flex flex-wrap flex-stack mb-6">
                    <div className="skeleton" style={{width: '300px', height: '20px'}}></div>
                    <div className="skeleton" style={{width: '150px', height: '38px'}}></div>
                </div>

                {/* Batch Info Card Skeleton */}
                <div className="row g-5 g-xl-10 mb-5 mb-xl-10">
                    <div className="col-md-12">
                        <div className="card">
                            <div className="card-header pt-5">
                                <div className="skeleton" style={{width: '200px', height: '48px'}}></div>
                            </div>
                            <div className="card-body pt-2 pb-4">
                                <div className="d-flex flex-column flex-grow-1">
                                    {[1, 2, 3, 4, 5].map((i) => (
                                        <div key={i} className="d-flex align-items-center mb-3">
                                            <div className="skeleton" style={{width: '120px', height: '18px', marginRight: '10px'}}></div>
                                            <div className="skeleton" style={{width: '200px', height: '18px'}}></div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Transactions Table Skeleton */}
                <div className="card">
                    <div className="card-header border-0 pt-6">
                        <div className="skeleton" style={{width: '150px', height: '24px'}}></div>
                    </div>
                    <div className="card-body pt-0">
                        <div className="table-responsive">
                            <table className="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr className="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                        <th><div className="skeleton" style={{width: '100px', height: '14px'}}></div></th>
                                        <th><div className="skeleton" style={{width: '80px', height: '14px'}}></div></th>
                                        <th><div className="skeleton" style={{width: '70px', height: '14px'}}></div></th>
                                        <th><div className="skeleton" style={{width: '80px', height: '14px'}}></div></th>
                                        <th><div className="skeleton" style={{width: '100px', height: '14px'}}></div></th>
                                        <th><div className="skeleton" style={{width: '70px', height: '14px'}}></div></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {[1, 2, 3, 4, 5].map((i) => (
                                        <tr key={i}>
                                            <td><div className="skeleton" style={{width: '120px', height: '16px'}}></div></td>
                                            <td><div className="skeleton" style={{width: '80px', height: '16px'}}></div></td>
                                            <td><div className="skeleton" style={{width: '80px', height: '24px', borderRadius: '6px'}}></div></td>
                                            <td><div className="skeleton" style={{width: '100px', height: '16px'}}></div></td>
                                            <td><div className="skeleton" style={{width: '140px', height: '16px'}}></div></td>
                                            <td><div className="skeleton" style={{width: '40px', height: '32px', borderRadius: '6px'}}></div></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </>
        );
    }

    if (!batch) {
        return null;
    }

    // Get status badge color
    const getStatusColor = (status) => {
        const statusMap = {
            'pending': 'warning',
            'settled': 'success',
            'failed': 'danger'
        };
        return statusMap[status?.toLowerCase()] || 'secondary';
    };

    // Get transaction status badge color
    const getTransactionStatusColor = (status) => {
        const statusMap = {
            'approved': 'success',
            'captured': 'success',
            'pending': 'warning',
            'declined': 'danger',
            'failed': 'danger',
            'voided': 'danger',
            'refunded': 'danger',
            'cancelled': 'danger',
            'expired': 'danger',
            'reversed': 'danger'
        };
        return statusMap[status?.toLowerCase()] || 'secondary';
    };

    return (
        <>
            {/* Breadcrumbs */}
            <div className="d-flex flex-wrap flex-stack mb-6">
                <ul className="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li className="breadcrumb-item text-muted">
                        <Link to="/merchant/dashboard" className="text-muted text-hover-primary">Home</Link>
                    </li>
                    <li className="breadcrumb-item">
                        <span className="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li className="breadcrumb-item text-muted">
                        <Link to="/merchant/batches" className="text-muted text-hover-primary">Batches</Link>
                    </li>
                    <li className="breadcrumb-item">
                        <span className="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li className="breadcrumb-item text-muted">Batch Details</li>
                </ul>

                {/* Toolbar Actions */}
                <div className="d-flex align-items-center gap-2">
                    <Link to="/merchant/batches" className="btn btn-sm btn-flex btn-secondary fw-bold">
                        <i className="ki-duotone ki-arrow-left fs-6 text-muted me-1">
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        Back to Batches
                    </Link>
                </div>
            </div>

            <div className="row g-5 g-xl-10 mb-5 mb-xl-10">
                <div className="col-md-12">
                    {/* Batch Info Card */}
                    <div className="card">
                        <div className="card-header pt-5">
                            <div className="card-title d-flex flex-column">
                                <div className="d-flex align-items-center">
                                    <span className="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">{batch.batch_number}</span>
                                </div>
                                <span className="text-gray-400 pt-1 fw-semibold fs-6">Batch Information</span>
                            </div>
                        </div>
                        <div className="card-body pt-2 pb-4 d-flex align-items-center">
                            <div className="d-flex flex-column flex-grow-1">
                                <div className="d-flex align-items-center mb-2">
                                    <span className="text-gray-600 fw-semibold fs-6 me-2">Merchant:</span>
                                    <span className="text-dark fw-bold fs-6">{batch.merchant?.name || 'N/A'}</span>
                                </div>
                                <div className="d-flex align-items-center mb-2">
                                    <span className="text-gray-600 fw-semibold fs-6 me-2">Status:</span>
                                    <span className={`badge badge-light-${getStatusColor(batch.status)} fs-7`}>
                                        {batch.status ? batch.status.charAt(0).toUpperCase() + batch.status.slice(1) : 'N/A'}
                                    </span>
                                </div>
                                <div className="d-flex align-items-center mb-2">
                                    <span className="text-gray-600 fw-semibold fs-6 me-2">Total Amount:</span>
                                    <span className="text-dark fw-bold fs-6">${parseFloat(batch.total_amount || 0).toFixed(2)}</span>
                                </div>
                                <div className="d-flex align-items-center mb-2">
                                    <span className="text-gray-600 fw-semibold fs-6 me-2">Transaction Count:</span>
                                    <span className="text-dark fw-bold fs-6">{batch.transaction_count || 0}</span>
                                </div>
                                <div className="d-flex align-items-center mb-2">
                                    <span className="text-gray-600 fw-semibold fs-6 me-2">Created At:</span>
                                    <span className="text-dark fw-bold fs-6">
                                        {batch.created_at ? new Date(batch.created_at).toLocaleString('en-US', {
                                            year: 'numeric',
                                            month: '2-digit',
                                            day: '2-digit',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                            second: '2-digit',
                                            hour12: false
                                        }).replace(',', '') : 'N/A'}
                                    </span>
                                </div>
                                {batch.settled_at && (
                                    <div className="d-flex align-items-center">
                                        <span className="text-gray-600 fw-semibold fs-6 me-2">Settled At:</span>
                                        <span className="text-dark fw-bold fs-6">
                                            {new Date(batch.settled_at).toLocaleString('en-US', {
                                                year: 'numeric',
                                                month: '2-digit',
                                                day: '2-digit',
                                                hour: '2-digit',
                                                minute: '2-digit',
                                                second: '2-digit',
                                                hour12: false
                                            }).replace(',', '')}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Transactions Card */}
            <div className="card">
                <div className="card-header border-0 pt-6">
                    <div className="card-title">
                        <h3 className="card-title">Transactions</h3>
                    </div>
                </div>
                <div className="card-body pt-0">
                    <div className="table-responsive">
                        <table className="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr className="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                    <th className="text-dark">Transaction ID</th>
                                    <th className="text-dark">Amount</th>
                                    <th className="text-dark">Status</th>
                                    <th className="text-dark">Terminal</th>
                                    <th className="text-dark">Created At</th>
                                    <th className="text-dark">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="fw-semibold text-gray-600">
                                {batch.transactions && batch.transactions.length > 0 ? (
                                    batch.transactions.map((transaction) => (
                                        <tr key={transaction.id}>
                                            <td className="text-dark fw-bold">{transaction.transaction_id}</td>
                                            <td className="text-dark fw-bold">
                                                {transaction.currency?.symbol || '$'} {parseFloat(transaction.amount || 0).toFixed(2)}
                                            </td>
                                            <td>
                                                <span className={`badge badge-light-${getTransactionStatusColor(transaction.status)} fs-7`}>
                                                    {transaction.status ? transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1) : 'N/A'}
                                                </span>
                                            </td>
                                            <td className="text-dark fw-bold">{transaction.terminal?.terminal_id || 'N/A'}</td>
                                            <td className="text-dark fw-bold">
                                                {transaction.created_at ? new Date(transaction.created_at).toLocaleString('en-US', {
                                                    year: 'numeric',
                                                    month: '2-digit',
                                                    day: '2-digit',
                                                    hour: '2-digit',
                                                    minute: '2-digit',
                                                    second: '2-digit',
                                                    hour12: false
                                                }).replace(',', '') : 'N/A'}
                                            </td>
                                            <td>
                                                <Link 
                                                    to={`/merchant/transactions/${transaction.id}`}
                                                    className="btn btn-icon btn-bg-light btn-active-color-primary btn-sm"
                                                    title="View Transaction"
                                                >
                                                    <i className="ki-duotone ki-eye fs-2">
                                                        <span className="path1"></span>
                                                        <span className="path2"></span>
                                                        <span className="path3"></span>
                                                    </i>
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="6" className="text-center text-muted fs-6 py-8">
                                            <i className="ki-duotone ki-document fs-2hx text-muted mb-3">
                                                <span className="path1"></span>
                                                <span className="path2"></span>
                                            </i>
                                            <div>No transactions found</div>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </>
    );
};

export default BatchDetail;

