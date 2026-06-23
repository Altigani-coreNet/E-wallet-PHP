import React from 'react';

const TerminalsFilters = ({ filters, setFilters, onApply, onClear, onClose }) => {
    const handleChange = (field, value) => {
        setFilters(prev => ({ ...prev, [field]: value }));
    };

    return (
        <div className="card card-flush mb-5">
            <div className="card-header">
                <h3 className="card-title">Filters</h3>
                <div className="card-toolbar">
                    <button 
                        type="button" 
                        className="btn btn-sm btn-icon btn-active-light-primary"
                        onClick={onClose}
                    >
                        <i className="ki-duotone ki-cross fs-1">
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                    </button>
                </div>
            </div>
            <div className="card-body">
                <div className="row g-5">
                    {/* Search */}
                    <div className="col-md-6">
                        <label className="form-label">Search</label>
                        <input
                            type="text"
                            className="form-control"
                            placeholder="Search terminals..."
                            value={filters.search}
                            onChange={(e) => handleChange('search', e.target.value)}
                        />
                    </div>

                    {/* Status */}
                    <div className="col-md-6">
                        <label className="form-label">Status</label>
                        <select
                            className="form-select"
                            value={filters.status}
                            onChange={(e) => handleChange('status', e.target.value)}
                        >
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    {/* Date From */}
                    <div className="col-md-6">
                        <label className="form-label">Date From</label>
                        <input
                            type="date"
                            className="form-control"
                            value={filters.date_from}
                            onChange={(e) => handleChange('date_from', e.target.value)}
                        />
                    </div>

                    {/* Date To */}
                    <div className="col-md-6">
                        <label className="form-label">Date To</label>
                        <input
                            type="date"
                            className="form-control"
                            value={filters.date_to}
                            onChange={(e) => handleChange('date_to', e.target.value)}
                        />
                    </div>
                </div>

                {/* Action Buttons */}
                <div className="d-flex justify-content-end mt-7">
                    <button 
                        type="button" 
                        className="btn btn-light btn-sm me-3"
                        onClick={onClear}
                    >
                        Clear
                    </button>
                    <button 
                        type="button" 
                        className="btn btn-primary btn-sm"
                        onClick={onApply}
                    >
                        Apply Filters
                    </button>
                </div>
            </div>
        </div>
    );
};

export default TerminalsFilters;

