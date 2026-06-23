import React, { useState } from 'react';
import { createTerminal } from '../../services/terminalsService';
import TerminalForm from './TerminalForm';
import Toolbar from '../common/Toolbar';
import Swal from 'sweetalert2';

const TerminalCreate = () => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const breadcrumbs = [
        { label: 'Home', url: '/' },
        { label: 'Merchant', url: '/merchant/dashboard' },
        { label: 'Terminals', url: '/merchant/terminals' },
        { label: 'Create', active: true }
    ];

    const handleSubmit = async (formData) => {
        setLoading(true);
        setError(null);

        try {
            const response = await createTerminal(formData);
            
            if (response.success) {
                await Swal.fire({
                    title: 'Success!',
                    text: 'Terminal created successfully.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                window.location.href = '/merchant/terminals';
            } else {
                setError(response.error || 'Failed to create terminal');
                Swal.fire('Error!', response.error || 'Failed to create terminal.', 'error');
            }
        } catch (err) {
            setError('An unexpected error occurred');
            Swal.fire('Error!', 'An unexpected error occurred.', 'error');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="terminal-create">
            <Toolbar
                pageTitle="Create Terminal"
                breadcrumbs={breadcrumbs}
            />

            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-xxl">
                    <TerminalForm
                        mode="create"
                        onSubmit={handleSubmit}
                        loading={loading}
                        error={error}
                    />
                </div>
            </div>
        </div>
    );
};

export default TerminalCreate;

