import React, { useState } from 'react';
import { createUser } from '../../services/usersService';
import UserForm from './UserForm';
import Toolbar from '../common/Toolbar';

const UserCreate = ({ onSuccess }) => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    // Build breadcrumbs
    const breadcrumbs = [
        { label: 'Home', url: '/' },
        { label: 'Sales', url: '/merchant/sales/dashboard' },
        { label: 'Users', url: '/merchant/sales/users' },
        { label: 'Create', active: true }
    ];

    const handleSubmit = async (formData) => {
        setLoading(true);
        setError(null);

        try {
            const response = await createUser(formData);

            if (response.success) {
                // Success - redirect or call success callback
                const apiData = response.data.data || response.data;
                
                // If password was auto-generated, show it to user
                if (apiData.generated_password) {
                    const emailStatus = apiData.email_sent ? 
                        'An email has been sent to the user with their credentials.' : 
                        'Please save this password and share it with the user manually.';
                    
                    alert(
                        `✅ User created successfully!\n\n` +
                        `📧 Email: ${formData.email}\n` +
                        `🔑 Generated Password: ${apiData.generated_password}\n\n` +
                        `${emailStatus}\n\n` +
                        `⚠️ Please save this password - it won't be shown again!`
                    );
                }
                
                if (onSuccess) {
                    onSuccess(apiData);
                } else {
                    // Redirect to users list with success message
                    window.location.href = '/merchant/sales/users';
                }
            } else {
                // Handle validation errors
                const errorData = response.error || response.details || 'Failed to create user';
                setError(errorData);
            }
        } catch (err) {
            console.error('Error creating user:', err);
            setError('An unexpected error occurred while creating the user');
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            {/* Toolbar */}
            <Toolbar 
                pageTitle="Add User"
                breadcrumbs={breadcrumbs}
            />

            {/* Main Content */}
            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-xxl">
                    <UserForm
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

export default UserCreate;

