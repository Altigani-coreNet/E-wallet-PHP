import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { getBranch } from '../../services/branchesService';
import Toolbar from '../common/Toolbar';
import LoadingSpinner from '../common/LoadingSpinner';

const BranchView = () => {
    const { id } = useParams();
    const [branch, setBranch] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const breadcrumbs = [
        { label: 'Home', url: '/' },
        { label: 'Merchant', url: '/merchant/dashboard' },
        { label: 'Branches', url: '/merchant/branches' },
        { label: 'View', active: true }
    ];

    useEffect(() => {
        fetchBranch();
    }, [id]);

    const fetchBranch = async () => {
        setLoading(true);
        try {
            const response = await getBranch(id);
            console.log('Branch View Response:', response);
            if (response.success) {
                // Handle both response.data and response.data.data
                const branchData = response.data?.data || response.data;
                console.log('Branch Data:', branchData);
                setBranch(branchData);
            } else {
                setError(response.error || 'Failed to fetch branch');
            }
        } catch (err) {
            console.error('Error fetching branch:', err);
            setError('An unexpected error occurred');
        } finally {
            setLoading(false);
        }
    };

    const getStatusBadge = (status) => {
        const statusColors = {
            'pending': 'badge-warning',
            'approved': 'badge-success',
            'rejected': 'badge-danger',
            'suspended': 'badge-secondary',
            'viewed': 'badge-info'
        };
        return statusColors[status] || 'badge-secondary';
    };

    if (loading) {
        return (
            <>
                <Toolbar 
                    pageTitle="Branch Details"
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

    if (error || !branch) {
        return (
            <>
                <Toolbar 
                    pageTitle="Branch Details"
                    breadcrumbs={breadcrumbs}
                />
                <div className="post d-flex flex-column-fluid" id="kt_post">
                    <div id="kt_content_container" className="container-xxl">
                        <div className="alert alert-danger">
                            <strong>Error:</strong> {error || 'Branch not found'}
                        </div>
                        <a href="/merchant/sales/branches" className="btn btn-primary">
                            Back to Branches
                        </a>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Toolbar 
                pageTitle="Branch Details"
                breadcrumbs={breadcrumbs}
            >
                <div className="d-flex align-items-center gap-2">
                    <a href={`/merchant/branches/${id}/edit`} className="btn btn-sm btn-primary">
                        <i className="ki-duotone ki-pencil fs-3">
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        Edit Branch
                    </a>
                </div>
            </Toolbar>

            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-xxl">
                    <div className="card">
                        <div className="card-header">
                            <h3 className="card-title">Branch Information</h3>
                        </div>

                        <div className="card-body">
                            <div className="row mb-7">
                                <label className="col-lg-4 fw-bold text-muted">Branch Name</label>
                                <div className="col-lg-8">
                                    <span className="fw-bolder fs-6 text-gray-800">{branch.name}</span>
                                </div>
                            </div>

                            <div className="row mb-7">
                                <label className="col-lg-4 fw-bold text-muted">Address</label>
                                <div className="col-lg-8">
                                    <span className="fw-bolder fs-6 text-gray-800">
                                        {branch.address || 'N/A'}
                                    </span>
                                </div>
                            </div>

                            <div className="row mb-7">
                                <label className="col-lg-4 fw-bold text-muted">Status</label>
                                <div className="col-lg-8">
                                    <span className={`badge ${getStatusBadge(branch.status)}`}>
                                        {branch.status ? branch.status.toUpperCase() : 'N/A'}
                                    </span>
                                </div>
                            </div>

                            <div className="row mb-7">
                                <label className="col-lg-4 fw-bold text-muted">Active Status</label>
                                <div className="col-lg-8">
                                    <span className={`badge ${branch.is_active ? 'badge-success' : 'badge-secondary'}`}>
                                        {branch.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                            </div>

                            <div className="row mb-7">
                                <label className="col-lg-4 fw-bold text-muted">Created At</label>
                                <div className="col-lg-8">
                                    <span className="fw-bolder fs-6 text-gray-800">
                                        {branch.created_at ? new Date(branch.created_at).toLocaleString() : 'N/A'}
                                    </span>
                                </div>
                            </div>

                            <div className="row mb-7">
                                <label className="col-lg-4 fw-bold text-muted">Updated At</label>
                                <div className="col-lg-8">
                                    <span className="fw-bolder fs-6 text-gray-800">
                                        {branch.updated_at ? new Date(branch.updated_at).toLocaleString() : 'N/A'}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div className="card-footer d-flex justify-content-end">
                            <a href="/merchant/branches" className="btn btn-light">
                                Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

export default BranchView;


