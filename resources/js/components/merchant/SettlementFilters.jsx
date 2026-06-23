import React from 'react';

const SettlementFilters = ({ filters, onFilterChange, onClearFilters }) => {
    const handleInputChange = (e) => {
        const { name, value } = e.target;
        onFilterChange({ [name]: value });
    };

    return (
        <div className="card bg-white card-xl-stretch mb-5">
            <div className="card-body">
                <div className="row">
                    {/* Search */}
                    <div className="col-md-3">
                        <label className="form-label">Search</label>
                        <input
                            type="text"
                            name="search"
                            className="form-control form-control-sm"
                            placeholder="Search by settlement ID..."
                            value={filters.search}
                            onChange={handleInputChange}
                        />
                    </div>

                    {/* Status */}
                    <div className="col-md-3">
                        <label className="form-label">Status</label>
                        <select
                            name="status"
                            className="form-select form-select-sm"
                            value={filters.status}
                            onChange={handleInputChange}
                        >
                            <option value="">All</option>
                            <option value="settled">Settled</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </div>

                {/* Filter Summary and Clear Button */}
                <div className="row mt-3">
                    <div className="col-8">
                        {(filters.search || filters.status) && (
                            <div className="text-muted fs-7">
                                <i className="ki-duotone ki-filter fs-6 text-muted me-1">
                                    <span className="path1"></span>
                                    <span className="path2"></span>
                                </i>
                                <span>
                                    {[
                                        filters.search && `Search: "${filters.search}"`,
                                        filters.status && `Status: ${filters.status}`
                                    ].filter(Boolean).join(', ')}
                                </span>
                            </div>
                        )}
                    </div>
                    <div className="col-4 text-end">
                        <button 
                            type="button" 
                            className="btn btn-secondary btn-sm"
                            onClick={onClearFilters}
                        >
                            <i className="ki-duotone ki-filter-remove fs-3">
                                <span className="path1"></span>
                                <span className="path2"></span>
                            </i>
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default SettlementFilters;

