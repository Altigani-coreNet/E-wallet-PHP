import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { getTerminal, deleteTerminal } from '../../services/terminalsService';
import Toolbar from '../common/Toolbar';
import LoadingSpinner from '../common/LoadingSpinner';
import Swal from 'sweetalert2';

const TerminalView = () => {
    const { id } = useParams();
    const [terminal, setTerminal] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const breadcrumbs = [
        { label: 'Home', url: '/' },
        { label: 'Merchant', url: '/merchant/dashboard' },
        { label: 'Terminals', url: '/merchant/terminals' },
        { label: 'View', active: true }
    ];

    useEffect(() => {
        fetchTerminal();
    }, [id]);

    const fetchTerminal = async () => {
        try {
            const response = await getTerminal(id);
            if (response.success) {
                setTerminal(response.data);
            } else {
                setError(response.error || 'Failed to fetch terminal');
            }
        } catch (err) {
            setError('An unexpected error occurred');
        } finally {
            setLoading(false);
        }
    };

    const handleEdit = () => {
        window.location.href = `/merchant/terminals/${id}/edit`;
    };

    const handleDelete = async () => {
        const result = await Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete terminal "${terminal?.name}". This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        });

        if (result.isConfirmed) {
            try {
                const response = await deleteTerminal(id);
                if (response.success) {
                    await Swal.fire({
                        title: 'Deleted!',
                        text: 'Terminal has been deleted successfully.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    window.location.href = '/merchant/terminals';
                } else {
                    Swal.fire('Error!', response.error || 'Failed to delete terminal.', 'error');
                }
            } catch (error) {
                Swal.fire('Error!', 'An unexpected error occurred.', 'error');
            }
        }
    };

    if (loading) {
        return <LoadingSpinner />;
    }

    if (error || !terminal) {
        return (
            <div className="alert alert-danger">
                {error || 'Terminal not found or you don\'t have permission to view it.'}
            </div>
        );
    }

    return (
        <div className="terminal-view">
            <Toolbar
                title="Terminal Details"
                breadcrumbs={breadcrumbs}
                actions={[
                    {
                        label: 'Edit',
                        onClick: handleEdit,
                        variant: 'primary',
                        icon: 'pencil'
                    },
                    {
                        label: 'Delete',
                        onClick: handleDelete,
                        variant: 'danger',
                        icon: 'trash'
                    }
                ]}
            />

            <div className="card">
                <div className="card-header">
                    <h3 className="card-title">Terminal Information</h3>
                </div>
                <div className="card-body">
                    <div className="row mb-7">
                        <label className="col-lg-4 fw-bold text-muted">Name</label>
                        <div className="col-lg-8">
                            <span className="fw-semibold text-gray-800 fs-6">{terminal.name}</span>
                        </div>
                    </div>

                    <div className="row mb-7">
                        <label className="col-lg-4 fw-bold text-muted">Terminal ID</label>
                        <div className="col-lg-8">
                            <span className="fw-semibold text-gray-800 fs-6">{terminal.terminal_id || 'N/A'}</span>
                        </div>
                    </div>

                    <div className="row mb-7">
                        <label className="col-lg-4 fw-bold text-muted">Branch</label>
                        <div className="col-lg-8">
                            <span className="fw-semibold text-gray-800 fs-6">{terminal.branch?.name || 'N/A'}</span>
                        </div>
                    </div>

                    <div className="row mb-7">
                        <label className="col-lg-4 fw-bold text-muted">Model</label>
                        <div className="col-lg-8">
                            <span className="fw-semibold text-gray-800 fs-6">{terminal.model || 'N/A'}</span>
                        </div>
                    </div>

                    <div className="row mb-7">
                        <label className="col-lg-4 fw-bold text-muted">Manufacturer</label>
                        <div className="col-lg-8">
                            <span className="fw-semibold text-gray-800 fs-6">{terminal.manufacturer || 'N/A'}</span>
                        </div>
                    </div>

                    <div className="row mb-7">
                        <label className="col-lg-4 fw-bold text-muted">Serial Number</label>
                        <div className="col-lg-8">
                            <span className="fw-semibold text-gray-800 fs-6">{terminal.serial_no || 'N/A'}</span>
                        </div>
                    </div>

                    <div className="row mb-7">
                        <label className="col-lg-4 fw-bold text-muted">SDK ID</label>
                        <div className="col-lg-8">
                            <span className="fw-semibold text-gray-800 fs-6">{terminal.sdk_id || 'N/A'}</span>
                        </div>
                    </div>

                    <div className="row mb-7">
                        <label className="col-lg-4 fw-bold text-muted">SDK Version</label>
                        <div className="col-lg-8">
                            <span className="fw-semibold text-gray-800 fs-6">{terminal.sdk_version || 'N/A'}</span>
                        </div>
                    </div>

                    <div className="row mb-7">
                        <label className="col-lg-4 fw-bold text-muted">Android OS</label>
                        <div className="col-lg-8">
                            <span className="fw-semibold text-gray-800 fs-6">{terminal.android_os || 'N/A'}</span>
                        </div>
                    </div>

                    <div className="row mb-7">
                        <label className="col-lg-4 fw-bold text-muted">Add Type</label>
                        <div className="col-lg-8">
                            <span className="badge badge-light-primary">{terminal.add_type || 'N/A'}</span>
                        </div>
                    </div>

                    <div className="row mb-7">
                        <label className="col-lg-4 fw-bold text-muted">Status</label>
                        <div className="col-lg-8">
                            {terminal.is_active ? (
                                <span className="badge badge-light-success">Active</span>
                            ) : (
                                <span className="badge badge-light-danger">Inactive</span>
                            )}
                        </div>
                    </div>

                    <div className="row mb-7">
                        <label className="col-lg-4 fw-bold text-muted">Created At</label>
                        <div className="col-lg-8">
                            <span className="fw-semibold text-gray-800 fs-6">
                                {terminal.created_at ? new Date(terminal.created_at).toLocaleString() : 'N/A'}
                            </span>
                        </div>
                    </div>

                    <div className="row mb-7">
                        <label className="col-lg-4 fw-bold text-muted">Updated At</label>
                        <div className="col-lg-8">
                            <span className="fw-semibold text-gray-800 fs-6">
                                {terminal.updated_at ? new Date(terminal.updated_at).toLocaleString() : 'N/A'}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default TerminalView;

