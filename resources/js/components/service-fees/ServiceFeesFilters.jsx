import React from 'react';

const ServiceFeesFilters = ({ filters, types = [], onFilterChange, onApply, onClear }) => {
    const handleChange = (e) => {
        const { name, value } = e.target;
        onFilterChange(prev => ({
            ...prev,
            [name]: value
        }));
    };

    return (
        <div className="card bg-white card-xl-stretch mb-5 mb-xl-8">
            <div className="card-header border-0 pt-6">
                <div className="card-title">
                    <h3 className="fw-bold m-0">Filters</h3>
                </div>
                <div className="card-toolbar">
                    <button 
                        type="button" 
                        className="btn btn-sm btn-light-primary"
                        onClick={onClear}
                    >
                        <i className="ki-duotone ki-refresh fs-2">
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        Clear Filters
                    </button>
                </div>
            </div>

            <div className="card-body">
                <div className="row g-4">
                    {/* Search */}
                    <div className="col-md-4">
                        <label className="form-label fw-bold">Search</label>
                        <input
                            type="text"
                            className="form-control"
                            name="search"
                            placeholder="Search by name, type, description..."
                            value={filters.search}
                            onChange={handleChange}
                        />
                    </div>

                    {/* Type Filter */}
                    <div className="col-md-4">
                        <label className="form-label fw-bold">Type</label>
                        <select
                            className="form-select"
                            name="type"
                            value={filters.type}
                            onChange={handleChange}
                        >
                            <option value="">All Types</option>
                            {types.map((type) => (
                                <option key={type} value={type}>
                                    {type.charAt(0).toUpperCase() + type.slice(1)}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Date From */}
                    <div className="col-md-4">
                        <label className="form-label fw-bold">Created Date From</label>
                        <input
                            type="date"
                            className="form-control"
                            name="date_from"
                            value={filters.date_from}
                            onChange={handleChange}
                        />
                    </div>

                    {/* Date To */}
                    <div className="col-md-4">
                        <label className="form-label fw-bold">Created Date To</label>
                        <input
                            type="date"
                            className="form-control"
                            name="date_to"
                            value={filters.date_to}
                            onChange={handleChange}
                        />
                    </div>
                </div>

                {/* Action Buttons */}
                <div className="row mt-4">
                    <div className="col-12">
                        <div className="d-flex gap-2">
                            <button
                                type="button"
                                className="btn btn-primary"
                                onClick={onApply}
                            >
                                <i className="ki-duotone ki-filter fs-2">
                                    <span className="path1"></span>
                                    <span className="path2"></span>
                                </i>
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ServiceFeesFilters;

