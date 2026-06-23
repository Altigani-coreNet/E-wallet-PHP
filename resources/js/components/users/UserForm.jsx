import React, { useState, useEffect } from 'react';
import { getRoles } from '../../services/rolesService';
import LoadingSpinner from '../common/LoadingSpinner';
import ErrorAlert from '../common/ErrorAlert';

const UserForm = ({ user = null, onSubmit, loading, error, mode = 'create' }) => {
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        phone: '',
        branch_id: '',
        module: 'pos', // Default to POS module
        status: 1,
        roles: []
    });
    
    const [roles, setRoles] = useState([]);
    const [loadingRoles, setLoadingRoles] = useState(true);
    const [selectedRoles, setSelectedRoles] = useState([]);
    const [validationErrors, setValidationErrors] = useState({});

    // Load form data if editing
    useEffect(() => {
        if (user && mode === 'edit') {
            setFormData({
                name: user.name || '',
                email: user.email || '',
                phone: user.phone || '',
                branch_id: user.branch_id || '',
                module: user.module || 'pos',
                status: user.status !== undefined ? user.status : 1,
                roles: user.roles || []
            });
            setSelectedRoles(user.roles?.map(r => r.id) || []);
        }
    }, [user, mode]);

    // Fetch roles from AuthService when component mounts or module changes
    useEffect(() => {
        fetchRoles();
    }, [formData.module]); // Re-fetch when module changes

    const fetchRoles = async () => {
        setLoadingRoles(true);
        try {
            // Build params with module filter
            const params = { per_page: 100 };
            
            // Filter roles by module if selected
            if (formData.module && formData.module !== '') {
                params.module = formData.module;
            }
            
            const response = await getRoles(params);
            
            console.log('📦 Roles Response for module:', formData.module, response);
            console.log('📦 Full response.data:', response.data);
            
            if (response.success) {
                // Handle nested response structure: { success: true, data: { data: [...], meta: {...} } }
                const responseData = response.data.data || {};
                const rolesList = response.data.data.data || response.data.data.roles || [];
                
                console.log('📋 Extracted roles array:', rolesList);
                console.log('📋 Is array?', Array.isArray(rolesList));
                
                // Ensure it's an array
                const rolesArray = Array.isArray(rolesList) ? rolesList : [];
                setRoles(rolesArray);
                
                console.log('✅ Roles state updated with', rolesArray.length, 'roles');
            } else {
                console.error('Failed to fetch roles:', response.error);
                setRoles([]); // Set empty array on error
            }
        } catch (err) {
            console.error('Error fetching roles:', err);
            setRoles([]); // Set empty array on error
        } finally {
            setLoadingRoles(false);
        }
    };

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value
        }));
        
        // Clear validation error for this field when user types
        if (validationErrors[name]) {
            setValidationErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[name];
                return newErrors;
            });
        }
    };

    const handleRoleToggle = (roleId) => {
        setSelectedRoles(prev => {
            if (prev.includes(roleId)) {
                return prev.filter(id => id !== roleId);
            } else {
                return [...prev, roleId];
            }
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Clear previous validation errors
        setValidationErrors({});

        const dataToSubmit = {
            name: formData.name,
            email: formData.email,
            phone: formData.phone,
            branch_id: formData.branch_id || null,
            module: formData.module || null,
            status: formData.status,
            roles: selectedRoles  // Array of role IDs
        };

        console.log('📤 Submitting user data:', dataToSubmit);
        onSubmit(dataToSubmit);
    };
    
    // Parse error to extract validation errors
    useEffect(() => {
        if (error) {
            // Check if error is an object with field-specific errors
            if (typeof error === 'object' && !Array.isArray(error)) {
                setValidationErrors(error);
            } else {
                setValidationErrors({});
            }
        }
    }, [error]);

    return (
        <div className="card">
            {/* Card Header */}
            <div className="card-header border-0 pt-6">
                <div className="card-title">
                    <h2>{mode === 'create' ? 'Add User' : 'Edit User'}</h2>
                </div>
                <div className="card-toolbar">
                    <div className="d-flex justify-content-end">
                        <a href="/merchant/sales/users" className="btn btn-light-danger me-3">
                            <i className="ki-duotone ki-arrow-left fs-2">
                                <span className="path1"></span>
                                <span className="path2"></span>
                            </i>
                            Back
                        </a>
                    </div>
                </div>
            </div>

            {/* Card Body */}
            <div className="card-body">
                {/* Show general error only if it's not a validation error object */}
                {error && typeof error === 'string' && <ErrorAlert message={error} />}

                <form onSubmit={handleSubmit}>
                    <div className="row">
                        {/* Name */}
                        <div className="col-md-6 mb-5">
                            <label className="form-label fs-6 fw-bold required">Name</label>
                            <input
                                type="text"
                                name="name"
                                value={formData.name}
                                onChange={handleChange}
                                className={`form-control form-control-solid ${validationErrors.name ? 'is-invalid' : ''}`}
                                placeholder="Enter name"
                                required
                            />
                            {validationErrors.name && (
                                <div className="invalid-feedback d-block">
                                    {Array.isArray(validationErrors.name) ? validationErrors.name[0] : validationErrors.name}
                                </div>
                            )}
                        </div>

                        {/* Email */}
                        <div className="col-md-6 mb-5">
                            <label className="form-label fs-6 fw-bold required">Email</label>
                            <input
                                type="email"
                                name="email"
                                value={formData.email}
                                onChange={handleChange}
                                className={`form-control form-control-solid ${validationErrors.email ? 'is-invalid' : ''}`}
                                placeholder="Enter email"
                                required
                            />
                            {validationErrors.email && (
                                <div className="invalid-feedback d-block">
                                    {Array.isArray(validationErrors.email) ? validationErrors.email[0] : validationErrors.email}
                                </div>
                            )}
                        </div>

                        {/* Phone */}
                        <div className="col-md-6 mb-5">
                            <label className="form-label fs-6 fw-bold required">Phone</label>
                            <input
                                type="text"
                                name="phone"
                                value={formData.phone}
                                onChange={handleChange}
                                className={`form-control form-control-solid ${validationErrors.phone ? 'is-invalid' : ''}`}
                                placeholder="Enter phone"
                                required
                            />
                            {validationErrors.phone && (
                                <div className="invalid-feedback d-block">
                                    {Array.isArray(validationErrors.phone) ? validationErrors.phone[0] : validationErrors.phone}
                                </div>
                            )}
                        </div>

                        {/* Module */}
                        <div className="col-md-6 mb-5">
                            <label className="form-label fs-6 fw-bold required">Module</label>
                            <select
                                name="module"
                                value={formData.module}
                                onChange={handleChange}
                                className="form-select form-select-solid"
                                required
                            >
                                <option value="">All Modules</option>
                                <option value="pos">POS</option>
                                <option value="sales">Sales</option>
                            </select>
                            <div className="form-text">Roles list will update based on selected module</div>
                        </div>

                        {/* Status */}
                        <div className="col-md-6 mb-5">
                            <label className="form-label fs-6 fw-bold">Status</label>
                            <select
                                name="status"
                                value={formData.status}
                                onChange={handleChange}
                                className="form-select form-select-solid"
                            >
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        {/* Password Info */}
                        {mode === 'create' && (
                            <div className="col-md-12 mb-5">
                                <div className="alert alert-info d-flex align-items-center p-1">
                                    <i className="ki-duotone ki-information-5 fs-4hx text-info me-4">
                                        <span className="path1"></span>
                                        <span className="path2"></span>
                                        <span className="path3"></span>
                                    </i>
                                    <div>
                                        <h5 className="mb-1">Password Auto-Generation</h5>
                                        <p className="mb-0">A secure random password will be automatically generated for this user. You'll receive it after creation.</p>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Roles */}
                        <div className="col-md-12 mb-5">
                            <label className="form-label fs-6 fw-bold">Roles (Select Multiple)</label>
                            <div className="text-muted fs-7 mb-3">
                                {loadingRoles ? 'Loading roles...' : 'Select one or more roles for this user (filtered by selected module)'}
                            </div>
                            
                            {loadingRoles ? (
                                <LoadingSpinner message="Loading roles..." />
                            ) : (
                                <div className="border rounded p-4" style={{ maxHeight: '300px', overflowY: 'auto' }}>
                                    <div className="row">
                                        {Array.isArray(roles) && roles.map((role) => (
                                            <div key={role.id} className="col-md-6 mb-3">
                                                <div className="form-check form-check-custom form-check-solid">
                                                    <input
                                                        className="form-check-input"
                                                        type="checkbox"
                                                        value={role.id}
                                                        checked={selectedRoles.includes(role.id)}
                                                        onChange={() => handleRoleToggle(role.id)}
                                                        id={`role_${role.id}`}
                                                    />
                                                    <label className="form-check-label fw-semibold" htmlFor={`role_${role.id}`}>
                                                        {role.name}
                                                    </label>
                                                </div>
                                            </div>
                                        ))}

                                        {(!roles || roles.length === 0) && (
                                            <div className="col-12 text-center text-muted py-5">
                                                No roles available
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}
                            
                            {/* Selected Roles Display */}
                            {selectedRoles.length > 0 && Array.isArray(roles) && (
                                <div className="mt-3">
                                    <div className="fs-7 text-muted mb-2">Selected Roles ({selectedRoles.length}):</div>
                                    <div className="d-flex flex-wrap gap-2">
                                        {roles
                                            .filter(role => selectedRoles.includes(role.id))
                                            .map((role) => (
                                                <span key={role.id} className="badge badge-light-primary">
                                                    {role.name}
                                                    <i 
                                                        className="ki-duotone ki-cross fs-7 ms-1 cursor-pointer"
                                                        onClick={() => handleRoleToggle(role.id)}
                                                    >
                                                        <span className="path1"></span>
                                                        <span className="path2"></span>
                                                    </i>
                                                </span>
                                            ))}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Form Actions */}
                        <div className="col-12 mt-5">
                            <button 
                                type="submit" 
                                className="btn btn-primary"
                                disabled={loading || loadingRoles}
                            >
                                {loading ? (
                                    <>
                                        <span className="spinner-border spinner-border-sm me-2"></span>
                                        {mode === 'create' ? 'Saving...' : 'Updating...'}
                                    </>
                                ) : (
                                    'Save'
                                )}
                            </button>
                            <a href="/merchant/sales/users" className="btn btn-light-danger ms-2">
                                Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default UserForm;

