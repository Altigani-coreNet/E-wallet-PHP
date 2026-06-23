import React, { useState, useEffect } from 'react';

const PaymentLinkForm = ({ mode, initialData, onSubmit, loading, error }) => {
    const [formData, setFormData] = useState({
        amount: '',
        currency_id: '1', // Default currency
        customer_name: '',
        customer_phone: '',
        customer_email: '',
        scheduled_date: '',
        expired_date: '',
        payment_method_types: ['card']
    });

    useEffect(() => {
        if (mode === 'edit' && initialData) {
            console.log('Initial Data Received:', initialData);
            
            // Helper function to extract date from datetime string
            const extractDate = (dateString) => {
                if (!dateString) return '';
                // Handle both "YYYY-MM-DD HH:MM:SS" and "YYYY-MM-DD" formats
                return dateString.split(' ')[0];
            };
            
            const updatedFormData = {
                amount: initialData.amount || '',
                currency_id: String(initialData.currency_id || '1'),
                customer_name: initialData.customer_name || '',
                customer_phone: initialData.customer_phone || '',
                customer_email: initialData.customer_email || '',
                scheduled_date: extractDate(initialData.scheduled_date),
                expired_date: extractDate(initialData.expired_date),
                payment_method_types: Array.isArray(initialData.payment_method_types) 
                    ? initialData.payment_method_types 
                    : (initialData.payment_method_types ? [initialData.payment_method_types] : ['card'])
            };
            
            console.log('Form Data Set:', updatedFormData);
            setFormData(updatedFormData);
        }
    }, [mode, initialData]);

    const handleChange = (e) => {
        const { name, value, type, options } = e.target;
        
        // Handle multi-select
        if (type === 'select-multiple') {
            const selectedValues = Array.from(options)
                .filter(option => option.selected)
                .map(option => option.value);
            setFormData(prev => ({
                ...prev,
                [name]: selectedValues
            }));
        } else {
            setFormData(prev => ({
                ...prev,
                [name]: value
            }));
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        console.log('Submitting Form Data:', formData);
        onSubmit(formData);
    };

    const paymentMethods = [
        { value: 'card', label: 'Card' },
        { value: 'afterpay_clearpay', label: 'Afterpay / Clearpay' },
        { value: 'alipay', label: 'Alipay' },
        { value: 'bancontact', label: 'Bancontact' },
        { value: 'eps', label: 'EPS' },
        { value: 'giropay', label: 'Giropay' },
        { value: 'grabpay', label: 'GrabPay' },
        { value: 'ideal', label: 'iDEAL' },
        { value: 'klarna', label: 'Klarna' },
        { value: 'oxxo', label: 'OXXO' },
        { value: 'p24', label: 'Przelewy24' },
        { value: 'sepa_debit', label: 'SEPA Debit' },
        { value: 'sofort', label: 'Sofort' },
        { value: 'us_bank_account', label: 'US Bank Account' },
        { value: 'wechat_pay', label: 'WeChat Pay' }
    ];

    return (
        <div className="card">
            <div className="card-header">
                <div className="card-title">
                    <h3>{mode === 'create' ? 'Create' : 'Edit'} Payment Link</h3>
                </div>
            </div>
            <form onSubmit={handleSubmit}>
                <div className="card-body">
                    {error && (
                        <div className="alert alert-danger" role="alert">
                            {error}
                        </div>
                    )}

                    <div className="row">
                        {/* Amount */}
                        <div className="col-md-6 mb-5">
                            <label className="form-label required">Amount</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                name="amount"
                                className="form-control"
                                placeholder="Enter amount"
                                value={formData.amount}
                                onChange={handleChange}
                                required
                            />
                        </div>

                        {/* Currency */}
                        <div className="col-md-6 mb-5">
                            <label className="form-label required">Currency</label>
                            <select
                                name="currency_id"
                                className="form-select"
                                value={formData.currency_id}
                                onChange={handleChange}
                                required
                            >
                                <option value="1">USD - United States Dollar</option>
                                <option value="2">EUR - Euro</option>
                                <option value="3">GBP - British Pound</option>
                                {/* Add more currencies as needed */}
                            </select>
                        </div>

                        {/* Customer Name */}
                        <div className="col-md-12 mb-5">
                            <label className="form-label required">Customer Name</label>
                            <input
                                type="text"
                                name="customer_name"
                                className="form-control"
                                placeholder="Enter customer name"
                                value={formData.customer_name}
                                onChange={handleChange}
                                required
                            />
                        </div>

                        {/* Customer Email */}
                        <div className="col-md-6 mb-5">
                            <label className="form-label">Customer Email</label>
                            <input
                                type="email"
                                name="customer_email"
                                className="form-control"
                                placeholder="Enter customer email"
                                value={formData.customer_email}
                                onChange={handleChange}
                            />
                            <div className="form-text">
                                Optional: Email for sending payment link
                            </div>
                        </div>

                        {/* Customer Phone */}
                        <div className="col-md-6 mb-5">
                            <label className="form-label">Customer Phone</label>
                            <input
                                type="tel"
                                name="customer_phone"
                                className="form-control"
                                placeholder="Enter customer phone"
                                value={formData.customer_phone}
                                onChange={handleChange}
                            />
                            <div className="form-text">
                                Optional: Phone for SMS/WhatsApp
                            </div>
                        </div>

                        {/* Scheduled Date */}
                        <div className="col-md-6 mb-5">
                            <label className="form-label">Scheduled Date</label>
                            <input
                                type="date"
                                name="scheduled_date"
                                className="form-control"
                                value={formData.scheduled_date}
                                onChange={handleChange}
                                min={new Date().toISOString().split('T')[0]}
                            />
                            <div className="form-text">
                                Optional: Schedule when this link becomes active
                            </div>
                        </div>

                        {/* Expiry Date */}
                        <div className="col-md-6 mb-5">
                            <label className="form-label">Expiry Date</label>
                            <input
                                type="date"
                                name="expired_date"
                                className="form-control"
                                value={formData.expired_date}
                                onChange={handleChange}
                                min={new Date().toISOString().split('T')[0]}
                            />
                            <div className="form-text">
                                Optional: Set when this link expires
                            </div>
                        </div>

                        {/* Payment Method Types */}
                        <div className="col-md-12 mb-5">
                            <label className="form-label required">Payment Methods</label>
                            <select
                                name="payment_method_types"
                                className="form-select"
                                value={formData.payment_method_types}
                                onChange={handleChange}
                                multiple
                                size="8"
                                required
                            >
                                {paymentMethods.map(method => (
                                    <option key={method.value} value={method.value}>
                                        {method.label}
                                    </option>
                                ))}
                            </select>
                            <div className="form-text">
                                Hold Ctrl/Cmd to select multiple payment methods
                            </div>
                        </div>
                    </div>
                </div>

                <div className="card-footer d-flex justify-content-end py-6">
                    <a
                        href="/merchant/payment-links"
                        className="btn btn-light btn-active-light-primary me-2"
                    >
                        Cancel
                    </a>
                    <button
                        type="submit"
                        className="btn btn-primary"
                        disabled={loading}
                    >
                        {loading ? (
                            <>
                                <span className="spinner-border spinner-border-sm me-2"></span>
                                Saving...
                            </>
                        ) : (
                            <>
                                <i className="ki-duotone ki-check fs-2"></i>
                                {mode === 'create' ? 'Create' : 'Update'} Payment Link
                            </>
                        )}
                    </button>
                </div>
            </form>
        </div>
    );
};

export default PaymentLinkForm;

