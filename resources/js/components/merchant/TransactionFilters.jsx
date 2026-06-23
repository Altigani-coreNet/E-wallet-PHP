import React, { useState, useEffect } from 'react';
import axios from 'axios';

const TransactionFilters = ({ filters, onFilterChange, onClearFilters }) => {
    const [terminals, setTerminals] = useState([]);
    const [activeFiltersCount, setActiveFiltersCount] = useState(0);

    // Get API token from multiple sources
    const getApiToken = () => {
        return window.merchantTransactionsConfig?.apiToken ||
               window.merchantAppConfig?.apiToken ||
               document.getElementById('merchant-app-root')?.getAttribute('data-api-token') ||
               localStorage.getItem('jwt_token');
    };

    useEffect(() => {
        fetchTerminals();
    }, []);

    useEffect(() => {
        // Count active filters
        let count = 0;
        if (filters.search) count++;
        if (filters.status) count++;
        if (filters.payment_type) count++;
        if (filters.terminal_id) count++;
        if (filters.start_date) count++;
        if (filters.end_date) count++;
        setActiveFiltersCount(count);
    }, [filters]);

    const fetchTerminals = async () => {
        try {
            const token = getApiToken();
            const response = await axios.get('/api/softpos/merchant/terminals', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            setTerminals(response.data);
        } catch (error) {
            console.error('Error fetching terminals:', error);
        }
    };

    const handleInputChange = (field, value) => {
        onFilterChange({ [field]: value });
    };

    const getFilterSummary = () => {
        const details = [];
        if (filters.search) details.push(`Search: "${filters.search}"`);
        if (filters.status) details.push(`Status: ${filters.status}`);
        if (filters.payment_type) details.push(`Payment Type: ${filters.payment_type}`);
        if (filters.terminal_id) {
            const terminal = terminals.find(t => t.id === parseInt(filters.terminal_id));
            details.push(`Terminal: ${terminal?.name || filters.terminal_id}`);
        }
        if (filters.start_date) details.push(`From: ${filters.start_date}`);
        if (filters.end_date) details.push(`To: ${filters.end_date}`);
        return details.join(', ');
    };

    return (
        <div className="card bg-white card-xl-stretch mb-5 mb-xl-8">
            <div className="card-body">
                <div className="row">
                    {/* Search */}
                    <div className="col-md-3 mb-3">
                        <label htmlFor="search" className="form-label">Search</label>
                        <input
                            type="text"
                            className="form-control"
                            id="search"
                            placeholder="Transaction ID, RRN, Auth Code"
                            value={filters.search}
                            onChange={(e) => handleInputChange('search', e.target.value)}
                        />
                    </div>

                    {/* Status */}
                    <div className="col-md-3 mb-3">
                        <label htmlFor="status" className="form-label">Status</label>
                        <select
                            className="form-select"
                            id="status"
                            value={filters.status}
                            onChange={(e) => handleInputChange('status', e.target.value)}
                        >
                            <option value="">All Statuses</option>
                            <option value="APPROVED">APPROVED</option>
                            <option value="DECLINED">DECLINED</option>
                            <option value="PENDING">PENDING</option>
                            <option value="CAPTURED">CAPTURED</option>
                            <option value="VOIDED">VOIDED</option>
                            <option value="REFUNDED">REFUNDED</option>
                        </select>
                    </div>

                    {/* Payment Type */}
                    <div className="col-md-3 mb-3">
                        <label htmlFor="payment_type" className="form-label">Payment Type</label>
                        <select
                            className="form-select"
                            id="payment_type"
                            value={filters.payment_type}
                            onChange={(e) => handleInputChange('payment_type', e.target.value)}
                        >
                            <option value="">All Types</option>
                            <option value="card">Card</option>
                            <option value="web">Web</option>
                            <option value="bank">Bank</option>
                            <option value="mobile">Mobile</option>
                            <option value="qr">QR</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    {/* Terminal */}
                    <div className="col-md-3 mb-3">
                        <label htmlFor="terminal_id" className="form-label">Terminal</label>
                        <select
                            className="form-select"
                            id="terminal_id"
                            value={filters.terminal_id}
                            onChange={(e) => handleInputChange('terminal_id', e.target.value)}
                        >
                            <option value="">All Terminals</option>
                            {terminals.map(terminal => (
                                <option key={terminal.id} value={terminal.id}>
                                    {terminal.name}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                {/* Date Range */}
                <div className="row mt-3">
                    <div className="col-md-6 mb-3">
                        <label htmlFor="start_date" className="form-label">From Date</label>
                        <input
                            type="date"
                            className="form-control"
                            id="start_date"
                            value={filters.start_date}
                            onChange={(e) => handleInputChange('start_date', e.target.value)}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label htmlFor="end_date" className="form-label">To Date</label>
                        <input
                            type="date"
                            className="form-control"
                            id="end_date"
                            value={filters.end_date}
                            onChange={(e) => handleInputChange('end_date', e.target.value)}
                        />
                    </div>
                </div>

                {/* Filter Summary */}
                <div className="row mt-3">
                    <div className="col-8">
                        {activeFiltersCount > 0 && (
                            <div className="text-muted fs-7">
                                <i className="ki-duotone ki-filter fs-6 text-muted me-1">
                                    <span className="path1"></span>
                                    <span className="path2"></span>
                                </i>
                                <span className="fw-bold">{activeFiltersCount}</span> active filter{activeFiltersCount !== 1 ? 's' : ''}
                                <span className="ms-2 badge badge-light-primary fs-8" title={getFilterSummary()}>
                                    {getFilterSummary().length > 50 
                                        ? getFilterSummary().substring(0, 50) + '...' 
                                        : getFilterSummary()}
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

export default TransactionFilters;

