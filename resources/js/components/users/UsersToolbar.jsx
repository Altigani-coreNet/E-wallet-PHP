import React, { useEffect } from 'react';

const UsersToolbar = ({ onRefresh, loading, onToggleFilters }) => {
    // Initialize KTMenu for filter dropdown
    useEffect(() => {
        if (typeof KTMenu !== 'undefined' && typeof KTMenu.createInstances === 'function') {
            setTimeout(() => {
                KTMenu.createInstances();
            }, 100);
        }
    }, []);

    return (
        <div className="d-flex align-items-center gap-2 gap-lg-3">
            {/* Filter Toggle Button */}
            <button 
                onClick={onToggleFilters}
                className="btn btn-sm btn-flex btn-light btn-active-primary fw-bold"
            >
                <i className="ki-duotone ki-filter fs-6 text-muted me-1">
                    <span className="path1"></span>
                    <span className="path2"></span>
                </i>
                Filter
            </button>
            
            {/* Refresh Button */}
            <button 
                className="btn btn-sm btn-icon btn-light-primary" 
                onClick={onRefresh}
                disabled={loading}
                title="Refresh"
            >
                <i className={`ki-duotone ki-arrows-circle fs-2 ${loading ? 'spinner' : ''}`}>
                    <span className="path1"></span>
                    <span className="path2"></span>
                </i>
            </button>
            
            {/* Add User Button */}
            <a 
                href="/merchant/sales/users/create" 
                className="btn btn-sm fw-bold btn-primary"
            >
                <i className="ki-duotone ki-plus fs-3">
                    <span className="path1"></span>
                    <span className="path2"></span>
                </i>
                Add User
            </a>
        </div>
    );
};

export default UsersToolbar;

