import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { apiGet } from '../../utils/apiUtils';
import LoadingSpinner from '../common/LoadingSpinner';

const POS_API_BASE = 'http://localhost:8002';

const InvoicePrint = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const [invoice, setInvoice] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        const fetchInvoice = async () => {
            try {
                setLoading(true);
                const response = await apiGet(`${POS_API_BASE}/api/v1/pos/invoice/${id}`);
                
                if (response.success) {
                    // console.log(response.data);
                    setInvoice(response.data.data);
                } else {
                    setError(response.error?.message || 'Failed to load invoice');
                }
            } catch (err) {
                console.error('Error fetching invoice:', err);
                setError('Failed to load invoice. Please try again.');
            } finally {
                setLoading(false);
            }
        };

        if (id) {
            fetchInvoice();
        }
    }, [id]);

    const handlePrint = () => {
        window.print();
    };

    const handleBack = () => {
        navigate('/merchant/sales/sale');
    };

    const formatCurrency = (amount) => {
        const currency = invoice?.shop?.currency || '$';
        return `${currency}${parseFloat(amount || 0).toFixed(2)}`;
    };

    const formatDate = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    if (loading) {
        return (
            <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
                <LoadingSpinner />
            </div>
        );
    }

    if (error) {
        return (
            <div className="container mt-5">
                <div className="alert alert-danger" role="alert">
                    <h4 className="alert-heading">Error</h4>
                    <p>{error}</p>
                    <hr />
                    <button className="btn btn-primary" onClick={handleBack}>
                        Back to POS
                    </button>
                </div>
            </div>
        );
    }

    if (!invoice) {
        return null;
    }

    return (
        <div className="invoice-container">
            {/* Print Controls */}
            <div className="no-print mb-4 d-flex justify-content-between align-items-center" style={{ padding: '20px' }}>
                <button className="btn btn-secondary" onClick={handleBack}>
                    <i className="ki-duotone ki-arrow-left fs-2">
                        <span className="path1"></span>
                        <span className="path2"></span>
                    </i>
                    Back to POS
                </button>
                <button className="btn btn-primary" onClick={handlePrint}>
                    <i className="ki-duotone ki-printer fs-2">
                        <span className="path1"></span>
                        <span className="path2"></span>
                        <span className="path3"></span>
                    </i>
                    Print Invoice
                </button>
            </div>

            {/* Receipt Content */}
            <div className="receipt-wrapper">
                <div className="receipt-content">
                    {/* Logo & Branding */}
                    <div className="receipt-header">
                        <div className="receipt-logo">
                            <div className="logo-icon">FP</div>
                            <div className="logo-text">fastpay</div>
                        </div>
                    </div>

                    {/* Merchant Info */}
                    <div className="merchant-info">
                        <div className="merchant-name">{invoice.shop?.name || 'FastPay Merchant'}</div>
                        <div className="merchant-address">
                            {invoice.shop?.address || '123 App Street, Flutter City'}
                        </div>
                        {invoice.shop?.phone && (
                            <div className="merchant-address">Phone: {invoice.shop.phone}</div>
                        )}
                    </div>

                    {/* Dashed Separator */}
                    <div className="receipt-divider"></div>

                    {/* Order Summary */}
                    <div className="order-summary-title">Order Summary</div>

                    {/* Items List */}
                    <div className="items-list">
                        {invoice.products && invoice.products.map((product, index) => (
                            <div key={index} className="item-row">
                                <div className="item-name">
                                    {product.name} (x{product.qty})
                                </div>
                                <div className="item-price">
                                    {formatCurrency(product.total)}
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Dashed Separator */}
                    <div className="receipt-divider"></div>

                    {/* Totals */}
                    <div className="totals-section">
                        <div className="total-row">
                            <span>Subtotal</span>
                            <span>{formatCurrency(invoice.total_price)}</span>
                        </div>
                        {invoice.total_tax > 0 && (
                            <div className="total-row">
                                <span>VAT ({((invoice.total_tax / invoice.total_price) * 100).toFixed(0)}%)</span>
                                <span>{formatCurrency(invoice.total_tax)}</span>
                            </div>
                        )}
                        {invoice.total_discount > 0 && (
                            <div className="total-row">
                                <span>Discount</span>
                                <span>-{formatCurrency(invoice.total_discount)}</span>
                            </div>
                        )}
                        <div className="total-row total-final">
                            <span>Total</span>
                            <span>{formatCurrency(invoice.grand_total)}</span>
                        </div>
                    </div>

                    {/* Dashed Separator */}
                    <div className="receipt-divider"></div>

                    {/* QR Code Section */}
                    <div className="qr-section">
                        <div className="qr-title">Scan QR For E-Receipt</div>
                        <div className="qr-code">
                            <img 
                                src={`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(
                                    invoice.invoice_url || 
                                    `${window.location.origin}/merchant/sales/invoice/${invoice.id || id}` ||
                                    window.location.href
                                )}`}
                                alt="Receipt QR Code"
                            />
                        </div>
                        <div className="qr-instruction">
                            Use your phone camera to download receipt
                        </div>
                    </div>
                </div>
            </div>

            {/* Styles */}
            <style>{`
                .invoice-container {
                    min-height: 100vh;
                    background: #f5f5f5;
                }

                .receipt-wrapper {
                    display: flex;
                    justify-content: center;
                    padding: 20px;
                }

                .receipt-content {
                    width: 100%;
                    max-width: 400px;
                    background: white;
                    padding: 30px;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
                    color: #000;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }

                /* Header & Logo */
                .receipt-header {
                    text-align: center;
                    margin-bottom: 20px;
                }

                .receipt-logo {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    margin-bottom: 15px;
                }

                .logo-icon {
                    width: 40px;
                    height: 40px;
                    background: #000;
                    color: white;
                    border-radius: 8px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    font-size: 18px;
                }

                .logo-text {
                    font-size: 24px;
                    font-weight: 600;
                    color: #000;
                    letter-spacing: -0.5px;
                }

                /* Merchant Info */
                .merchant-info {
                    text-align: center;
                    margin-bottom: 20px;
                }

                .merchant-name {
                    font-size: 20px;
                    font-weight: bold;
                    color: #000;
                    margin-bottom: 8px;
                }

                .merchant-address {
                    font-size: 13px;
                    color: #666;
                    line-height: 1.5;
                }

                /* Divider */
                .receipt-divider {
                    border-top: 2px dashed #ccc;
                    margin: 20px 0;
                }

                /* Order Summary */
                .order-summary-title {
                    text-align: center;
                    font-size: 16px;
                    font-weight: bold;
                    color: #000;
                    margin-bottom: 15px;
                }

                /* Items List */
                .items-list {
                    margin-bottom: 15px;
                }

                .item-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 8px;
                    font-size: 14px;
                    color: #000;
                }

                .item-name {
                    flex: 1;
                    text-align: left;
                }

                .item-price {
                    text-align: right;
                    white-space: nowrap;
                    margin-left: 15px;
                }

                /* Totals */
                .totals-section {
                    margin-bottom: 15px;
                }

                .total-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 8px;
                    font-size: 14px;
                    color: #000;
                }

                .total-final {
                    font-weight: bold;
                    font-size: 16px;
                    margin-top: 5px;
                }

                /* QR Section */
                .qr-section {
                    text-align: center;
                    padding-top: 10px;
                }

                .qr-title {
                    font-size: 14px;
                    font-weight: 600;
                    color: #000;
                    margin-bottom: 15px;
                }

                .qr-code {
                    display: flex;
                    justify-content: center;
                    margin-bottom: 15px;
                }

                .qr-code img {
                    width: 180px;
                    height: 180px;
                    border: 1px solid #e0e0e0;
                }

                .qr-instruction {
                    font-size: 12px;
                    color: #666;
                }

                /* Print Styles */
                @media print {
                    .no-print {
                        display: none !important;
                    }

                    .invoice-container {
                        background: white !important;
                    }

                    .receipt-wrapper {
                        padding: 0 !important;
                    }

                    .receipt-content {
                        max-width: 100% !important;
                        box-shadow: none !important;
                        padding: 20px !important;
                    }

                    #kt_app_sidebar,
                    #kt_app_header,
                    #kt_app_toolbar,
                    nav,
                    aside {
                        display: none !important;
                    }

                    @page {
                        margin: 1cm;
                        size: 80mm auto;
                    }
                }
            `}</style>
        </div>
    );
};

export default InvoicePrint;
