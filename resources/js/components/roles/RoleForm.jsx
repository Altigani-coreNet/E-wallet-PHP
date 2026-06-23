import React, { useState, useEffect } from 'react';
import { getPermissions } from '../../services/rolesService';
import LoadingSpinner from '../common/LoadingSpinner';
import ErrorAlert from '../common/ErrorAlert';

const RoleForm = ({ role = null, onSubmit, loading, error, mode = 'create' }) => {
    const [formData, setFormData] = useState({
        name: '',
        display_name: '',
        description: '',
        guard_name: 'web',
        module: 'all', // all, pos, sales
        permissions: []
    });
    
    const [permissions, setPermissions] = useState([]);
    const [loadingPermissions, setLoadingPermissions] = useState(true);
    const [selectedPermissions, setSelectedPermissions] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [validationErrors, setValidationErrors] = useState({});

    // Load form data if editing
    useEffect(() => {
        if (role && mode === 'edit') {
            setFormData({
                name: role.name || '',
                display_name: role.display_name || '',
                description: role.description || '',
                guard_name: role.guard_name || 'web',
                module: role.module || 'all',
                permissions: role.permissions || []
            });
            setSelectedPermissions(role.permissions?.map(p => p.id) || []);
        }
    }, [role, mode]);

    // Fetch permissions from AuthService
    useEffect(() => {
        fetchPermissions();
    }, []);

    const fetchPermissions = async () => {
        setLoadingPermissions(true);
        try {
            // Always fetch ALL merchant permissions (both pos and sales) for role creation/editing
            const module = 'merchant';
            
            console.log('📋 Fetching ALL merchant permissions (POS + Sales)');
            const response = await getPermissions(module);
            
            console.log('📦 Full API Response:', response);
            
            // Check for both success and status (API returns status: true)
            if (response.success || response.status || response.data?.status) {
                // Handle nested data structure: response.data.data.permissions
                const permissionsList = 
                    response.data?.data?.permissions ||  // { data: { data: { permissions: [] } } }
                    response.data?.permissions ||        // { data: { permissions: [] } }
                    response.data?.data ||               // { data: { data: [] } }
                    [];
                
                console.log('✅ Loaded permissions:', permissionsList.length, permissionsList);
                setPermissions(permissionsList);
            } else {
                console.error('❌ API returned error:', response);
            }
        } catch (err) {
            console.error('Error fetching permissions:', err);
        } finally {
            setLoadingPermissions(false);
        }
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
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

    const handlePermissionToggle = (permissionId) => {
        setSelectedPermissions(prev => {
            if (prev.includes(permissionId)) {
                return prev.filter(id => id !== permissionId);
            } else {
                return [...prev, permissionId];
            }
        });
    };

    // Check if all permissions in a category are selected
    const isCategorySelected = (categoryPermissions) => {
        return categoryPermissions.every(p => selectedPermissions.includes(p.id));
    };

    // Check if all permissions in a module are selected
    const isModuleSelected = (modulePermissions) => {
        return modulePermissions.every(p => selectedPermissions.includes(p.id));
    };

    // Toggle all permissions in a category
    const toggleCategory = (categoryPermissions) => {
        const categoryIds = categoryPermissions.map(p => p.id);
        const allSelected = categoryIds.every(id => selectedPermissions.includes(id));
        
        if (allSelected) {
            // Deselect all in category
            setSelectedPermissions(prev => prev.filter(id => !categoryIds.includes(id)));
        } else {
            // Select all in category
            setSelectedPermissions(prev => [...new Set([...prev, ...categoryIds])]);
        }
    };

    // Toggle all permissions in a module
    const toggleModule = (modulePermissions) => {
        const moduleIds = modulePermissions.map(p => p.id);
        const allSelected = moduleIds.every(id => selectedPermissions.includes(id));
        
        if (allSelected) {
            // Deselect all in module
            setSelectedPermissions(prev => prev.filter(id => !moduleIds.includes(id)));
        } else {
            // Select all in module
            setSelectedPermissions(prev => [...new Set([...prev, ...moduleIds])]);
        }
    };

    const handleSelectAll = () => {
        const allIds = permissions.map(p => p.id);
        setSelectedPermissions(allIds);
    };

    const handleDeselectAll = () => {
        setSelectedPermissions([]);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Clear previous validation errors
        setValidationErrors({});
        
        onSubmit({
            ...formData,
            module: formData.module === 'all' ? null : formData.module, // Send null for 'all'
            permissions: selectedPermissions
        });
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

    // Group permissions by module and category
    const groupPermissionsByModuleAndCategory = () => {
        const grouped = {
            pos: {},
            sales: {}
        };

        permissions.forEach(permission => {
            // Permission name format: pos.transactions.view_transactions or sales.customers.view_customers
            const parts = permission.name.split('.');
            
            if (parts.length >= 2) {
                const module = parts[0]; // pos or sales
                const category = parts[1]; // transactions, customers, etc.
                
                if (module === 'pos' || module === 'sales') {
                    if (!grouped[module][category]) {
                        grouped[module][category] = [];
                    }
                    grouped[module][category].push(permission);
                }
            }
        });

        return grouped;
    };

    const groupedPermissions = groupPermissionsByModuleAndCategory();

    // Filter grouped permissions based on selected module
    const getFilteredGroupedPermissions = () => {
        if (formData.module === 'all') {
            return groupedPermissions;
        } else if (formData.module === 'pos') {
            return { pos: groupedPermissions.pos, sales: {} };
        } else if (formData.module === 'sales') {
            return { pos: {}, sales: groupedPermissions.sales };
        }
        return groupedPermissions;
    };

    const filteredGroupedPermissions = getFilteredGroupedPermissions();

    // Get all permissions for a module
    const getModulePermissions = (module) => {
        const modulePerms = [];
        Object.values(groupedPermissions[module] || {}).forEach(categoryPerms => {
            modulePerms.push(...categoryPerms);
        });
        return modulePerms;
    };

    return (
        <div className="card">
            {/* Card Header */}
            <div className="card-header border-0 pt-6">
                <div className="card-title">
                    <h2>{mode === 'create' ? 'Add Role' : 'Edit Role'}</h2>
                </div>
                <div className="card-toolbar">
                    <div className="d-flex justify-content-end">
                        <a href="/merchant/sales/roles" className="btn btn-light-danger me-3">
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
                        {/* Role Name */}
                        <div className="col-md-8 mb-5">
                            <label className="form-label fs-6 fw-bold required">Role Name</label>
                            <input
                                type="text"
                                name="name"
                                value={formData.name}
                                onChange={handleChange}
                                className={`form-control form-control-solid ${validationErrors.name ? 'is-invalid' : ''}`}
                                placeholder="Enter role name"
                                required
                            />
                            {validationErrors.name && (
                                <div className="invalid-feedback d-block">
                                    {Array.isArray(validationErrors.name) ? validationErrors.name[0] : validationErrors.name}
                                </div>
                            )}
                        </div>

                        {/* Module Selector */}
                        <div className="col-md-4 mb-5">
                            <label className="form-label fs-6 fw-bold">Module</label>
                            <select
                                name="module"
                                value={formData.module}
                                onChange={handleChange}
                                className="form-select form-select-solid"
                            >
                                <option value="all">All Modules</option>
                                <option value="pos">POS </option>
                                <option value="sales">Sales</option>
                            </select>
                            <div className="form-text">Select which module permissions to display and assign</div>
                        </div>

                        {/* Permissions */}
                        <div className="col-md-12">
                            <div className="d-flex justify-content-between align-items-center mb-4">
                                <label className="fs-6 fw-bold mb-0">Permissions</label>
                                <div>
                                    <button 
                                        type="button"
                                        className="btn btn-sm btn-light-success me-2"
                                        onClick={handleSelectAll}
                                    >
                                        Select All
                                    </button>
                                    <button 
                                        type="button"
                                        className="btn btn-sm btn-light-danger"
                                        onClick={handleDeselectAll}
                                    >
                                        Deselect All
                                    </button>
                                </div>
                            </div>
                            
                            {loadingPermissions ? (
                                <LoadingSpinner message="Loading permissions..." />
                            ) : (
                                <div className="border rounded p-4">
                                    {/* POS Module */}
                                    {Object.keys(filteredGroupedPermissions.pos).length > 0 && (
                                        <div className="mb-6">
                                            {/* POS Module Header with Checkbox */}
                                            <div className="form-check form-check-custom form-check-solid mb-3 bg-light-primary p-3 rounded">
                                                <input
                                                    className="form-check-input"
                                                    type="checkbox"
                                                    id="module_pos"
                                                    checked={isModuleSelected(getModulePermissions('pos'))}
                                                    onChange={() => toggleModule(getModulePermissions('pos'))}
                                                />
                                                <label className="form-check-label fw-bold fs-5 text-primary" htmlFor="module_pos">
                                                    <i className="ki-duotone ki-shop fs-2 me-2">
                                                        <span className="path1"></span>
                                                        <span className="path2"></span>
                                                    </i>
                                                    POS Module
                                                </label>
                                            </div>

                                            {/* POS Categories */}
                                            <div className="ms-10">
                                                {Object.entries(filteredGroupedPermissions.pos).map(([category, categoryPermissions]) => (
                                                    <div key={`pos-${category}`} className="mb-4">
                                                        {/* Category Header with Checkbox */}
                                                        <div className="form-check form-check-custom form-check-solid mb-2 bg-light p-2 rounded">
                                                            <input
                                                                className="form-check-input"
                                                                type="checkbox"
                                                                id={`pos_category_${category}`}
                                                                checked={isCategorySelected(categoryPermissions)}
                                                                onChange={() => toggleCategory(categoryPermissions)}
                                                            />
                                                            <label className="form-check-label fw-bold text-gray-800" htmlFor={`pos_category_${category}`}>
                                                                {category.replace(/_/g, ' ').toUpperCase()}
                                                            </label>
                                                        </div>

                                                        {/* Individual Permissions */}
                                                        <div className="ms-8 row">
                                                            {categoryPermissions.map((permission) => (
                                                                <div key={permission.id} className="col-6 mb-2">
                                                                    <div className="form-check form-check-custom form-check-solid">
                                                                        <input
                                                                            type="checkbox"
                                                                            name="permission[]"
                                                                            className="form-check-input"
                                                                            value={permission.id}
                                                                            id={`perm_${permission.id}`}
                                                                            checked={selectedPermissions.includes(permission.id)}
                                                                            onChange={() => handlePermissionToggle(permission.id)}
                                                                        />
                                                                        <label className="form-check-label text-gray-700" htmlFor={`perm_${permission.id}`}>
                                                                            {permission.display_name || permission.name.split('.').pop().replace(/_/g, ' ')}
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {/* Sales Module */}
                                    {Object.keys(filteredGroupedPermissions.sales).length > 0 && (
                                        <div className="mb-6">
                                            {/* Sales Module Header with Checkbox */}
                                            <div className="form-check form-check-custom form-check-solid mb-3 bg-light-success p-3 rounded">
                                                <input
                                                    className="form-check-input"
                                                    type="checkbox"
                                                    id="module_sales"
                                                    checked={isModuleSelected(getModulePermissions('sales'))}
                                                    onChange={() => toggleModule(getModulePermissions('sales'))}
                                                />
                                                <label className="form-check-label fw-bold fs-5 text-success" htmlFor="module_sales">
                                                    <i className="ki-duotone ki-chart-line-up fs-2 me-2">
                                                        <span className="path1"></span>
                                                        <span className="path2"></span>
                                                    </i>
                                                    Sales Module
                                                </label>
                                            </div>

                                            {/* Sales Categories */}
                                            <div className="ms-10">
                                                {Object.entries(filteredGroupedPermissions.sales).map(([category, categoryPermissions]) => (
                                                    <div key={`sales-${category}`} className="mb-4">
                                                        {/* Category Header with Checkbox */}
                                                        <div className="form-check form-check-custom form-check-solid mb-2 bg-light p-2 rounded">
                                                            <input
                                                                className="form-check-input"
                                                                type="checkbox"
                                                                id={`sales_category_${category}`}
                                                                checked={isCategorySelected(categoryPermissions)}
                                                                onChange={() => toggleCategory(categoryPermissions)}
                                                            />
                                                            <label className="form-check-label fw-bold text-gray-800" htmlFor={`sales_category_${category}`}>
                                                                {category.replace(/_/g, ' ').toUpperCase()}
                                                            </label>
                                                        </div>

                                                        {/* Individual Permissions */}
                                                        <div className="ms-8 row">
                                                            {categoryPermissions.map((permission) => (
                                                                <div key={permission.id} className="col-6 mb-2">
                                                                    <div className="form-check form-check-custom form-check-solid">
                                                                        <input
                                                                            type="checkbox"
                                                                            name="permission[]"
                                                                            className="form-check-input"
                                                                            value={permission.id}
                                                                            id={`perm_${permission.id}`}
                                                                            checked={selectedPermissions.includes(permission.id)}
                                                                            onChange={() => handlePermissionToggle(permission.id)}
                                                                        />
                                                                        <label className="form-check-label text-gray-700" htmlFor={`perm_${permission.id}`}>
                                                                            {permission.display_name || permission.name.split('.').pop().replace(/_/g, ' ')}
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {/* No Permissions Found */}
                                    {Object.keys(filteredGroupedPermissions.pos).length === 0 && 
                                     Object.keys(filteredGroupedPermissions.sales).length === 0 && (
                                        <div className="text-center text-muted py-10">
                                            <i className="ki-duotone ki-file-deleted fs-3x mb-3">
                                                <span className="path1"></span>
                                                <span className="path2"></span>
                                            </i>
                                            <p>No permissions available</p>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* Selected Count */}
                        {selectedPermissions.length > 0 && (
                            <div className="col-12 mb-3">
                                <div className="alert alert-info d-flex align-items-center">
                                    <i className="ki-duotone ki-shield-tick fs-2x text-info me-3">
                                        <span className="path1"></span>
                                        <span className="path2"></span>
                                    </i>
                                    <span className="fw-bold">
                                        {selectedPermissions.length} permission{selectedPermissions.length !== 1 ? 's' : ''} selected
                                    </span>
                                </div>
                            </div>
                        )}

                        {/* Error Messages */}
                        {error && (
                            <div className="col-12 mt-3">
                                <div className="alert alert-danger">
                                    <ul className="mb-0">
                                        <li>{error}</li>
                                    </ul>
                                </div>
                            </div>
                        )}

                        {/* Form Actions */}
                        <div className="col-12 mt-5">
                            <button 
                                type="submit" 
                                className="btn btn-primary"
                                disabled={loading || loadingPermissions}
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
                            <a href="/merchant/sales/roles" className="btn btn-light-danger ms-2">
                                Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default RoleForm;
