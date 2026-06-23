import React, { useState, useEffect } from 'react';
import { getUser, updateUser } from '../../services/usersService';
import UserForm from './UserForm';
import Toolbar from '../common/Toolbar';
import LoadingSpinner from '../common/LoadingSpinner';
import ErrorAlert from '../common/ErrorAlert';

const UserEdit = ({ userId }) => {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [fetchingUser, setFetchingUser] = useState(true);

    // Build breadcrumbs
    const breadcrumbs = [
        { label: 'Home', url: '/' },
        { label: 'Sales', url: '/merchant/sales/dashboard' },
        { label: 'Users', url: '/merchant/sales/users' },
        { label: 'Edit', active: true }
    ];

    // Fetch user data
    useEffect(() => {
        const fetchUserData = async () => {
            setFetchingUser(true);
            try {
                const response = await getUser(userId);
                
                if (response.success) {
                    // Extract user from nested data structure
                    const apiData = response.data.data || response.data;
                    setUser(apiData.user || apiData);
                } else {
                    setError(response.error || 'Failed to fetch user');
                }
            } catch (err) {
                console.error('Error fetching user:', err);
                setError('An unexpected error occurred while fetching user');
            } finally {
                setFetchingUser(false);
            }
        };

        if (userId) {
            fetchUserData();
        }
    }, [userId]);

    const handleSubmit = async (formData) => {
        setLoading(true);
        setError(null);

        try {
            const response = await updateUser(userId, formData);

            if (response.success) {
                // Success message
                alert('✅ User updated successfully!');
                
                // Redirect to users list
                window.location.href = '/merchant/sales/users';
            } else {
                // Handle validation errors
                const errorData = response.error || response.details || 'Failed to update user';
                setError(errorData);
            }
        } catch (err) {
            console.error('Error updating user:', err);
            setError('An unexpected error occurred while updating the user');
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            {/* Toolbar */}
            <Toolbar 
                pageTitle="Edit User"
                breadcrumbs={breadcrumbs}
            />

            {/* Main Content */}
            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-xxl">
                    {fetchingUser ? (
                        <div className="card">
                            <div className="card-body">
                                <LoadingSpinner message="Loading user data..." />
                            </div>
                        </div>
                    ) : error && !user ? (
                        <div className="card">
                            <div className="card-body">
                                <ErrorAlert message={typeof error === 'string' ? error : 'Failed to load user'} />
                                <a href="/merchant/sales/users" className="btn btn-light mt-3">
                                    Back to Users
                                </a>
                            </div>
                        </div>
                    ) : user ? (
                        <UserForm
                            mode="edit"
                            user={user}
                            onSubmit={handleSubmit}
                            loading={loading}
                            error={error}
                        />
                    ) : null}
                </div>
            </div>
        </>
    );
};

export default UserEdit;



