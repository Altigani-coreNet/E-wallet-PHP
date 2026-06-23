import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { getPaymentLink, updatePaymentLink } from '../../services/paymentLinksService';
import PaymentLinkForm from './PaymentLinkForm';
import Toolbar from '../common/Toolbar';
import Swal from 'sweetalert2';

const PaymentLinkEdit = () => {
    const { id: paymentLinkId } = useParams();
    const [loading, setLoading] = useState(false);
    const [loadingData, setLoadingData] = useState(true);
    const [error, setError] = useState(null);
    const [paymentLink, setPaymentLink] = useState(null);

    const breadcrumbs = [
        { label: 'Home', url: '/' },
        { label: 'Merchant', url: '/merchant/dashboard' },
        { label: 'Payment Links', url: '/merchant/payment-links' },
        { label: 'Edit', active: true }
    ];

    useEffect(() => {
        fetchPaymentLink();
    }, [paymentLinkId]);

    const fetchPaymentLink = async () => {
        setLoadingData(true);
        try {
            const response = await getPaymentLink(paymentLinkId);
            if (response.success) {
                setPaymentLink(response.data.data);
            } else {
                setError(response.error || 'Failed to fetch payment link');
                Swal.fire('Error!', response.error || 'Failed to fetch payment link.', 'error');
            }
        } catch (err) {
            setError('An unexpected error occurred');
            Swal.fire('Error!', 'An unexpected error occurred.', 'error');
        } finally {
            setLoadingData(false);
        }
    };

    const handleSubmit = async (formData) => {
        setLoading(true);
        setError(null);

        try {
            const response = await updatePaymentLink(paymentLinkId, formData);
            
            if (response.success) {
                await Swal.fire({
                    title: 'Success!',
                    text: 'Payment link updated successfully.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                window.location.href = '/merchant/payment-links';
            } else {
                setError(response.error || 'Failed to update payment link');
                Swal.fire('Error!', response.error || 'Failed to update payment link.', 'error');
            }
        } catch (err) {
            setError('An unexpected error occurred');
            Swal.fire('Error!', 'An unexpected error occurred.', 'error');
        } finally {
            setLoading(false);
        }
    };

    if (loadingData) {
        return (
            <div className="payment-link-edit">
                <Toolbar
                    pageTitle="Edit Payment Link"
                    breadcrumbs={breadcrumbs}
                />
                <div className="post d-flex flex-column-fluid" id="kt_post">
                    <div id="kt_content_container" className="container-xxl">
                        <div className="text-center py-5">
                            <div className="spinner-border text-primary" role="status">
                                <span className="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    if (error && !paymentLink) {
        return (
            <div className="payment-link-edit">
                <Toolbar
                    pageTitle="Edit Payment Link"
                    breadcrumbs={breadcrumbs}
                />
                <div className="post d-flex flex-column-fluid" id="kt_post">
                    <div id="kt_content_container" className="container-xxl">
                        <div className="alert alert-danger">{error}</div>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="payment-link-edit">
            <Toolbar
                pageTitle="Edit Payment Link"
                breadcrumbs={breadcrumbs}
            />

            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-xxl">
                    <PaymentLinkForm
                        mode="edit"
                        initialData={paymentLink}
                        onSubmit={handleSubmit}
                        loading={loading}
                        error={error}
                    />
                </div>
            </div>
        </div>
    );
};

export default PaymentLinkEdit;

