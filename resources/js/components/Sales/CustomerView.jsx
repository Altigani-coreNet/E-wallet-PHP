import React, { useState, useEffect } from 'react';
import { get, del, getToken } from '../../utils/api';
import { API_ENDPOINTS } from '../../utils/constants';
import { useParams } from 'react-router-dom';

export default function CustomerView() {
    const { id } = useParams();
    const [customer, setCustomer] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        fetchCustomerDetails();
    }, [id]);

    const fetchCustomerDetails = async () => {
        try {
            setLoading(true);
            const token = getToken();
            if (!token) {
                setError('Authentication required. Please login again.');
                setLoading(false);
                return;
            }

            const response = await get(API_ENDPOINTS.CUSTOMERS.DETAILS(id));
            
            if (response.data.success !== false) {
                const customerData = response.data.data?.customers || response.data.data?.customer;
                setCustomer(customerData);
            } else {
                setError(response.data.message || 'Failed to load customer');
            }
        } catch (error) {
            console.error('Error fetching customer:', error);
            setError('Failed to load customer details');
        } finally {
            setLoading(false);
        }
    };

    const handleBack = () => {
        window.location.href = '/merchant/sales/customers';
    };

    const handleEdit = () => {
        window.location.href = `/merchant/sales/customers/${id}/edit`;
    };

    const handleDelete = async () => {
        if (!confirm(`Are you sure you want to delete customer "${customer.name}"? This action cannot be undone.`)) {
            return;
        }

        try {
            const response = await del(API_ENDPOINTS.CUSTOMERS.DELETE(id));
            
            if (response.data.success !== false) {
                alert('Customer deleted successfully!');
                window.location.href = '/merchant/sales/customers';
            } else {
                alert(response.data.message || 'Failed to delete customer');
            }
        } catch (err) {
            console.error('Error deleting customer:', err);
            alert('Failed to delete customer');
        }
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

    if (error) {
        return (
            <>
                <div id="kt_app_toolbar" className="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" className="app-container container-xxl d-flex flex-stack">
                        <div className="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                            <h1 className="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                                Customer Details
                            </h1>
                        </div>
                    </div>
                </div>
                <div id="kt_app_content" className="app-content flex-column-fluid">
                    <div id="kt_app_content_container" className="app-container container-xxl">
                        <div className="alert alert-danger" role="alert">
                            <i className="ki-duotone ki-information fs-2x me-3">
                                <span className="path1"></span>
                                <span className="path2"></span>
                                <span className="path3"></span>
                            </i>
                            {error}
                        </div>
                    </div>
                </div>
            </>
        );
    }

    if (!customer) {
        return <div className="alert alert-warning">Customer not found</div>;
    }

    return (
        <>
            {/* Toolbar */}
            <div id="kt_app_toolbar" className="app-toolbar py-3 py-lg-6">
                <div id="kt_app_toolbar_container" className="app-container container-xxl d-flex flex-stack">
                    {/* Page title */}
                    <div className="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                        <h1 className="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                            Customer Details
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
                                <a href="/merchant/sales/customers" className="text-muted text-hover-primary">
                                    Customers
                                </a>
                            </li>
                            <li className="breadcrumb-item">
                                <span className="bullet bg-gray-500 w-5px h-2px"></span>
                            </li>
                            <li className="breadcrumb-item text-gray-900">Details #{id}</li>
                        </ul>
                    </div>
                    
                    {/* Toolbar Actions */}
                    <div className="d-flex align-items-center gap-2">
                        <button type="button" className="btn btn-sm btn-light" onClick={handleBack}>
                            <i className="ki-duotone ki-arrow-left fs-3">
                                <span className="path1"></span>
                                <span className="path2"></span>
                            </i>
                            Back
                        </button>
                        <button type="button" className="btn btn-sm btn-light-primary" onClick={handleEdit}>
                            <i className="ki-duotone ki-pencil fs-3">
                                <span className="path1"></span>
                                <span className="path2"></span>
                            </i>
                            Edit Customer
                        </button>
                    </div>
                </div>
            </div>

            {/* Content */}
            <div id="kt_app_content" className="app-content flex-column-fluid">
                <div id="kt_app_content_container" className="app-container container-xxl">
                    
                    <div className="d-flex flex-column flex-xl-row">
                        {/* Sidebar */}
                        <div className="flex-column flex-lg-row-auto w-100 w-xl-350px mb-10">
                            <div className="card mb-5 mb-xl-8">
                                <div className="card-body pt-15">
                                    {/* Summary */}
                                    <div className="d-flex flex-center flex-column mb-5">
                                        {/* Avatar */}
                                        <div className="symbol symbol-150px symbol-circle mb-7">
                                            <div className="symbol-label fs-2x fw-bold bg-light-primary text-primary">
                                                {customer.name?.charAt(0).toUpperCase() || 'C'}
                                            </div>
                                        </div>
                                        {/* Name */}
                                        <a href="#" className="fs-3 text-gray-800 text-hover-primary fw-bold mb-1">
                                            {customer.name}
                                        </a>
                                        {/* Email */}
                                        <a href={`mailto:${customer.email}`} className="fs-5 fw-semibold text-muted text-hover-primary mb-6">
                                            {customer.email}
                                        </a>
                                    </div>
                                    
                                    {/* Details toggle */}
                                    <div className="d-flex flex-stack fs-4 py-3">
                                        <div className="fw-bold">Details</div>
                                        <div className="badge badge-light-info d-inline">Active Customer</div>
                                    </div>
                                    <div className="separator separator-dashed my-3"></div>
                                    
                                    {/* Details content */}
                                    <div className="pb-5 fs-6">
                                        {/* Customer ID */}
                                        <div className="fw-bold mt-5">Customer ID</div>
                                        <div className="text-gray-600">#{customer.id}</div>
                                        
                                        {/* Email */}
                                        <div className="fw-bold mt-5">Email</div>
                                        <div className="text-gray-600">
                                            <a href={`mailto:${customer.email}`} className="text-gray-600 text-hover-primary">
                                                {customer.email}
                                            </a>
                                        </div>
                                        
                                        {/* Phone */}
                                        <div className="fw-bold mt-5">Phone</div>
                                        <div className="text-gray-600">
                                            {customer.phone || customer.phone_number ? (
                                                <a href={`tel:${customer.phone || customer.phone_number}`} className="text-gray-600 text-hover-primary">
                                                    {customer.phone || customer.phone_number}
                                                </a>
                                            ) : 'No phone provided'}
                                        </div>
                                        
                                        {/* Address */}
                                        <div className="fw-bold mt-5">Address</div>
                                        <div className="text-gray-600">
                                            {customer.address || customer.city || customer.state || customer.postal_code || customer.zip ? (
                                                <>
                                                    {customer.address && <>{customer.address}<br /></>}
                                                    {(customer.city || customer.state || customer.postal_code || customer.zip) && (
                                                        <>{[customer.city, customer.state, customer.postal_code || customer.zip].filter(Boolean).join(', ')}</>
                                                    )}
                                                </>
                                            ) : 'No address provided'}
                                        </div>
                                        
                                        {/* Customer Group */}
                                        <div className="fw-bold mt-5">Customer Group</div>
                                        <div className="text-gray-600">
                                            {customer.customer_group?.name || 'No group assigned'}
                                        </div>
                                        
                                        {/* Created */}
                                        <div className="fw-bold mt-5">Created</div>
                                        <div className="text-gray-600">
                                            {customer.created_at ? new Date(customer.created_at).toLocaleDateString('en-US', {
                                                year: 'numeric',
                                                month: 'short',
                                                day: 'numeric'
                                            }) : 'N/A'}
                                        </div>
                                        
                                        {/* Last Updated */}
                                        <div className="fw-bold mt-5">Last Updated</div>
                                        <div className="text-gray-600">
                                            {customer.updated_at ? new Date(customer.updated_at).toLocaleDateString('en-US', {
                                                year: 'numeric',
                                                month: 'short',
                                                day: 'numeric',
                                                hour: '2-digit',
                                                minute: '2-digit',
                                                second: '2-digit'
                                            }) : 'N/A'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {/* Main Content */}
                        <div className="flex-lg-row-fluid ms-lg-15">
                            {/* Tabs */}
                            <ul className="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8" role="tablist">
                                <li className="nav-item" role="presentation">
                                    <a 
                                        className={`nav-link text-active-primary pb-4 ${activeTab === 'overview' ? 'active' : ''}`}
                                        onClick={() => setActiveTab('overview')}
                                        role="tab"
                                        style={{cursor: 'pointer'}}
                                    >
                                        Overview
                                    </a>
                                </li>
                                <li className="nav-item" role="presentation">
                                    <a 
                                        className={`nav-link text-active-primary pb-4 ${activeTab === 'general' ? 'active' : ''}`}
                                        onClick={() => setActiveTab('general')}
                                        role="tab"
                                        style={{cursor: 'pointer'}}
                                    >
                                        General Settings
                                    </a>
                                </li>
                                <li className="nav-item" role="presentation">
                                    <a 
                                        className={`nav-link text-active-primary pb-4 ${activeTab === 'advanced' ? 'active' : ''}`}
                                        onClick={() => setActiveTab('advanced')}
                                        role="tab"
                                        style={{cursor: 'pointer'}}
                                    >
                                        Advanced Settings
                                    </a>
                                </li>
                            </ul>
                            
                            {/* Tab Content */}
                            <div className="tab-content">
                                {/* Overview Tab */}
                                {activeTab === 'overview' && (
                                    <div className="tab-pane fade show active">
                                        <div className="row row-cols-1 row-cols-md-2 mb-6 mb-xl-9">
                                            {/* Account Status Card */}
                                            <div className="col">
                                                <div className="card pt-4 h-md-100 mb-6 mb-md-0">
                                                    <div className="card-header border-0">
                                                        <div className="card-title">
                                                            <h2 className="fw-bold">Account Status</h2>
                                                        </div>
                                                    </div>
                                                    <div className="card-body pt-0">
                                                        <div className="fw-bold fs-2">
                                                            <div className="d-flex">
                                                                <i className="ki-duotone ki-check-circle text-success fs-2x">
                                                                    <span className="path1"></span>
                                                                    <span className="path2"></span>
                                                                </i>
                                                                <div className="ms-2">
                                                                    Active
                                                                    <span className="text-muted fs-4 fw-semibold d-block">Customer Account</span>
                                                                </div>
                                                            </div>
                                                            <div className="fs-7 fw-normal text-muted mt-3">
                                                                Customer account is active and operational.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            {/* Shop Info Card */}
                                            <div className="col">
                                                <div className="card bg-primary hoverable h-md-100">
                                                    <div className="card-body">
                                                        <i className="ki-duotone ki-shop text-white fs-3x ms-n1">
                                                            <span className="path1"></span>
                                                            <span className="path2"></span>
                                                            <span className="path3"></span>
                                                        </i>
                                                        <div className="text-white fw-bold fs-2 mt-5">
                                                            {customer.shop?.name || 'No Shop'}
                                                        </div>
                                                        <div className="fw-semibold text-white">Associated Shop</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        {/* Customer Information Card */}
                                        <div className="card pt-4 mb-6 mb-xl-9">
                                            <div className="card-header border-0">
                                                <div className="card-title">
                                                    <h2>Customer Information</h2>
                                                </div>
                                                <div className="card-toolbar">
                                                    <button onClick={handleEdit} className="btn btn-sm btn-light-primary">
                                                        <i className="ki-duotone ki-pencil fs-3">
                                                            <span className="path1"></span>
                                                            <span className="path2"></span>
                                                        </i>
                                                        Edit Customer
                                                    </button>
                                                </div>
                                            </div>
                                            <div className="card-body pt-0 pb-5">
                                                <div className="table-responsive">
                                                    <table className="table align-middle table-row-dashed gy-5">
                                                        <tbody className="fs-6 fw-semibold text-gray-600">
                                                            <tr>
                                                                <td className="text-muted min-w-125px w-125px">Customer ID</td>
                                                                <td className="text-gray-800">#{customer.id}</td>
                                                            </tr>
                                                            <tr>
                                                                <td className="text-muted min-w-125px w-125px">Name</td>
                                                                <td className="text-gray-800">{customer.name}</td>
                                                            </tr>
                                                            <tr>
                                                                <td className="text-muted min-w-125px w-125px">Email</td>
                                                                <td className="text-gray-800">
                                                                    <a href={`mailto:${customer.email}`} className="text-gray-900 text-hover-primary">
                                                                        {customer.email}
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td className="text-muted min-w-125px w-125px">Phone</td>
                                                                <td className="text-gray-800">
                                                                    {customer.phone || customer.phone_number ? (
                                                                        <a href={`tel:${customer.phone || customer.phone_number}`} className="text-gray-900 text-hover-primary">
                                                                            {customer.phone || customer.phone_number}
                                                                        </a>
                                                                    ) : 'No phone provided'}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td className="text-muted min-w-125px w-125px">Address</td>
                                                                <td className="text-gray-800">
                                                                    {customer.address || customer.city || customer.state || customer.postal_code || customer.zip ? (
                                                                        <>
                                                                            {customer.address && <>{customer.address}<br /></>}
                                                                            {(customer.city || customer.state || customer.postal_code || customer.zip) && (
                                                                                <>{[customer.city, customer.state, customer.postal_code || customer.zip].filter(Boolean).join(', ')}</>
                                                                            )}
                                                                        </>
                                                                    ) : 'No address provided'}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td className="text-muted min-w-125px w-125px">Created</td>
                                                                <td className="text-gray-800">
                                                                    {customer.created_at ? new Date(customer.created_at).toLocaleDateString('en-US', {
                                                                        year: 'numeric',
                                                                        month: 'short',
                                                                        day: 'numeric',
                                                                        hour: '2-digit',
                                                                        minute: '2-digit',
                                                                        second: '2-digit'
                                                                    }) : 'N/A'}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td className="text-muted min-w-125px w-125px">Last Updated</td>
                                                                <td className="text-gray-800">
                                                                    {customer.updated_at ? new Date(customer.updated_at).toLocaleDateString('en-US', {
                                                                        year: 'numeric',
                                                                        month: 'short',
                                                                        day: 'numeric',
                                                                        hour: '2-digit',
                                                                        minute: '2-digit',
                                                                        second: '2-digit'
                                                                    }) : 'N/A'}
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        {/* Quick Actions Card */}
                                        <div className="card pt-4 mb-6 mb-xl-9">
                                            <div className="card-header border-0">
                                                <div className="card-title">
                                                    <h2 className="fw-bold mb-0">Quick Actions</h2>
                                                </div>
                                            </div>
                                            <div className="card-body pt-0">
                                                <div className="d-flex flex-wrap gap-3">
                                                    <button onClick={handleEdit} className="btn btn-light-primary">
                                                        <i className="ki-duotone ki-pencil fs-3">
                                                            <span className="path1"></span>
                                                            <span className="path2"></span>
                                                        </i>
                                                        Edit Customer
                                                    </button>
                                                    <button onClick={handleDelete} className="btn btn-light-danger">
                                                        <i className="ki-duotone ki-trash fs-3">
                                                            <span className="path1"></span>
                                                            <span className="path2"></span>
                                                            <span className="path3"></span>
                                                            <span className="path4"></span>
                                                            <span className="path5"></span>
                                                        </i>
                                                        Delete Customer
                                                    </button>
                                                    <button onClick={handleBack} className="btn btn-light-secondary">
                                                        <i className="ki-duotone ki-arrow-left fs-3">
                                                            <span className="path1"></span>
                                                            <span className="path2"></span>
                                                        </i>
                                                        Back to Customers
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                )}
                                
                                {/* General Settings Tab */}
                                {activeTab === 'general' && (
                                    <div className="tab-pane fade show active">
                                        <div className="card pt-4 mb-6 mb-xl-9">
                                            <div className="card-header border-0">
                                                <div className="card-title">
                                                    <h2>Profile Information</h2>
                                                </div>
                                            </div>
                                            <div className="card-body pt-0 pb-5">
                                                <div className="table-responsive">
                                                    <table className="table align-middle table-row-dashed gy-5">
                                                        <tbody className="fs-6 fw-semibold text-gray-600">
                                                            <tr>
                                                                <td className="text-muted min-w-125px w-125px">Full Name</td>
                                                                <td className="text-gray-800">{customer.name}</td>
                                                            </tr>
                                                            <tr>
                                                                <td className="text-muted min-w-125px w-125px">Company Name</td>
                                                                <td className="text-gray-800">{customer.company_name || 'N/A'}</td>
                                                            </tr>
                                                            <tr>
                                                                <td className="text-muted min-w-125px w-125px">Email Address</td>
                                                                <td className="text-gray-800">{customer.email}</td>
                                                            </tr>
                                                            <tr>
                                                                <td className="text-muted min-w-125px w-125px">Phone Number</td>
                                                                <td className="text-gray-800">{customer.phone || customer.phone_number || 'N/A'}</td>
                                                            </tr>
                                                            <tr>
                                                                <td className="text-muted min-w-125px w-125px">Country</td>
                                                                <td className="text-gray-800">{customer.country || 'N/A'}</td>
                                                            </tr>
                                                            <tr>
                                                                <td className="text-muted min-w-125px w-125px">City</td>
                                                                <td className="text-gray-800">{customer.city || 'N/A'}</td>
                                                            </tr>
                                                            <tr>
                                                                <td className="text-muted min-w-125px w-125px">State</td>
                                                                <td className="text-gray-800">{customer.state || 'N/A'}</td>
                                                            </tr>
                                                            <tr>
                                                                <td className="text-muted min-w-125px w-125px">Postal Code</td>
                                                                <td className="text-gray-800">{customer.postal_code || customer.zip || 'N/A'}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                )}
                                
                                {/* Advanced Settings Tab */}
                                {activeTab === 'advanced' && (
                                    <div className="tab-pane fade show active">
                                        <div className="card pt-4 mb-6 mb-xl-9">
                                            <div className="card-header border-0">
                                                <div className="card-title">
                                                    <h2>Shop Information</h2>
                                                </div>
                                            </div>
                                            <div className="card-body pt-0 pb-5">
                                                {customer.shop ? (
                                                    <div className="table-responsive">
                                                        <table className="table align-middle table-row-dashed gy-5">
                                                            <tbody className="fs-6 fw-semibold text-gray-600">
                                                                <tr>
                                                                    <td className="text-muted min-w-125px w-125px">Shop ID</td>
                                                                    <td className="text-gray-800">#{customer.shop.id}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td className="text-muted min-w-125px w-125px">Shop Name</td>
                                                                    <td className="text-gray-800">{customer.shop.name}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td className="text-muted min-w-125px w-125px">Status</td>
                                                                    <td className="text-gray-800">
                                                                        <span className="badge badge-success">Active</span>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                ) : (
                                                    <div className="text-center py-10">
                                                        <div className="text-muted fs-6">No shop assigned to this customer.</div>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </>
    );
}

