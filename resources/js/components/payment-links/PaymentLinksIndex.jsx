import React, { useState, useEffect } from 'react';
import { getPaymentLinks, bulkDeletePaymentLinks, exportPaymentLinks } from '../../services/paymentLinksService';
import Toolbar from '../common/Toolbar';
import PaymentLinksTable from './PaymentLinksTable';
import PaymentLinksFilters from './PaymentLinksFilters';
import RescheduleModal from './RescheduleModal';
import SendModal from './SendModal';
import Swal from 'sweetalert2';

const PaymentLinksIndex = () => {
    const [paymentLinks, setPaymentLinks] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [selectedIds, setSelectedIds] = useState([]);
    const [showFilters, setShowFilters] = useState(false);
    const [showRescheduleModal, setShowRescheduleModal] = useState(false);
    const [showSendModal, setShowSendModal] = useState(false);
    const [selectedPaymentLink, setSelectedPaymentLink] = useState(null);
    const [pagination, setPagination] = useState({
        current_page: 1,
        per_page: 15,
        total: 0,
        last_page: 1
    });
    const [filters, setFilters] = useState({
        search: '',
        customer: '',
        from_date: '',
        to_date: '',
    });

    const breadcrumbs = [
        { label: 'Home', url: '/' },
        { label: 'Merchant', url: '/merchant/dashboard' },
        { label: 'Payment Links', active: true }
    ];

    useEffect(() => {
        fetchPaymentLinks();
    }, [pagination.current_page, pagination.per_page]);

    const fetchPaymentLinks = async () => {
        setLoading(true);
        setError(null);
        try {
            const params = {
                page: pagination.current_page,
                per_page: pagination.per_page,
                ...filters
            };

            const response = await getPaymentLinks(params);
            console.log('Payment Links Response:', response);
            if (response.success) {
                const paymentLinksData = Array.isArray(response.data) ? response.data : (response.data?.data || response.data || []);
                console.log('Payment Links Data:', paymentLinksData);
                setPaymentLinks(paymentLinksData);
                
                if (response.pagination) {
                    setPagination(prev => ({
                        ...prev,
                        ...response.pagination
                    }));
                }
            } else {
                setError(response.error || 'Failed to fetch payment links');
                Swal.fire({
                    title: 'Error!',
                    text: response.error || 'Failed to fetch payment links.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        } catch (error) {
            console.error('Error fetching payment links:', error);
            setError('An unexpected error occurred while fetching payment links');
            Swal.fire({
                title: 'Error!',
                text: 'An unexpected error occurred while fetching payment links.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        } finally {
            setLoading(false);
        }
    };

    const handleApplyFilters = () => {
        setPagination(prev => ({ ...prev, current_page: 1 }));
        fetchPaymentLinks();
    };

    const handleClearFilters = () => {
        setFilters({
            search: '',
            customer: '',
            from_date: '',
            to_date: '',
        });
        setPagination(prev => ({ ...prev, current_page: 1 }));
        setTimeout(() => fetchPaymentLinks(), 100);
    };

    const handleBulkDelete = async () => {
        if (selectedIds.length === 0) return;

        const result = await Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete ${selectedIds.length} payment link(s). This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel'
        });

        if (result.isConfirmed) {
            try {
                const response = await bulkDeletePaymentLinks(selectedIds);
                if (response.success) {
                    await Swal.fire({
                        title: 'Deleted!',
                        text: 'Payment links have been deleted successfully.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setSelectedIds([]);
                    fetchPaymentLinks();
                } else {
                    Swal.fire('Error!', response.error || 'Failed to delete payment links.', 'error');
                }
            } catch (error) {
                Swal.fire('Error!', 'An unexpected error occurred.', 'error');
            }
        }
    };

    const handleExport = async () => {
        try {
            const response = await exportPaymentLinks(filters);
            if (response.success) {
                await Swal.fire({
                    title: 'Success!',
                    text: 'Payment links exported successfully.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire('Error!', response.error || 'Failed to export payment links.', 'error');
            }
        } catch (error) {
            Swal.fire('Error!', 'An unexpected error occurred.', 'error');
        }
    };

    const handlePageChange = (newPage) => {
        setPagination(prev => ({ ...prev, current_page: newPage }));
    };

    const handlePerPageChange = (newPerPage) => {
        setPagination(prev => ({ 
            ...prev, 
            per_page: newPerPage,
            current_page: 1 
        }));
    };

    const handleReschedule = (paymentLink) => {
        setSelectedPaymentLink(paymentLink);
        setShowRescheduleModal(true);
    };

    const handleSend = (paymentLink) => {
        setSelectedPaymentLink(paymentLink);
        setShowSendModal(true);
    };

    const handleRescheduleSuccess = () => {
        setShowRescheduleModal(false);
        setSelectedPaymentLink(null);
        fetchPaymentLinks();
    };

    const handleSendSuccess = () => {
        setShowSendModal(false);
        setSelectedPaymentLink(null);
    };

    return (
        <div className="payment-links-index">
            {/* Toolbar */}
            <Toolbar
                pageTitle="Payment Links"
                breadcrumbs={breadcrumbs}
            >
                <div className="d-flex align-items-center gap-2 gap-lg-3">
                    {/* Filters Button */}
                    <button 
                        onClick={() => setShowFilters(!showFilters)}
                        className="btn btn-sm btn-flex btn-light fw-bold"
                    >
                        <i className={`ki-duotone ki-filter fs-6 text-muted me-1 ${showFilters ? '' : 'rotate-90'}`}>
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        Filters
                    </button>

                    {/* Bulk Delete Button */}
                    {selectedIds.length > 0 && (
                        <button 
                            onClick={handleBulkDelete}
                            className="btn btn-sm fw-bold btn-danger"
                        >
                            <i className="ki-duotone ki-trash fs-3">
                                <span className="path1"></span>
                                <span className="path2"></span>
                                <span className="path3"></span>
                                <span className="path4"></span>
                                <span className="path5"></span>
                            </i>
                            Delete Selected ({selectedIds.length})
                        </button>
                    )}

                    {/* Export Button */}
                    <button 
                        onClick={handleExport}
                        className="btn btn-sm fw-bold btn-success"
                    >
                        <i className="ki-duotone ki-exit-up fs-3">
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        Export
                    </button>

                    {/* Add Payment Link Button */}
                    <a 
                        href="/merchant/payment-links/create"
                        className="btn btn-sm fw-bold btn-primary"
                    >
                        <i className="ki-duotone ki-plus fs-3">
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        Add Payment Link
                    </a>
                </div>
            </Toolbar>

            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-xxl">
                    {/* Filters Sidebar */}
                    {showFilters && (
                        <PaymentLinksFilters
                            filters={filters}
                            setFilters={setFilters}
                            onApply={handleApplyFilters}
                            onClear={handleClearFilters}
                            onClose={() => setShowFilters(false)}
                        />
                    )}

                    {/* Payment Links Table */}
                    <div className="card">
                        <div className="card-header border-0 pt-6">
                            <div className="card-title">
                                <h3 className="card-label">Payment Links List</h3>
                            </div>
                        </div>
                        <div className="card-body pt-0">
                            <PaymentLinksTable
                                paymentLinks={paymentLinks}
                                selectedIds={selectedIds}
                                setSelectedIds={setSelectedIds}
                                pagination={pagination}
                                onPageChange={handlePageChange}
                                onPerPageChange={handlePerPageChange}
                                onRefresh={fetchPaymentLinks}
                                onReschedule={handleReschedule}
                                onSend={handleSend}
                                loading={loading}
                                error={error}
                            />
                        </div>
                    </div>
                </div>
            </div>

            {/* Reschedule Modal */}
            {showRescheduleModal && selectedPaymentLink && (
                <RescheduleModal
                    show={showRescheduleModal}
                    paymentLink={selectedPaymentLink}
                    onClose={() => {
                        setShowRescheduleModal(false);
                        setSelectedPaymentLink(null);
                    }}
                    onSuccess={handleRescheduleSuccess}
                />
            )}

            {/* Send Modal */}
            {showSendModal && selectedPaymentLink && (
                <SendModal
                    show={showSendModal}
                    paymentLink={selectedPaymentLink}
                    onClose={() => {
                        setShowSendModal(false);
                        setSelectedPaymentLink(null);
                    }}
                    onSuccess={handleSendSuccess}
                />
            )}
        </div>
    );
};

export default PaymentLinksIndex;

