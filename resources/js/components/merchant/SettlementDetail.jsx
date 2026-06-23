import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { toast } from 'react-toastify';
import { useNavigate, useParams, Link } from 'react-router-dom';

const SettlementDetail = () => {
    const navigate = useNavigate();
    const { id } = useParams();
    const [settlement, setSettlement] = useState(null);
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
        const fetchSettlement = async () => {
            try {
                const token = getApiToken();
                const baseUrl = getApiBaseUrl();
                const response = await axios.get(`${baseUrl}/settlements/${id}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                setSettlement(response.data);
            } catch (error) {
                console.error('Error fetching settlement details:', error);
                toast.error('Failed to load settlement details');
                navigate('/merchant/settlements');
            } finally {
                setLoading(false);
            }
        };

        if (id) {
            fetchSettlement();
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
                    <div className="skeleton" style={{width: '180px', height: '38px'}}></div>
                </div>

                {/* Settlement Info Card Skeleton */}
                <div className="card mb-5">
                    <div className="card-header">
                        <div className="skeleton" style={{width: '200px', height: '24px'}}></div>
                    </div>
                    <div className="card-body">
                        <div className="row mb-7">
                            <div className="col-md-6">
                                {[1, 2, 3].map((i) => (
                                    <div key={i} className="mb-5">
                                        <div className="skeleton" style={{width: '100px', height: '16px', marginBottom: '8px'}}></div>
                                        <div className="skeleton" style={{width: '180px', height: '18px'}}></div>
                                    </div>
                                ))}
                            </div>
                            <div className="col-md-6">
                                {[1, 2, 3].map((i) => (
                                    <div key={i} className="mb-5">
                                        <div className="skeleton" style={{width: '100px', height: '16px', marginBottom: '8px'}}></div>
                                        <div className="skeleton" style={{width: '180px', height: '18px'}}></div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Batch Transactions Card Skeleton */}
                <div className="card">
                    <div className="card-header">
                        <div className="skeleton" style={{width: '180px', height: '24px'}}></div>
                    </div>
                    <div className="card-body">
                        <div className="table-responsive">
                            <table className="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                                <thead>
                                    <tr className="fw-bold text-muted">
                                        <th><div className="skeleton" style={{width: '100px', height: '14px'}}></div></th>
                                        <th><div className="skeleton" style={{width: '70px', height: '14px'}}></div></th>
                                        <th><div className="skeleton" style={{width: '70px', height: '14px'}}></div></th>
                                        <th><div className="skeleton" style={{width: '80px', height: '14px'}}></div></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {[1, 2, 3, 4].map((i) => (
                                        <tr key={i}>
                                            <td><div className="skeleton" style={{width: '120px', height: '16px'}}></div></td>
                                            <td><div className="skeleton" style={{width: '80px', height: '16px'}}></div></td>
                                            <td><div className="skeleton" style={{width: '80px', height: '24px', borderRadius: '6px'}}></div></td>
                                            <td><div className="skeleton" style={{width: '140px', height: '16px'}}></div></td>
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

    if (!settlement) {
        return null;
    }

    // Get status badge color
    const getStatusColor = (status) => {
        const statusColors = {
            'settled': 'success',
            'pending': 'warning',
            'failed': 'danger'
        };
        return statusColors[status] || 'secondary';
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
                        <Link to="/merchant/settlements" className="text-muted text-hover-primary">Settlements</Link>
                    </li>
                    <li className="breadcrumb-item">
                        <span className="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li className="breadcrumb-item text-muted">Settlement Details</li>
                </ul>

                {/* Toolbar Actions */}
                <div className="d-flex align-items-center gap-2">
                    <Link to="/merchant/settlements" className="btn btn-sm btn-flex btn-secondary fw-bold">
                        <i className="ki-duotone ki-arrow-left fs-6 text-muted me-1">
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        Back to Settlements
                    </Link>
                </div>
            </div>

            {/* Settlement Info Card */}
            <div className="card mb-5">
                    <div className="card-header">
                        <h3 className="card-title">Settlement Information</h3>
                    </div>
                    <div className="card-body">
                        <div className="row mb-7">
                            <div className="col-md-6">
                                <div className="mb-5">
                                    <label className="form-label fw-bold">Settlement ID</label>
                                    <div className="text-gray-800">{settlement.settlement_id || 'N/A'}</div>
                                </div>
                                <div className="mb-5">
                                    <label className="form-label fw-bold">Merchant</label>
                                    <div className="text-gray-800">{settlement.merchant?.name || 'N/A'}</div>
                                </div>
                                <div className="mb-5">
                                    <label className="form-label fw-bold">Batch Number</label>
                                    <div className="text-gray-800">{settlement.batch?.batch_number || 'N/A'}</div>
                                </div>
                            </div>
                            <div className="col-md-6">
                                <div className="mb-5">
                                    <label className="form-label fw-bold">Status</label>
                                    <div>
                                        <span className={`badge badge-light-${getStatusColor(settlement.status)}`}>
                                            {settlement.status?.charAt(0).toUpperCase() + settlement.status?.slice(1)}
                                        </span>
                                    </div>
                                </div>
                                <div className="mb-5">
                                    <label className="form-label fw-bold">Total Amount</label>
                                    <div className="text-gray-800">
                                        {settlement.currency?.currency_code || 'USD'} {parseFloat(settlement.total_amount || 0).toFixed(2)}
                                    </div>
                                </div>
                                <div className="mb-5">
                                    <label className="form-label fw-bold">Settlement Date</label>
                                    <div className="text-gray-800">
                                        {settlement.settlement_date ? new Date(settlement.settlement_date).toLocaleDateString() : 'N/A'}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="row">
                            <div className="col-md-12">
                                <div className="mb-5">
                                    <label className="form-label fw-bold">Created At</label>
                                    <div className="text-gray-800">
                                        {settlement.created_at ? new Date(settlement.created_at).toLocaleString() : 'N/A'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Batch Transactions (if available) */}
                {settlement.batch?.transactions && settlement.batch.transactions.length > 0 && (
                    <div className="card">
                        <div className="card-header">
                            <h3 className="card-title">Batch Transactions</h3>
                        </div>
                        <div className="card-body">
                            <div className="table-responsive">
                                <table className="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                                    <thead>
                                        <tr className="fw-bold text-muted">
                                            <th>Transaction ID</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {settlement.batch.transactions.map((transaction) => (
                                            <tr key={transaction.id}>
                                                <td>{transaction.transaction_id}</td>
                                                <td>${parseFloat(transaction.amount || 0).toFixed(2)}</td>
                                                <td>
                                                    <span className={`badge badge-light-${transaction.status === 'APPROVED' ? 'success' : 'secondary'}`}>
                                                        {transaction.status}
                                                    </span>
                                                </td>
                                                <td>{transaction.created_at ? new Date(transaction.created_at).toLocaleString() : 'N/A'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                )}
        </>
    );
};

export default SettlementDetail;

