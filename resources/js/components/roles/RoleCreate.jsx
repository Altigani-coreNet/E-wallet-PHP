import React, { useState } from 'react';
import { createRole } from '../../services/rolesService';
import RoleForm from './RoleForm';
import Toolbar from '../common/Toolbar';

const RoleCreate = ({ typeParam, onSuccess }) => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    // Get translations
    const translations = window.translations || {};

    // Build breadcrumbs
    const breadcrumbs = [
        { label: 'Home', url: '/' },
        { label: 'Sales', url: '/merchant/sales/dashboard' },
        { label: 'Roles', url: '/merchant/sales/roles' },
        { label: 'Create', active: true }
    ];

    const handleSubmit = async (formData) => {
        setLoading(true);
        setError(null);

        try {
            // Add type parameter if exists
            const dataToSubmit = {
                ...formData,
                ...(typeParam && { type: typeParam })
            };

            const response = await createRole(dataToSubmit);

            if (response.success) {
                // Success message
                alert('✅ Role created successfully!');
                
                // Redirect or call success callback
                if (onSuccess) {
                    onSuccess(response.data);
                } else {
                    // Redirect to roles list
                    window.location.href = `/merchant/sales/roles${typeParam ? `?type=${typeParam}` : ''}`;
                }
            } else {
                // Handle validation errors
                const errorData = response.error || response.details || 'Failed to create role';
                setError(errorData);
            }
        } catch (err) {
            console.error('Error creating role:', err);
            setError('An unexpected error occurred while creating the role');
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            {/* Toolbar */}
            <Toolbar 
                pageTitle="Create Role"
                breadcrumbs={breadcrumbs}
            />

            {/* Main Content */}
            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-xxl">
                    <RoleForm
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

export default RoleCreate;

