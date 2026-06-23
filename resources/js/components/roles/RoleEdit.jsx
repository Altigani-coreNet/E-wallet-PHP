import React, { useState, useEffect } from 'react';
import { getRole, updateRole } from '../../services/rolesService';
import RoleForm from './RoleForm';
import Toolbar from '../common/Toolbar';
import LoadingSpinner from '../common/LoadingSpinner';
import ErrorAlert from '../common/ErrorAlert';

const RoleEdit = ({ roleId, typeParam, onSuccess }) => {
    const [role, setRole] = useState(null);
    const [loading, setLoading] = useState(false);
    const [loadingRole, setLoadingRole] = useState(true);
    const [error, setError] = useState(null);

    // Get translations
    const translations = window.translations || {};

    // Build breadcrumbs
    const breadcrumbs = [
        { label: 'Home', url: '/' },
        { label: 'Sales', url: '/merchant/sales/dashboard' },
        { label: 'Roles', url: '/merchant/sales/roles' },
        { label: role?.name || 'Edit', active: true }
    ];

    // Fetch role data
    useEffect(() => {
        fetchRole();
    }, [roleId]);

    const fetchRole = async () => {
        setLoadingRole(true);
        setError(null);

        try {
            const response = await getRole(roleId);

            if (response.success) {
                setRole(response.data.data || response.data.role || response.data);
            } else {
                setError(response.error || 'Failed to fetch role');
            }
        } catch (err) {
            console.error('Error fetching role:', err);
            setError('An unexpected error occurred while fetching the role');
        } finally {
            setLoadingRole(false);
        }
    };

    const handleSubmit = async (formData) => {
        setLoading(true);
        setError(null);

        try {
            const response = await updateRole(roleId, formData);

            if (response.success) {
                // Success message
                alert('✅ Role updated successfully!');
                
                // Redirect or call success callback
                if (onSuccess) {
                    onSuccess(response.data);
                } else {
                    // Redirect to roles list
                    window.location.href = `/merchant/sales/roles${typeParam ? `?type=${typeParam}` : ''}`;
                }
            } else {
                // Handle validation errors
                const errorData = response.error || response.details || 'Failed to update role';
                setError(errorData);
            }
        } catch (err) {
            console.error('Error updating role:', err);
            setError('An unexpected error occurred while updating the role');
        } finally {
            setLoading(false);
        }
    };

    if (loadingRole) {
        return (
            <>
                <Toolbar 
                    pageTitle="Edit Role"
                    breadcrumbs={breadcrumbs}
                />
                <div className="post d-flex flex-column-fluid" id="kt_post">
                    <div id="kt_content_container" className="container-fluid">
                        <LoadingSpinner message="Loading role..." />
                    </div>
                </div>
            </>
        );
    }

    if (error && !role) {
        return (
            <>
                <Toolbar 
                    pageTitle="Edit Role"
                    breadcrumbs={breadcrumbs}
                />
                <div className="post d-flex flex-column-fluid" id="kt_post">
                    <div id="kt_content_container" className="container-fluid">
                        <ErrorAlert message={error} />
                        <a href="/merchant/sales/roles" className="btn btn-light mt-3">
                            Back to Roles
                        </a>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            {/* Toolbar */}
            <Toolbar 
                pageTitle={`Edit Role: ${role?.name || ''}`}
                breadcrumbs={breadcrumbs}
            />

            {/* Main Content */}
            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-xxl">
                    <RoleForm
                        role={role}
                        mode="edit"
                        onSubmit={handleSubmit}
                        loading={loading}
                        error={error}
                    />
                </div>
            </div>
        </>
    );
};

export default RoleEdit;

