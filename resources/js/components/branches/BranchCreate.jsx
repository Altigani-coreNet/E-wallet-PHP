import React, { useState } from 'react';
import { createBranch } from '../../services/branchesService';
import BranchForm from './BranchForm';
import Toolbar from '../common/Toolbar';
import Swal from 'sweetalert2';

const BranchCreate = () => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const breadcrumbs = [
        { label: 'Home', url: '/' },
        { label: 'Merchant', url: '/merchant/dashboard' },
        { label: 'Branches', url: '/merchant/branches' },
        { label: 'Create', active: true }
    ];

    const handleSubmit = async (formData) => {
        setLoading(true);
        setError(null);

        try {
            const response = await createBranch(formData);

            if (response.success) {
                Swal.fire({
                    title: 'Success!',
                    text: 'Branch request submitted successfully! It will be reviewed by administrators.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = '/merchant/branches';
                });
            } else {
                const errorData = response.error || response.errors || 'Failed to create branch';
                setError(errorData);
            }
        } catch (err) {
            console.error('Error creating branch:', err);
            setError('An unexpected error occurred while creating the branch');
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <Toolbar 
                pageTitle="Create Branch"
                breadcrumbs={breadcrumbs}
            />

            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-xxl">
                    <BranchForm
                        mode="create"
                        onSubmit={handleSubmit}
                        loading={loading}
                        error={error}
                    />
                </div>
            </div>
        </>
    );
};

export default BranchCreate;


