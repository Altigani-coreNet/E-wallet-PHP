import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { getServiceFee } from '../../services/serviceFeesService';
import Toolbar from '../common/Toolbar';
import LoadingSpinner from '../common/LoadingSpinner';

const ServiceFeeView = () => {
    const { id } = useParams();
    const [serviceFee, setServiceFee] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const breadcrumbs = [
        { label: 'Home', url: '/' },
        { label: 'Merchant', url: '/merchant/dashboard' },
        { label: 'Service Fees', url: '/merchant/service-fees' },
        { label: 'View', active: true }
    ];

    useEffect(() => {
        fetchServiceFee();
    }, [id]);

    const fetchServiceFee = async () => {
        setLoading(true);
        try {
            const response = await getServiceFee(id);
            console.log('Service Fee View Response:', response);
            
            if (response.success) {
                const feeData = response.data?.data || response.data;
                setServiceFee(feeData);
            } else {
                setError(response.error || 'Failed to fetch service fee');
            }
        } catch (err) {
            console.error('Error fetching service fee:', err);
            setError('An unexpected error occurred');
        } finally {
            setLoading(false);
        }
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2
        }).format(amount);
    };

    if (loading) {
        return (
            <>
                <Toolbar 
                    pageTitle="Service Fee Details"
                    breadcrumbs={breadcrumbs}
                />
                <div className="post d-flex flex-column-fluid" id="kt_post">
                    <div id="kt_content_container" className="container-xxl">
                        <LoadingSpinner />
                    </div>
                </div>
            </>
        );
    }

    if (error || !serviceFee) {
        return (
            <>
                <Toolbar 
                    pageTitle="Service Fee Details"
                    breadcrumbs={breadcrumbs}
                />
                <div className="post d-flex flex-column-fluid" id="kt_post">
                    <div id="kt_content_container" className="container-xxl">
                        <div className="alert alert-danger">
                            <strong>Error:</strong> {error || 'Service fee not found'}
                        </div>
                        <a href="/merchant/service-fees" className="btn btn-primary">
                            Back to Service Fees
                        </a>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Toolbar 
                pageTitle="Service Fee Details"
                breadcrumbs={breadcrumbs}
            />

            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-xxl">
                    <div className="card">
                        <div className="card-header">
                            <h3 className="card-title">Service Fee Information</h3>
                        </div>

                        <div className="card-body">
                            <div className="row mb-7">
                                <label className="col-lg-4 fw-bold text-muted">Fee Name</label>
                                <div className="col-lg-8">
                                    <span className="fw-bolder fs-6 text-gray-800">{serviceFee.name}</span>
                                </div>
                            </div>

                            <div className="row mb-7">
                                <label className="col-lg-4 fw-bold text-muted">Type</label>
                                <div className="col-lg-8">
                                    <span className="badge badge-light-primary">
                                        {serviceFee.type?.toUpperCase() || 'N/A'}
                                    </span>
                                </div>
                            </div>

                            <div className="row mb-7">
                                <label className="col-lg-4 fw-bold text-muted">Fee Amount</label>
                                <div className="col-lg-8">
                                    <span className="fw-bolder fs-3 text-success">
                                        {formatCurrency(serviceFee.fees)}
                                    </span>
                                </div>
                            </div>

                            <div className="row mb-7">
                                <label className="col-lg-4 fw-bold text-muted">Description</label>
                                <div className="col-lg-8">
                                    <span className="fw-bolder fs-6 text-gray-800">
                                        {serviceFee.description || 'No description available'}
                                    </span>
                                </div>
                            </div>

                            <div className="row mb-7">
                                <label className="col-lg-4 fw-bold text-muted">Created At</label>
                                <div className="col-lg-8">
                                    <span className="fw-bolder fs-6 text-gray-800">
                                        {serviceFee.created_at ? new Date(serviceFee.created_at).toLocaleString() : 'N/A'}
                                    </span>
                                </div>
                            </div>

                            <div className="row mb-7">
                                <label className="col-lg-4 fw-bold text-muted">Updated At</label>
                                <div className="col-lg-8">
                                    <span className="fw-bolder fs-6 text-gray-800">
                                        {serviceFee.updated_at ? new Date(serviceFee.updated_at).toLocaleString() : 'N/A'}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div className="card-footer d-flex justify-content-end">
                            <a href="/merchant/service-fees" className="btn btn-light">
                                Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

export default ServiceFeeView;

