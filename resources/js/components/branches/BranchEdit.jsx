import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { getBranch, updateBranch } from '../../services/branchesService';
import BranchForm from './BranchForm';
import Toolbar from '../common/Toolbar';
import LoadingSpinner from '../common/LoadingSpinner';
import Swal from 'sweetalert2';

const BranchEdit = () => {
    const { id } = useParams();
    const [branch, setBranch] = useState(null);
    const [loading, setLoading] = useState(true);
    const [submitLoading, setSubmitLoading] = useState(false);
    const [error, setError] = useState(null);

    const breadcrumbs = [
        { label: 'Home', url: '/' },
        { label: 'Merchant', url: '/merchant/dashboard' },
        { label: 'Branches', url: '/merchant/branches' },
        { label: 'Edit', active: true }
    ];

    useEffect(() => {
        fetchBranch();
    }, [id]);

    const fetchBranch = async () => {
        setLoading(true);
        try {
            const response = await getBranch(id);
            console.log('Branch Edit Response:', response);
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

    const handleSubmit = async (formData) => {
        setSubmitLoading(true);
        setError(null);

        try {
            const response = await updateBranch(id, formData);

            if (response.success) {
                Swal.fire({
                    title: 'Success!',
                    text: 'Branch updated successfully!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = '/merchant/branches';
                });
            } else{
                const errorData = response.error || response.errors || 'Failed to update branch';
                setError(errorData);
            }
        } catch (err) {
            console.error('Error updating branch:', err);
            setError('An unexpected error occurred while updating the branch');
        } finally {
            setSubmitLoading(false);
        }
    };

    if (loading) {
        return (
            <>
                <Toolbar 
                    pageTitle="Edit Branch"
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

    if (error && !branch) {
        return (
            <>
                <Toolbar 
                    pageTitle="Edit Branch"
                    breadcrumbs={breadcrumbs}
                />
                <div className="post d-flex flex-column-fluid" id="kt_post">
                    <div id="kt_content_container" className="container-xxl">
                        <div className="alert alert-danger">
                            <strong>Error:</strong> {error}
                        </div>
                        <a href="/merchant/branches" className="btn btn-primary">
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
                pageTitle="Edit Branch"
                breadcrumbs={breadcrumbs}
            />

            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-xxl">
                    <BranchForm
                        mode="edit"
                        initialData={branch}
                        onSubmit={handleSubmit}
                        loading={submitLoading}
                        error={error}
                    />
                </div>
            </div>
        </>
    );
};

export default BranchEdit;


