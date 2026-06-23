import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { getTerminal, updateTerminal } from '../../services/terminalsService';
import TerminalForm from './TerminalForm';
import Toolbar from '../common/Toolbar';
import LoadingSpinner from '../common/LoadingSpinner';
import Swal from 'sweetalert2';

const TerminalEdit = () => {
    const { id } = useParams();
    const [terminal, setTerminal] = useState(null);
    const [loading, setLoading] = useState(true);
    const [updating, setUpdating] = useState(false);
    const [error, setError] = useState(null);

    const breadcrumbs = [
        { label: 'Home', url: '/' },
        { label: 'Merchant', url: '/merchant/dashboard' },
        { label: 'Terminals', url: '/merchant/terminals' },
        { label: 'Edit', active: true }
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
                Swal.fire('Error!', response.error || 'Failed to fetch terminal.', 'error');
            }
        } catch (err) {
            setError('An unexpected error occurred');
            Swal.fire('Error!', 'An unexpected error occurred.', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (formData) => {
        setUpdating(true);
        setError(null);

        try {
            const response = await updateTerminal(id, formData);
            
            if (response.success) {
                await Swal.fire({
                    title: 'Success!',
                    text: 'Terminal updated successfully.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                window.location.href = '/merchant/terminals';
            } else {
                setError(response.error || 'Failed to update terminal');
                Swal.fire('Error!', response.error || 'Failed to update terminal.', 'error');
            }
        } catch (err) {
            setError('An unexpected error occurred');
            Swal.fire('Error!', 'An unexpected error occurred.', 'error');
        } finally {
            setUpdating(false);
        }
    };

    if (loading) {
        return <LoadingSpinner />;
    }

    if (!terminal) {
        return (
            <div className="alert alert-danger">
                Terminal not found or you don't have permission to edit it.
            </div>
        );
    }

    return (
        <div className="terminal-edit">
            <Toolbar
                pageTitle="Edit Terminal"
                breadcrumbs={breadcrumbs}
            />

            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-xxl">
                    <TerminalForm
                        mode="edit"
                        initialData={terminal}
                        onSubmit={handleSubmit}
                        loading={updating}
                        error={error}
                    />
                </div>
            </div>
        </div>
    );
};

export default TerminalEdit;

