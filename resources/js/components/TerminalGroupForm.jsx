import { useState, useEffect } from 'react';
import axios from 'axios';
import CustomTerminalSelector from './CustomTerminalSelector';
import CustomUserGroupSelector from './CustomUserGroupSelector';

/**
 * Terminal Group Form Component
 * 
 * This component retrieves the admin API token directly from the database via the blade template.
 * The token is automatically included as a Bearer token in all API requests.
 * 
 * Token Flow:
 * 1. Admin logs in → Token created via Passport and stored in oauth_access_tokens table
 * 2. Blade template queries latest non-revoked token from database
 * 3. Token passed to React via data-admin-token attribute on #terminal-group-form-root
 * 4. Token included as "Authorization: Bearer {token}" header in all requests
 */
const TerminalGroupForm = () => {
    // Detect if we're in merchant context
    const isMerchantContext = window.location.pathname.includes('merchant');
    
    // Get admin token from the root element (retrieved from database in blade template)
    const getAdminToken = () => {
        const rootElement = document.getElementById('terminal-group-form-root');
        return rootElement ? rootElement.getAttribute('data-admin-token') : null;
    };
    
    // Get merchant ID from the page context
    const getMerchantId = () => {
        if (isMerchantContext) {
            // Try to get from meta tag first
            const merchantMeta = document.querySelector('meta[name="merchant-id"]');
            if (merchantMeta) {
                return merchantMeta.getAttribute('content');
            }
            
            // Try to get from window object
            if (window.merchantId) {
                return window.merchantId;
            }
            
            // Try to extract from URL if it's in the path
            const pathParts = window.location.pathname.split('/');
            const merchantIndex = pathParts.findIndex(part => part === 'merchant');
            if (merchantIndex !== -1 && pathParts[merchantIndex + 1] && !isNaN(pathParts[merchantIndex + 1])) {
                return pathParts[merchantIndex + 1];
            }
        }
        return null;
    };
    
    const merchantId = getMerchantId();
    
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        parent_id: '',
        terminal_ids: [],
        user_group_ids: [],
        merchant_id: ''
    });

    const [errors, setErrors] = useState({});
    const [loading, setLoading] = useState(false);
    const [parentGroups, setParentGroups] = useState([]);
    const [isSubgroup, setIsSubgroup] = useState(false);
    const [merchantOptions, setMerchantOptions] = useState([]);

    // CSRF token and Admin API token for Laravel
    useEffect(() => {
        // Set CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
        }
        
        // Set Admin API token from database
        const adminToken = getAdminToken();
        if (adminToken) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${adminToken}`;
        }
    }, []);

    // Load parent groups when needed
    useEffect(() => {
        if (isSubgroup) {
            loadParentGroups();
        }
    }, [isSubgroup]);

    // Load merchants for admin context
    useEffect(() => {
        const loadMerchants = async () => {
            if (isMerchantContext) return;
            try {
                const adminToken = getAdminToken();
                const headers = { 
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                };
                
                // Add admin token if available
                if (adminToken) {
                    headers['Authorization'] = `Bearer ${adminToken}`;
                }
                
                const res = await fetch('/admin/merchants/select', { headers });
                if (!res.ok) throw new Error('Failed to load merchants');
                const data = await res.json();
                const options = Array.isArray(data) ? data : (data.data ?? []);
                setMerchantOptions(options);
            } catch (e) {
                console.error('Failed to load merchants', e);
            }
        };
        loadMerchants();
    }, []);

    const loadParentGroups = async () => {
        try {
            // Use different endpoint based on context
            const endpoint = isMerchantContext 
                ? '/api/merchant/terminal-groups/parent-groups'
                : '/api/admin/terminal-groups/parent-groups';
                
            // Token is already set globally in axios.defaults.headers.common
            const response = await axios.get(endpoint);
            setParentGroups(response.data);
        } catch (error) {
            console.error('Error loading parent groups:', error);
        }
    };

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
        
        // Clear errors when user starts typing
        if (errors[name]) {
            setErrors(prev => ({
                ...prev,
                [name]: ''
            }));
        }
    };

    const handleSelectChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
        
        if (errors[name]) {
            setErrors(prev => ({
                ...prev,
                [name]: ''
            }));
        }
    };

    const handleSubgroupChange = (e) => {
        const checked = e.target.checked;
        setIsSubgroup(checked);
        
        if (!checked) {
            // Clear parent_id when unchecking subgroup
            setFormData(prev => ({
                ...prev,
                parent_id: ''
            }));
        }
        
        // Clear parent_id errors
        if (errors.parent_id) {
            setErrors(prev => ({
                ...prev,
                parent_id: ''
            }));
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        // Client-side validation
        const validationErrors = {};
        
        if (!formData.name || formData.name.trim() === '') {
            validationErrors.name = ['Group name is required.'];
        } else if (formData.name.length > 255) {
            validationErrors.name = ['Group name must not exceed 255 characters.'];
        }
        
        if (!formData.terminal_ids || formData.terminal_ids.length === 0) {
            validationErrors.terminal_ids = ['At least one terminal must be selected.'];
        }
        
        if (!formData.user_group_ids || formData.user_group_ids.length === 0) {
            validationErrors.user_group_ids = ['At least one user group must be selected.'];
        }

        // Require merchant_id in admin context
        if (!isMerchantContext && (!formData.merchant_id || formData.merchant_id === '')) {
            validationErrors.merchant_id = ['Merchant is required.'];
        }
        
        if (Object.keys(validationErrors).length > 0) {
            setErrors(validationErrors);
            return;
        }
        
        setLoading(true);
        setErrors({});

        try {
            // Use fetch for admin session, axios for merchant
            if (isMerchantContext) {
                // Merchant context - use axios
                const response = await axios.post('/merchant/terminal-groups', formData);
                window.location.href = '/merchant/terminal-groups';
            } else {
                // Admin context - use fetch
                const adminToken = getAdminToken();
                const headers = {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                };
                
                // Add admin token if available
                if (adminToken) {
                    headers['Authorization'] = `Bearer ${adminToken}`;
                }
                
                const response = await fetch('/admin/terminal-groups', {
                    method: 'POST',
                    headers: headers,
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (!response.ok) {
                    if (result.errors) {
                        setErrors(result.errors);
                    } else {
                        setErrors({ general: result.message || 'An error occurred while creating the terminal group.' });
                    }
                    return;
                }

                // Success - redirect to admin terminal groups index
                window.location.href = '/admin/terminal-groups';
            }
        } catch (error) {
            if (error.response && error.response.data.errors) {
                setErrors(error.response.data.errors);
            } else {
                setErrors({ general: 'An error occurred while creating the terminal group.' });
            }
        } finally {
            setLoading(false);
        }
    };

    const handleCancel = () => {
        const redirectUrl = isMerchantContext ? '/merchant/terminal-groups' : '/admin/terminal-groups';
        window.location.href = redirectUrl;
    };

    return (
        <div className="post d-flex flex-column-fluid" id="kt_post">
            <div id="kt_content_container" className="container-xxl">
                <form onSubmit={handleSubmit}>
                    <div className="row">
                        <div className="row col-md-12">
                            <div className="card">
                                <div className="card-header border-0">
                                    <div className="card-title">
                                        <h2>Add Terminal Group</h2>
                                    </div>
                                </div>
                                
                                <div className="card-body p-3">
                                    <div className="col-md-12">
                                        <div className="">
                                            {Object.keys(errors).length > 0 && (
                                                <div className="alert alert-danger alert-dismissible fade show" role="alert">
                                                    <div className="d-flex">
                                                        <i className="ki-duotone ki-cross-circle fs-2hx text-danger me-4">
                                                            <span className="path1"></span>
                                                            <span className="path2"></span>
                                                        </i>
                                                        <div className="d-flex flex-column">
                                                            <h4 className="mb-1">Validation Errors</h4>
                                                            <ul className="mb-0">
                                                                {Object.values(errors).flat().map((error, index) => (
                                                                    <li key={index}>{error}</li>
                                                                ))}
                                                            </ul>
                                                        </div>
                                                    </div>
                                                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                </div>
                                            )}
                                            
                                            <div className="row">
                                                <div className="col-12">
                                                    <h4 className="mb-3">Terminal Group Information</h4>
                                                </div>
                                                
                                                <div className="col-md-12 mb-3">
                                                    <label htmlFor="name" className="form-label">Group Name <span className="text-danger">*</span></label>
                                                    <input 
                                                        type="text" 
                                                        name="name" 
                                                        id="name" 
                                                        className={`form-control ${errors.name ? 'is-invalid' : ''}`}
                                                        value={formData.name}
                                                        onChange={handleInputChange}
                                                        placeholder="Enter group name"
                                                        required
                                                    />
                                                    {errors.name && (
                                                        <div className="invalid-feedback">{errors.name[0]}</div>
                                                    )}
                                                </div>
                                                
                                                <div className="col-12 mb-3">
                                                    <div className="form-check">
                                                        <input 
                                                            className="form-check-input" 
                                                            type="checkbox" 
                                                            id="is_subgroup" 
                                                            checked={isSubgroup}
                                                            onChange={handleSubgroupChange}
                                                        />
                                                        <label className="form-check-label" htmlFor="is_subgroup">
                                                            This is a subgroup
                                                        </label>
                                                    </div>
                                                </div>
                                                
                                                {isSubgroup && (
                                                    <div className="col-md-12 mb-3">
                                                        <label htmlFor="parent_id" className="form-label">Parent Group <span className="text-danger">*</span></label>
                                                        <select 
                                                            name="parent_id" 
                                                            id="parent_id" 
                                                            className={`form-control ${errors.parent_id ? 'is-invalid' : ''}`}
                                                            value={formData.parent_id}
                                                            onChange={handleSelectChange}
                                                            required={isSubgroup}
                                                        >
                                                            <option value="">Select parent group</option>
                                                            {parentGroups.map(group => (
                                                                <option key={group.id} value={group.id}>
                                                                    {group.text}
                                                                </option>
                                                            ))}
                                                        </select>
                                                        {errors.parent_id && (
                                                            <div className="invalid-feedback">{errors.parent_id[0]}</div>
                                                        )}
                                                    </div>
                                                )}
                                                
                                                <div className="col-12 mb-3">
                                                    <label htmlFor="description" className="form-label">Description</label>
                                                    <textarea 
                                                        name="description" 
                                                        id="description" 
                                                        className={`form-control ${errors.description ? 'is-invalid' : ''}`}
                                                        rows="3" 
                                                        placeholder="Enter description"
                                                        value={formData.description}
                                                        onChange={handleInputChange}
                                                    ></textarea>
                                                    {errors.description && (
                                                        <div className="invalid-feedback">{errors.description[0]}</div>
                                                    )}
                                                </div>
                                                
                                                <div className="col-12 mb-3">
                                                    {!isMerchantContext && (
                                                        <div className="row">
                                                            <div className="col-md-12 mb-3">
                                                                <label htmlFor="merchant_id" className="form-label">Merchant <span className="text-danger">*</span></label>
                                                                <select
                                                                    id="merchant_id"
                                                                    name="merchant_id"
                                                                    className={`form-control ${errors.merchant_id ? 'is-invalid' : ''}`}
                                                                    value={formData.merchant_id}
                                                                    onChange={handleSelectChange}
                                                                    required
                                                                >
                                                                    <option value="">Select merchant</option>
                                                                    {merchantOptions.map(opt => (
                                                                        <option key={opt.id} value={opt.id}>{opt.text || opt.name}</option>
                                                                    ))}
                                                                </select>
                                                                {errors.merchant_id && (
                                                                    <div className="invalid-feedback d-block">{errors.merchant_id[0]}</div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>

                                                <div className="col-12 mb-3">
                                                    <label htmlFor="user_group_ids" className="form-label">
                                                        Select User Groups <span className="text-danger">*</span>
                                                    </label>
                                                    <CustomUserGroupSelector
                                                        selectedUserGroups={formData.user_group_ids}
                                                        onUserGroupChange={(userGroupIds) => {
                                                            setFormData(prev => ({
                                                                ...prev,
                                                                user_group_ids: userGroupIds
                                                            }));
                                                            // Clear errors when user groups are selected
                                                            if (errors.user_group_ids) {
                                                                setErrors(prev => ({
                                                                    ...prev,
                                                                    user_group_ids: ''
                                                                }));
                                                            }
                                                        }}
                                                        merchantId={!isMerchantContext ? formData.merchant_id : merchantId}
                                                        adminToken={getAdminToken()}
                                                        className="mt-2"
                                                    />
                                                    {errors.user_group_ids && (
                                                        <div className="invalid-feedback d-block">{errors.user_group_ids[0]}</div>
                                                    )}
                                                </div>
                                                
                                                <div className="col-12 mb-3">
                                                    <label htmlFor="terminal_ids" className="form-label">
                                                        Select Terminals <span className="text-danger">*</span>
                                                    </label>
                                                    <CustomTerminalSelector
                                                        selectedTerminals={formData.terminal_ids}
                                                        onTerminalChange={(terminalIds) => {
                                                            setFormData(prev => ({
                                                                ...prev,
                                                                terminal_ids: terminalIds
                                                            }));
                                                            // Clear errors when terminals are selected
                                                            if (errors.terminal_ids) {
                                                                setErrors(prev => ({
                                                                    ...prev,
                                                                    terminal_ids: ''
                                                                }));
                                                            }
                                                        }}
                                                        isMerchantContext={isMerchantContext}
                                                        className="mt-2"
                                                    />
                                                    {errors.terminal_ids && (
                                                        <div className="invalid-feedback d-block">{errors.terminal_ids[0]}</div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="card-footer">
                                    <div className="d-flex justify-content-end gap-2">
                                        <button 
                                            type="submit" 
                                            className="btn btn-primary"
                                            disabled={loading}
                                        >
                                            {loading ? (
                                                <>
                                                    <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                                    Creating...
                                                </>
                                            ) : (
                                                <>
                                                    <i className="ki-duotone ki-check fs-2">
                                                        <span className="path1"></span>
                                                        <span className="path2"></span>
                                                    </i>
                                                    Create Terminal Group
                                                </>
                                            )}
                                        </button>
                                        <button 
                                            type="button" 
                                            className="btn btn-secondary"
                                            onClick={handleCancel}
                                        >
                                            <i className="ki-duotone ki-cross fs-2">
                                                <span className="path1"></span>
                                                <span className="path2"></span>
                                            </i>
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default TerminalGroupForm; 