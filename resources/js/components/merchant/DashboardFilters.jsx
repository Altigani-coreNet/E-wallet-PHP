import React from 'react';

const DashboardFilters = ({ 
    filters, 
    onFilterChange, 
    onApplyFilters, 
    onClearFilters, 
    isCollapsed 
}) => {
    const transactionStatuses = [
        { value: '', label: 'All Statuses' },
        { value: 'pending', label: 'Pending' },
        { value: 'approved', label: 'Approved' },
        { value: 'declined', label: 'Declined' },
        { value: 'failed', label: 'Failed' },
        { value: 'processed', label: 'Processed' },
        { value: 'refunded', label: 'Refunded' },
        { value: 'captured', label: 'Captured' },
        { value: 'voided', label: 'Voided' },
        { value: 'cancelled', label: 'Cancelled' },
        { value: 'expired', label: 'Expired' },
        { value: 'reversed', label: 'Reversed' },
    ];

    const handleInputChange = (field, value) => {
        onFilterChange({ [field]: value });
    };

    if (isCollapsed) {
        return null;
    }

    return (
        <div 
            className="card bg-white card-xl-stretch mb-5 mb-xl-8" 
            id="filters-body"
            style={{
                transition: 'all 0.3s ease'
            }}
        >
            {/* Card header */}
            <div className="card-header border-0 pt-6">
                <div className="card-title">
                    <h3 className="fw-bold m-0">Filters</h3>
                </div>
                <div className="card-toolbar">
                    <button 
                        type="button" 
                        className="btn btn-sm btn-light-primary" 
                        onClick={onClearFilters}
                    >
                        <i className="ki-duotone ki-refresh fs-2">
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        Clear Filters
                    </button>
                </div>
            </div>
            
            {/* Card body */}
            <div className="card-body">
                <div className="row g-4">
                    {/* DateTime From */}
                    <div className="col-md-4">
                        <label className="form-label fw-bold">From Date Time</label>
                        <input 
                            type="datetime-local" 
                            className="form-control" 
                            value={filters.datetime_from}
                            onChange={(e) => handleInputChange('datetime_from', e.target.value)}
                            placeholder="Select start date and time"
                        />
                    </div>
                    
                    {/* DateTime To */}
                    <div className="col-md-4">
                        <label className="form-label fw-bold">To Date Time</label>
                        <input 
                            type="datetime-local" 
                            className="form-control" 
                            value={filters.datetime_to}
                            onChange={(e) => handleInputChange('datetime_to', e.target.value)}
                            placeholder="Select end date and time"
                        />
                    </div>
                    
                    {/* Transaction Status */}
                    <div className="col-md-4">
                        <label className="form-label fw-bold">Transaction Status</label>
                        <select 
                            className="form-select" 
                            value={filters.transaction_status}
                            onChange={(e) => handleInputChange('transaction_status', e.target.value)}
                        >
                            {transactionStatuses.map(status => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>
                
                {/* Action Buttons */}
                <div className="row mt-4">
                    <div className="col-12">
                        <div className="d-flex gap-2">
                            <button 
                                type="button" 
                                className="btn btn-primary" 
                                onClick={onApplyFilters}
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

export default DashboardFilters;

