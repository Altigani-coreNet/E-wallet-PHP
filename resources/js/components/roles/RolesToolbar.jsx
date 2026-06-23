import React from 'react';

const RolesToolbar = ({ onRefresh, loading, typeParam, onToggleFilters }) => {
    // Build the add role URL with type param if exists
    const addRoleUrl = `/merchant/sales/roles/create${typeParam ? `?type=${typeParam}` : ''}`;

    return (
        <>
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
                onClick={onRefresh}
                className="btn btn-sm btn-light-primary"
                disabled={loading}
                title="Refresh roles list"
            >
                <i className="ki-duotone ki-arrows-circle fs-2">
                    <span className="path1"></span>
                    <span className="path2"></span>
                </i>
                Refresh
            </button>

            {/* Add Role Button */}
            <a 
                href={addRoleUrl}
                className="btn btn-sm btn-primary"
                title="Add new role"
            >
                <i className="ki-duotone ki-plus fs-2"></i>
                Add Role
            </a>
        </>
    );
};

export default RolesToolbar;




