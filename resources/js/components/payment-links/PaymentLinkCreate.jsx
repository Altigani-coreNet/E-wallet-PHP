import React, { useState } from 'react';
import { createPaymentLink } from '../../services/paymentLinksService';
import PaymentLinkForm from './PaymentLinkForm';
import Toolbar from '../common/Toolbar';
import Swal from 'sweetalert2';

const PaymentLinkCreate = () => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const breadcrumbs = [
        { label: 'Home', url: '/' },
        { label: 'Merchant', url: '/merchant/dashboard' },
        { label: 'Payment Links', url: '/merchant/payment-links' },
        { label: 'Create', active: true }
    ];

    const handleSubmit = async (formData) => {
        setLoading(true);
        setError(null);

        try {
            const response = await createPaymentLink(formData);
            
            if (response.success) {
                await Swal.fire({
                    title: 'Success!',
                    text: 'Payment link created successfully.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                window.location.href = '/merchant/payment-links';
            } else {
                setError(response.error || 'Failed to create payment link');
                Swal.fire('Error!', response.error || 'Failed to create payment link.', 'error');
            }
        } catch (err) {
            setError('An unexpected error occurred');
            Swal.fire('Error!', 'An unexpected error occurred.', 'error');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="payment-link-create">
            <Toolbar
                pageTitle="Create Payment Link"
                breadcrumbs={breadcrumbs}
            />

            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-xxl">
                    <PaymentLinkForm
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

export default PaymentLinkCreate;

