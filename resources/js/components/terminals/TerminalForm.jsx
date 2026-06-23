import React, { useState, useEffect } from 'react';
import Select from 'react-select';
import { getBranchesForSelect } from '../../../services/branchesService';
import ErrorAlert from '../../common/ErrorAlert';

const TerminalForm = ({ mode = 'create', initialData = {}, onSubmit, loading, error }) => {
    const [formData, setFormData] = useState({
        name: '',
        terminal_id: '',
        branch_id: '',
        model: '',
        manufacturer: '',
        serial_no: '',
        sdk_id: '',
        sdk_version: '',
        android_os: '',
        add_type: 'static',
        is_active: 'active',
    });
    const [branches, setBranches] = useState([]);
    const [branchOptions, setBranchOptions] = useState([]);
    const [selectedBranch, setSelectedBranch] = useState(null);
    const [loadingBranches, setLoadingBranches] = useState(true);

    useEffect(() => {
        fetchBranches();
    }, []);

    useEffect(() => {
        if (mode === 'edit' && initialData) {
            setFormData({
                name: initialData.name || '',
                terminal_id: initialData.terminal_id || '',
                branch_id: initialData.branch_id || '',
                model: initialData.model || '',
                manufacturer: initialData.manufacturer || '',
                serial_no: initialData.serial_no || '',
                sdk_id: initialData.sdk_id || '',
                sdk_version: initialData.sdk_version || '',
                android_os: initialData.android_os || '',
                add_type: initialData.add_type || 'static',
                is_active: initialData.is_active ? 'active' : 'inactive',
            });

            // Set selected branch for react-select
            if (initialData.branch_id && branchOptions.length > 0) {
                const selected = branchOptions.find(option => option.value === initialData.branch_id);
                setSelectedBranch(selected || null);
            }
        }
    }, [mode, initialData, branchOptions]);

    const fetchBranches = async () => {
        try {
            console.log('Fetching branches from AuthService...');
            const response = await getBranchesForSelect();
            console.log('Branches response:', response);
            if (response.success) {
                const branchesData = response.data.data || [];
                console.log(`Loaded ${branchesData.length} branches from AuthService`);
                setBranches(branchesData);
                
                // Transform to react-select format
                const options = branchesData.map(branch => ({
                    value: branch.id,
                    label: branch.name,
                    data: branch
                }));
                setBranchOptions(options);
            } else {
                console.error('Failed to fetch branches:', response.error);
            }
        } catch (error) {
            console.error('Error fetching branches:', error);
        } finally {
            setLoadingBranches(false);
        }
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    const handleBranchChange = (selectedOption) => {
        setSelectedBranch(selectedOption);
        setFormData(prev => ({
            ...prev,
            branch_id: selectedOption ? selectedOption.value : ''
        }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        onSubmit(formData);
    };

    return (
        <div className="card">
            <div className="card-header">
                <h3 className="card-title">
                    {mode === 'create' ? 'Create New Terminal' : 'Edit Terminal'}
                </h3>
            </div>

            <form onSubmit={handleSubmit}>
                <div className="card-body">
                    {error && <ErrorAlert error={error} />}

                    <div className="row">
                        {/* Terminal Name */}
                        <div className="col-md-6 mb-6">
                            <label className="form-label required">Terminal Name</label>
                            <input
                                type="text"
                                name="name"
                                className="form-control"
                                placeholder="Enter terminal name"
                                value={formData.name}
                                onChange={handleChange}
                                required
                                disabled={loading}
                            />
                            <div className="form-text">Enter a unique name for this terminal</div>
                        </div>

                        {/* Terminal ID */}
                        <div className="col-md-6 mb-6">
                            <label className="form-label">Terminal ID</label>
                            <input
                                type="text"
                                name="terminal_id"
                                className="form-control"
                                placeholder="Auto-generated if left empty"
                                value={formData.terminal_id}
                                onChange={handleChange}
                                disabled={loading}
                            />
                            <div className="form-text">Leave empty to auto-generate</div>
                        </div>

                        {/* Branch - Searchable Select */}
                        <div className="col-md-6 mb-6">
                            <label className="form-label">Branch</label>
                            <Select
                                value={selectedBranch}
                                onChange={handleBranchChange}
                                options={branchOptions}
                                isSearchable={true}
                                isClearable={true}
                                isLoading={loadingBranches}
                                isDisabled={loading || loadingBranches}
                                placeholder={
                                    loadingBranches 
                                        ? "Loading branches..." 
                                        : branchOptions.length > 0 
                                            ? `Search branches (${branchOptions.length} available)` 
                                            : "No branches available"
                                }
                                noOptionsMessage={() => "No branches found"}
                                className="react-select-container"
                                classNamePrefix="react-select"
                                styles={{
                                    control: (base, state) => ({
                                        ...base,
                                        minHeight: '44px',
                                        borderColor: state.isFocused ? '#009ef7' : '#e4e6ef',
                                        boxShadow: state.isFocused ? '0 0 0 0.25rem rgba(0, 158, 247, 0.25)' : 'none',
                                        '&:hover': {
                                            borderColor: '#009ef7'
                                        }
                                    }),
                                    menu: (base) => ({
                                        ...base,
                                        zIndex: 9999
                                    })
                                }}
                            />
                            <div className="form-text">
                                {loadingBranches ? (
                                    <span className="text-muted">
                                        <span className="spinner-border spinner-border-sm me-1"></span>
                                        Loading branches from AuthService...
                                    </span>
                                ) : branchOptions.length > 0 ? (
                                    `Type to search and select branch (${branchOptions.length} branches from AuthService)`
                                ) : (
                                    <span className="text-warning">No branches found. Please create branches in AuthService first.</span>
                                )}
                            </div>
                        </div>

                        {/* Status */}
                        <div className="col-md-6 mb-6">
                            <label className="form-label required">Status</label>
                            <select
                                name="is_active"
                                className="form-select"
                                value={formData.is_active}
                                onChange={handleChange}
                                disabled={loading}
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div className="form-text">Set the terminal status</div>
                        </div>

                        {/* Model */}
                        <div className="col-md-6 mb-6">
                            <label className="form-label">Model</label>
                            <input
                                type="text"
                                name="model"
                                className="form-control"
                                placeholder="Enter model"
                                value={formData.model}
                                onChange={handleChange}
                                disabled={loading}
                            />
                        </div>

                        {/* Manufacturer */}
                        <div className="col-md-6 mb-6">
                            <label className="form-label">Manufacturer</label>
                            <input
                                type="text"
                                name="manufacturer"
                                className="form-control"
                                placeholder="Enter manufacturer"
                                value={formData.manufacturer}
                                onChange={handleChange}
                                disabled={loading}
                            />
                        </div>

                        {/* Serial Number */}
                        <div className="col-md-6 mb-6">
                            <label className="form-label">Serial Number</label>
                            <input
                                type="text"
                                name="serial_no"
                                className="form-control"
                                placeholder="Enter serial number"
                                value={formData.serial_no}
                                onChange={handleChange}
                                disabled={loading}
                            />
                        </div>

                        {/* SDK ID */}
                        <div className="col-md-6 mb-6">
                            <label className="form-label">SDK ID</label>
                            <input
                                type="text"
                                name="sdk_id"
                                className="form-control"
                                placeholder="Enter SDK ID"
                                value={formData.sdk_id}
                                onChange={handleChange}
                                disabled={loading}
                            />
                        </div>

                        {/* SDK Version */}
                        <div className="col-md-6 mb-6">
                            <label className="form-label">SDK Version</label>
                            <input
                                type="text"
                                name="sdk_version"
                                className="form-control"
                                placeholder="Enter SDK version"
                                value={formData.sdk_version}
                                onChange={handleChange}
                                disabled={loading}
                            />
                        </div>

                        {/* Android OS */}
                        <div className="col-md-6 mb-6">
                            <label className="form-label">Android OS</label>
                            <input
                                type="text"
                                name="android_os"
                                className="form-control"
                                placeholder="Enter Android OS version"
                                value={formData.android_os}
                                onChange={handleChange}
                                disabled={loading}
                            />
                        </div>

                        {/* Add Type */}
                        <div className="col-md-6 mb-6">
                            <label className="form-label">Add Type</label>
                            <select
                                name="add_type"
                                className="form-select"
                                value={formData.add_type}
                                onChange={handleChange}
                                disabled={loading}
                            >
                                <option value="static">Static</option>
                                <option value="auto">Auto</option>
                            </select>
                            <div className="form-text">How this terminal was added</div>
                        </div>
                    </div>
                </div>

                <div className="card-footer d-flex justify-content-end py-6 px-9">
                    <button 
                        type="button" 
                        className="btn btn-light me-3"
                        onClick={() => window.location.href = '/merchant/terminals'}
                        disabled={loading}
                    >
                        Cancel
                    </button>
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
                            mode === 'create' ? 'Create Terminal' : 'Update Terminal'
                        )}
                    </button>
                </div>
            </form>
        </div>
    );
};

export default TerminalForm;

