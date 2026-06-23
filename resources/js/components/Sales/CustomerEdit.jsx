import React, { useState, useEffect } from 'react';
import { get, put, getToken } from '../../utils/api';
import { API_ENDPOINTS } from '../../utils/constants';
import { useParams } from 'react-router-dom';

export default function CustomerEdit() {
    const { id } = useParams();
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        phone: '',
        company_name: '',
        address: '',
        city: '',
        state: '',
        postal_code: '',
        country: '',
        tax_no: '',
        customer_group_id: '',
    });

    const [customerGroups, setCustomerGroups] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});

    useEffect(() => {
        fetchCustomerGroups();
        fetchCustomerDetails();
    }, [id]);

    const fetchCustomerGroups = async () => {
        try {
            const response = await get(API_ENDPOINTS.CUSTOMERS.GROUPS);
            if (response.data.success !== false) {
                setCustomerGroups(response.data.data?.customerGroups || []);
            }
        } catch (error) {
            console.error('Error fetching customer groups:', error);
        }
    };

    const fetchCustomerDetails = async () => {
        try {
            setLoading(true);
            const token = getToken();
            if (!token) {
                setErrors({ general: 'Authentication required. Please login again.' });
                setLoading(false);
                return;
            }

            const response = await get(API_ENDPOINTS.CUSTOMERS.DETAILS(id));
            
            if (response.data.success !== false) {
                const customer = response.data.data?.customers || response.data.data?.customer;
                if (customer) {
                    setFormData({
                        name: customer.name || '',
                        email: customer.email || '',
                        phone: customer.phone || customer.phone_number || '',
                        company_name: customer.company_name || '',
                        address: customer.address || '',
                        city: customer.city || '',
                        state: customer.state || '',
                        postal_code: customer.postal_code || customer.zip || '',
                        country: customer.country || '',
                        tax_no: customer.tax_no || '',
                        customer_group_id: customer.customer_group_id || '',
                    });
                }
            } else {
                setErrors({ general: response.data.message || 'Failed to load customer' });
            }
        } catch (error) {
            console.error('Error fetching customer:', error);
            setErrors({ general: 'Failed to load customer details' });
        } finally {
            setLoading(false);
        }
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
        // Clear error for this field
        if (errors[name]) {
            setErrors(prev => ({
                ...prev,
                [name]: null
            }));
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setErrors({});

        try {
            const token = getToken();
            if (!token) {
                setErrors({ general: 'Authentication required. Please login again.' });
                setSaving(false);
                return;
            }

            // Prepare data - remove empty customer_group_id
            const submitData = { ...formData };
            if (!submitData.customer_group_id) {
                submitData.customer_group_id = null;
            }

            const response = await put(API_ENDPOINTS.CUSTOMERS.UPDATE(id), submitData);
            
            if (response.data.success !== false) {
                // Navigate back to customers list immediately
                window.location.href = '/merchant/sales/customers';
            } else {
                setErrors({ general: response.data.message || 'Failed to update customer' });
                setSaving(false);
            }

        } catch (err) {
            console.error('Error updating customer:', err);
            
            if (err.response?.data?.errors) {
                setErrors(err.response.data.errors);
            } else {
                setErrors({
                    general: err.response?.data?.message || 'Failed to update customer'
                });
            }
            setSaving(false);
        }
    };

    const handleCancel = () => {
        const event = new CustomEvent('spa-navigate', {
            detail: { route: '/merchant/sales/customers' }
        });
        window.dispatchEvent(event);
    };

    if (loading) {
        return (
            <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
                <div className="spinner-border text-primary" role="status">
                    <span className="visually-hidden">Loading...</span>
                </div>
            </div>
        );
    }

    return (
        <>
            {/* Toolbar */}
            <div id="kt_app_toolbar" className="app-toolbar py-3 py-lg-6">
                <div id="kt_app_toolbar_container" className="app-container container-xxl d-flex flex-stack">
                    {/* Page title */}
                    <div className="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                        <h1 className="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                            Edit Customer
                        </h1>
                        
                        {/* Breadcrumb */}
                        <ul className="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                            <li className="breadcrumb-item text-muted">
                                <a href="/merchant/dashboard" className="text-muted text-hover-primary">Home</a>
                            </li>
                            <li className="breadcrumb-item">
                                <span className="bullet bg-gray-500 w-5px h-2px"></span>
                            </li>
                            <li className="breadcrumb-item text-muted">Sales</li>
                            <li className="breadcrumb-item">
                                <span className="bullet bg-gray-500 w-5px h-2px"></span>
                            </li>
                            <li className="breadcrumb-item text-muted">
                                <a 
                                    href="/merchant/sales/customers" 
                                    className="text-muted text-hover-primary"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        handleCancel();
                                    }}
                                >
                                    Customers
                                </a>
                            </li>
                            <li className="breadcrumb-item">
                                <span className="bullet bg-gray-500 w-5px h-2px"></span>
                            </li>
                            <li className="breadcrumb-item text-gray-900">Edit #{id}</li>
                        </ul>
                    </div>
                    
                    {/* Toolbar Actions */}
                    <div className="d-flex align-items-center gap-2">
                        <button
                            type="button"
                            className="btn btn-sm btn-light"
                            onClick={handleCancel}
                        >
                            <i className="ki-duotone ki-arrow-left fs-3">
                                <span className="path1"></span>
                                <span className="path2"></span>
                            </i>
                            Back to List
                        </button>
                    </div>
                </div>
            </div>

            {/* Content */}
            <div id="kt_app_content" className="app-content flex-column-fluid">
                <div id="kt_app_content_container" className="app-container container-xxl">
                    <div className="card">
                        {/* Card Body */}
                        <div className="card-body">
                            {/* General Error */}
                            {errors.general && (
                                <div className="alert alert-danger d-flex align-items-center p-5 mb-10">
                                    <i className="ki-duotone ki-shield-cross fs-2hx text-danger me-4">
                                        <span className="path1"></span>
                                        <span className="path2"></span>
                                        <span className="path3"></span>
                                    </i>
                                    <div className="d-flex flex-column">
                                        <h4 className="mb-1 text-danger">Error</h4>
                                        <span>{errors.general}</span>
                                    </div>
                                </div>
                            )}

                            <form onSubmit={handleSubmit}>
                                <div className="row">
                                    <h4 className="mb-6">Customer Information</h4>

                                    {/* Name */}
                                    <div className="col-md-6 mb-6">
                                        <label className="required form-label">Customer Name</label>
                                        <input
                                            type="text"
                                            name="name"
                                            className={`form-control form-control-solid ${errors.name ? 'is-invalid' : ''}`}
                                            placeholder="Enter customer name"
                                            value={formData.name}
                                            onChange={handleChange}
                                            required
                                        />
                                        {errors.name && (
                                            <div className="invalid-feedback d-block">{errors.name[0]}</div>
                                        )}
                                    </div>

                                    {/* Email */}
                                    <div className="col-md-6 mb-6">
                                        <label className="required form-label">Email</label>
                                        <input
                                            type="email"
                                            name="email"
                                            className={`form-control form-control-solid ${errors.email ? 'is-invalid' : ''}`}
                                            placeholder="customer@example.com"
                                            value={formData.email}
                                            onChange={handleChange}
                                            required
                                        />
                                        {errors.email && (
                                            <div className="invalid-feedback d-block">{errors.email[0]}</div>
                                        )}
                                    </div>

                                    {/* Phone */}
                                    <div className="col-md-6 mb-6">
                                        <label className="form-label">Phone</label>
                                        <input
                                            type="text"
                                            name="phone"
                                            className={`form-control form-control-solid ${errors.phone ? 'is-invalid' : ''}`}
                                            placeholder="+1 234 567 8900"
                                            value={formData.phone}
                                            onChange={handleChange}
                                        />
                                        {errors.phone && (
                                            <div className="invalid-feedback d-block">{errors.phone[0]}</div>
                                        )}
                                    </div>

                                    {/* Company Name */}
                                    <div className="col-md-6 mb-6">
                                        <label className="form-label">Company Name</label>
                                        <input
                                            type="text"
                                            name="company_name"
                                            className={`form-control form-control-solid ${errors.company_name ? 'is-invalid' : ''}`}
                                            placeholder="Company name"
                                            value={formData.company_name}
                                            onChange={handleChange}
                                        />
                                        {errors.company_name && (
                                            <div className="invalid-feedback d-block">{errors.company_name[0]}</div>
                                        )}
                                    </div>

                                    {/* Customer Group */}
                                    <div className="col-md-6 mb-6">
                                        <label className="form-label">Customer Group</label>
                                        <select
                                            name="customer_group_id"
                                            className={`form-select form-select-solid ${errors.customer_group_id ? 'is-invalid' : ''}`}
                                            value={formData.customer_group_id}
                                            onChange={handleChange}
                                        >
                                            <option value="">Select Group (Optional)</option>
                                            {customerGroups.map(group => (
                                                <option key={group.id} value={group.id}>
                                                    {group.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.customer_group_id && (
                                            <div className="invalid-feedback d-block">{errors.customer_group_id[0]}</div>
                                        )}
                                    </div>

                                    {/* Tax No */}
                                    <div className="col-md-6 mb-6">
                                        <label className="form-label">Tax Number</label>
                                        <input
                                            type="text"
                                            name="tax_no"
                                            className={`form-control form-control-solid ${errors.tax_no ? 'is-invalid' : ''}`}
                                            placeholder="TAX123456"
                                            value={formData.tax_no}
                                            onChange={handleChange}
                                        />
                                        {errors.tax_no && (
                                            <div className="invalid-feedback d-block">{errors.tax_no[0]}</div>
                                        )}
                                    </div>

                                    {/* Address */}
                                    <div className="col-md-12 mb-6">
                                        <label className="form-label">Address</label>
                                        <input
                                            type="text"
                                            name="address"
                                            className={`form-control form-control-solid ${errors.address ? 'is-invalid' : ''}`}
                                            placeholder="Street address"
                                            value={formData.address}
                                            onChange={handleChange}
                                        />
                                        {errors.address && (
                                            <div className="invalid-feedback d-block">{errors.address[0]}</div>
                                        )}
                                    </div>

                                    {/* City */}
                                    <div className="col-md-4 mb-6">
                                        <label className="form-label">City</label>
                                        <input
                                            type="text"
                                            name="city"
                                            className={`form-control form-control-solid ${errors.city ? 'is-invalid' : ''}`}
                                            placeholder="City"
                                            value={formData.city}
                                            onChange={handleChange}
                                        />
                                        {errors.city && (
                                            <div className="invalid-feedback d-block">{errors.city[0]}</div>
                                        )}
                                    </div>

                                    {/* State */}
                                    <div className="col-md-4 mb-6">
                                        <label className="form-label">State</label>
                                        <input
                                            type="text"
                                            name="state"
                                            className={`form-control form-control-solid ${errors.state ? 'is-invalid' : ''}`}
                                            placeholder="State"
                                            value={formData.state}
                                            onChange={handleChange}
                                        />
                                        {errors.state && (
                                            <div className="invalid-feedback d-block">{errors.state[0]}</div>
                                        )}
                                    </div>

                                    {/* Postal Code */}
                                    <div className="col-md-4 mb-6">
                                        <label className="form-label">Postal Code</label>
                                        <input
                                            type="text"
                                            name="postal_code"
                                            className={`form-control form-control-solid ${errors.postal_code ? 'is-invalid' : ''}`}
                                            placeholder="12345"
                                            value={formData.postal_code}
                                            onChange={handleChange}
                                        />
                                        {errors.postal_code && (
                                            <div className="invalid-feedback d-block">{errors.postal_code[0]}</div>
                                        )}
                                    </div>

                                    {/* Country */}
                                    <div className="col-md-12 mb-6">
                                        <label className="form-label">Country</label>
                                        <input
                                            type="text"
                                            name="country"
                                            className={`form-control form-control-solid ${errors.country ? 'is-invalid' : ''}`}
                                            placeholder="Country"
                                            value={formData.country}
                                            onChange={handleChange}
                                        />
                                        {errors.country && (
                                            <div className="invalid-feedback d-block">{errors.country[0]}</div>
                                        )}
                                    </div>
                                </div>

                                {/* Form Actions */}
                                <div className="d-flex justify-content-end mt-10">
                                    <button
                                        type="button"
                                        className="btn btn-light me-3"
                                        onClick={handleCancel}
                                        disabled={saving}
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        className="btn btn-primary"
                                        disabled={saving}
                                    >
                                        {saving ? (
                                            <>
                                                <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                                Updating...
                                            </>
                                        ) : (
                                            <>
                                                <i className="ki-duotone ki-check fs-2"></i>
                                                Update Customer
                                            </>
                                        )}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

