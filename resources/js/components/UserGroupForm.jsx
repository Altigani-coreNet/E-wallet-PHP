import { useEffect, useState } from 'react';
import CustomUserSelector from './CustomUserSelector';

const fetchJson = async (url) => {
    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!response.ok) throw new Error(`Request failed: ${response.status}`);
    return await response.json();
};

const UserGroupForm = () => {
    const [formData, setFormData] = useState({
        merchant_id: '',
        branch_id: '',
        name: '',
        description: '',
        user_ids: []
    });

    const [errors, setErrors] = useState({});
    const [loading, setLoading] = useState(false);

    const [merchantOptions, setMerchantOptions] = useState([]);
    const [branchOptions, setBranchOptions] = useState([]);
    const [userOptions, setUserOptions] = useState([]);

    // Load merchants on mount
    useEffect(() => {
        const loadMerchants = async () => {
            try {
                const data = await fetchJson('/admin/merchants/select');
                setMerchantOptions(Array.isArray(data) ? data : (data.data ?? []));
            } catch (e) {
                console.error('Failed to load merchants', e);
            }
        };
        loadMerchants();
    }, []);

    // Load branches and users when merchant changes
    useEffect(() => {
        const merchantId = formData.merchant_id;
        const loadRelated = async () => {
            if (!merchantId) {
                setBranchOptions([]);
                setUserOptions([]);
                setFormData(prev => ({ ...prev, branch_id: '', user_ids: [] }));
                return;
            }
            try {
                const [branches, users] = await Promise.all([
                    fetchJson(`/admin/branches/select?merchant_id=${merchantId}`),
                    fetchJson(`api/admin/users/select?merchant_id=${merchantId}&status=active`)
                ]);
                setBranchOptions(Array.isArray(branches) ? branches : (branches.data ?? []));
                const usersArray = Array.isArray(users) ? users : (users.data ?? []);
                setUserOptions(usersArray);
            } catch (e) {
                console.error('Failed to load related data', e);
            }
        };
        loadRelated();
    }, [formData.merchant_id]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
        if (errors[name]) setErrors(prev => ({ ...prev, [name]: '' }));
    };

    const handleUsersChange = (e) => {
        const options = Array.from(e.target.selectedOptions).map(o => o.value);
        setFormData(prev => ({ ...prev, user_ids: options }));
        if (errors.user_ids) setErrors(prev => ({ ...prev, user_ids: '' }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const validation = {};
        if (!formData.merchant_id) validation.merchant_id = ['Merchant is required.'];
        if (!formData.name || formData.name.trim() === '') validation.name = ['Group name is required.'];
        if (!formData.user_ids || formData.user_ids.length === 0) validation.user_ids = ['Select at least one user.'];
        if (Object.keys(validation).length) { setErrors(validation); return; }

        setLoading(true);
        setErrors({});
        try {
            const res = await fetch('/admin/user-groups', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(formData)
            });
            if (!res.ok) {
                const result = await res.json().catch(() => ({}));
                if (result?.errors) setErrors(result.errors);
                else setErrors({ general: result?.message || 'Failed to create user group.' });
                return;
            }
            window.location.href = '/admin/user-groups';
        } catch (err) {
            setErrors({ general: 'An unexpected error occurred.' });
        } finally {
            setLoading(false);
        }
    };

    const handleCancel = () => { window.location.href = '/admin/user-groups'; };

    const renderError = (field) => errors[field] && (
        <div className="invalid-feedback d-block">{Array.isArray(errors[field]) ? errors[field][0] : errors[field]}</div>
    );

    return (
        <div className="post d-flex flex-column-fluid" id="kt_post">
            <div id="kt_content_container" className="container-xxl">
                <form onSubmit={handleSubmit}>
                    <div className="row">
                        <div className="row col-md-12">
                            <div className="card">
                                <div className="card-header border-0">
                                    <div className="card-title">
                                        <h2>Add User Group</h2>
                                    </div>
                                </div>
                                <div className="card-body p-3">
                                    <div className="col-md-12">
                                        {errors.general && (
                                            <div className="alert alert-danger" role="alert">{errors.general}</div>
                                        )}
                                        <div className="row">
                                            <div className="col-12">
                                                <h4 className="mb-3">User Group Information</h4>
                                            </div>
                                            <div className="col-md-4 mb-3">
                                                <label htmlFor="merchant_id" className="form-label">Merchant <span className="text-danger">*</span></label>
                                                <select
                                                    id="merchant_id"
                                                    name="merchant_id"
                                                    className={`form-control ${errors.merchant_id ? 'is-invalid' : ''}`}
                                                    value={formData.merchant_id}
                                                    onChange={handleChange}
                                                    required
                                                >
                                                    <option value="">Select merchant</option>
                                                    {merchantOptions.map(opt => (
                                                        <option key={opt.id} value={opt.id}>{opt.text || opt.name}</option>
                                                    ))}
                                                </select>
                                                {renderError('merchant_id')}
                                            </div>
                                            <div className="col-md-4 mb-3">
                                                <label htmlFor="branch_id" className="form-label">Branch</label>
                                                <select
                                                    id="branch_id"
                                                    name="branch_id"
                                                    className={`form-control ${errors.branch_id ? 'is-invalid' : ''}`}
                                                    value={formData.branch_id}
                                                    onChange={handleChange}
                                                >
                                                    <option value="">Select Branch (Optional)</option>
                                                    {branchOptions.map(opt => (
                                                        <option key={opt.id} value={opt.id}>{opt.text || opt.name}</option>
                                                    ))}
                                                </select>
                                                {renderError('branch_id')}
                                            </div>
                                            <div className="col-md-4 mb-3">
                                                <label htmlFor="name" className="form-label">Group Name <span className="text-danger">*</span></label>
                                                <input
                                                    type="text"
                                                    id="name"
                                                    name="name"
                                                    className={`form-control ${errors.name ? 'is-invalid' : ''}`}
                                                    value={formData.name}
                                                    onChange={handleChange}
                                                    placeholder="Enter group name"
                                                    required
                                                />
                                                {renderError('name')}
                                            </div>
                                            <div className="col-12 mb-3">
                                                <label htmlFor="description" className="form-label">Description</label>
                                                <textarea
                                                    id="description"
                                                    name="description"
                                                    className={`form-control ${errors.description ? 'is-invalid' : ''}`}
                                                    rows="3"
                                                    placeholder="Enter description"
                                                    value={formData.description}
                                                    onChange={handleChange}
                                                />
                                                {renderError('description')}
                                            </div>
                                            <div className="col-12 mb-3">
                                                <label className="form-label">Select Users <span className="text-danger">*</span></label>
                                                <CustomUserSelector
                                                    merchantId={formData.merchant_id}
                                                    selectedUsers={formData.user_ids}
                                                    onUserChange={(next) => {
                                                        setFormData(prev => ({ ...prev, user_ids: next }));
                                                    }}
                                                    className="mt-2"
                                                />
                                                {renderError('user_ids')}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="card-footer">
                                    <div className="d-flex justify-content-end gap-2">
                                        <button type="submit" className="btn btn-primary" disabled={loading}>
                                            {loading ? 'Creating...' : (
                                                <>
                                                    <i className="ki-duotone ki-check fs-2"><span className="path1"></span><span className="path2"></span></i>
                                                    Create User Group
                                                </>
                                            )}
                                        </button>
                                        <button type="button" className="btn btn-secondary" onClick={handleCancel}>
                                            <i className="ki-duotone ki-cross fs-2"><span className="path1"></span><span className="path2"></span></i>
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

export default UserGroupForm;


