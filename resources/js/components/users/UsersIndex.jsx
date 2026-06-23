import React, { useState, useEffect } from 'react';
import { getUsers, deleteUser, changeUserStatus } from '../../services/usersService';
import UsersTable from './UsersTable';
import UsersSearch from './UsersSearch';
import UsersToolbar from './UsersToolbar';
import Toolbar from '../common/Toolbar';
import LoadingSpinner from '../common/LoadingSpinner';
import ErrorAlert from '../common/ErrorAlert';

const UsersIndex = () => {
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [showFilters, setShowFilters] = useState(false);
    const [filters, setFilters] = useState({
        module: '',
        date_from: '',
        date_to: '',
    });
    const [pagination, setPagination] = useState({
        currentPage: 1,
        perPage: 10,
        total: 0,
        lastPage: 1
    });
    const [sortConfig, setSortConfig] = useState({
        column: 'id',
        direction: 'desc'
    });

    // Fetch users from AuthService API
    const fetchUsers = async (page = 1, search = '', status = '', sortBy = 'id', sortDirection = 'desc') => {
        setLoading(true);
        setError(null);

        try {
            const params = {
                page,
                per_page: pagination.perPage,
                search,
                sort_by: sortBy,
                sort_direction: sortDirection
            };

            // Add status filter if selected
            if (status) {
                params.status = status;
            }

            // Add additional filters
            if (filters.module) {
                params.module = filters.module;
            }
            if (filters.date_from) {
                params.date_from = filters.date_from;
            }
            if (filters.date_to) {
                params.date_to = filters.date_to;
            }

            const response = await getUsers(params);

            if (response.success) {
                // Extract users from nested data structure
                // API returns: { success: true, data: { status: true, data: { data: [...users...], current_page: 1, ... } } }
                const apiData = response.data.data || response.data;
                const usersArray = apiData.data || apiData.users || apiData || [];
                
                // Ensure it's an array
                setUsers(Array.isArray(usersArray) ? usersArray : []);
                
                // Update pagination from the correct level
                if (apiData.current_page !== undefined) {
                    setPagination({
                        currentPage: apiData.current_page,
                        perPage: apiData.per_page,
                        total: apiData.total,
                        lastPage: apiData.last_page
                    });
                }
            } else {
                setError(response.error || 'Failed to fetch users');
                setUsers([]); // Set empty array on error
            }
        } catch (err) {
            console.error('Error fetching users:', err);
            setError('An unexpected error occurred while fetching users');
        } finally {
            setLoading(false);
        }
    };

    // Initial load
    useEffect(() => {
        fetchUsers(pagination.currentPage, searchTerm, statusFilter, sortConfig.column, sortConfig.direction);
    }, []);

    // Real-time filtering - fetch when filters change
    useEffect(() => {
        const timer = setTimeout(() => {
            if (!loading) {
                fetchUsers(pagination.currentPage, searchTerm, statusFilter, sortConfig.column, sortConfig.direction);
            }
        }, 500); // Debounce for 500ms

        return () => clearTimeout(timer);
    }, [filters.module, filters.date_from, filters.date_to]);

    // Reinitialize KTMenu after users are loaded
    useEffect(() => {
        if (!loading && users.length > 0) {
            // Reinitialize Metronic menu components
            if (typeof KTMenu !== 'undefined' && typeof KTMenu.createInstances === 'function') {
                setTimeout(() => {
                    KTMenu.createInstances();
                }, 100);
            }
        }
    }, [loading, users]);

    // Handle search
    const handleSearch = (term) => {
        setSearchTerm(term);
        fetchUsers(1, term, statusFilter, sortConfig.column, sortConfig.direction);
    };

    // Handle status filter
    const handleStatusFilter = (status) => {
        setStatusFilter(status);
        fetchUsers(1, searchTerm, status, sortConfig.column, sortConfig.direction);
    };

    // Handle sort
    const handleSort = (column) => {
        const newDirection = 
            sortConfig.column === column && sortConfig.direction === 'asc' 
                ? 'desc' 
                : 'asc';
        
        setSortConfig({ column, direction: newDirection });
        fetchUsers(pagination.currentPage, searchTerm, statusFilter, column, newDirection);
    };

    // Handle page change
    const handlePageChange = (page) => {
        fetchUsers(page, searchTerm, statusFilter, sortConfig.column, sortConfig.direction);
    };

    // Handle delete user
    const handleDeleteUser = async (userId) => {
        if (!confirm('Are you sure you want to delete this user?')) {
            return;
        }

        try {
            const response = await deleteUser(userId);
            
            if (response.success) {
                // Refresh users list
                fetchUsers(pagination.currentPage, searchTerm, statusFilter, sortConfig.column, sortConfig.direction);
                // You can add a success toast notification here
            } else {
                setError(response.error || 'Failed to delete user');
            }
        } catch (err) {
            console.error('Error deleting user:', err);
            setError('An unexpected error occurred while deleting the user');
        }
    };

    // Handle status change
    const handleStatusChange = async (userId, currentStatus) => {
        const newStatus = currentStatus === 1 ? 0 : 1;
        
        try {
            const response = await changeUserStatus(userId, newStatus);
            
            if (response.success) {
                // Refresh users list
                fetchUsers(pagination.currentPage, searchTerm, statusFilter, sortConfig.column, sortConfig.direction);
            } else {
                setError(response.error || 'Failed to change user status');
            }
        } catch (err) {
            console.error('Error changing user status:', err);
            setError('An unexpected error occurred while changing user status');
        }
    };

    // Handle refresh
    const handleRefresh = () => {
        fetchUsers(pagination.currentPage, searchTerm, statusFilter, sortConfig.column, sortConfig.direction);
    };

    // Reset filters
    const resetFilters = () => {
        setFilters({
            module: '',
            date_from: '',
            date_to: '',
        });
        setSearchTerm('');
        setStatusFilter('');
    };

    // Build breadcrumbs
    const breadcrumbs = [
        { label: 'Home', url: '/' },
        { label: 'Sales', url: '/merchant/sales/dashboard' },
        { label: 'Users', active: true }
    ];

    return (
        <>
            {/* Toolbar with breadcrumbs and actions */}
            <Toolbar 
                pageTitle="Users Management"
                breadcrumbs={breadcrumbs}
                actions={
                    <UsersToolbar 
                        onRefresh={handleRefresh}
                        loading={loading}
                        onToggleFilters={() => setShowFilters(!showFilters)}
                    />
                }
            />

            {/* Main Content */}
            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-fluid">
                    
                    {/* Filter Panel */}
                    {showFilters && (
                        <div className="card mb-5">
                            <div className="card-body">
                                <div className="row g-3">
                                    <div className="col-md-3">
                                        <label className="form-label">Module</label>
                                        <select 
                                            className="form-select form-select-sm"
                                            value={filters.module}
                                            onChange={(e) => setFilters({...filters, module: e.target.value})}
                                        >
                                            <option value="">All Modules</option>
                                            <option value="pos">POS</option>
                                            <option value="sales">Sales</option>
                                        </select>
                                    </div>
                                    <div className="col-md-3">
                                        <label className="form-label">Status</label>
                                        <select 
                                            className="form-select form-select-sm"
                                            value={statusFilter}
                                            onChange={(e) => handleStatusFilter(e.target.value)}
                                        >
                                            <option value="">All Status</option>
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                    </div>
                                    <div className="col-md-3">
                                        <label className="form-label">Date From</label>
                                        <input 
                                            type="date" 
                                            className="form-control form-control-sm"
                                            value={filters.date_from}
                                            onChange={(e) => setFilters({...filters, date_from: e.target.value})}
                                        />
                                    </div>
                                    <div className="col-md-3">
                                        <label className="form-label">Date To</label>
                                        <input 
                                            type="date" 
                                            className="form-control form-control-sm"
                                            value={filters.date_to}
                                            onChange={(e) => setFilters({...filters, date_to: e.target.value})}
                                        />
                                    </div>
                                </div>
                                <div className="d-flex justify-content-between align-items-center mt-4">
                                    <div className="text-muted fs-7">
                                        <i className="ki-duotone ki-information fs-5 text-primary me-1">
                                            <span className="path1"></span>
                                            <span className="path2"></span>
                                            <span className="path3"></span>
                                        </i>
                                        Filters apply automatically as you type
                                    </div>
                                    <button onClick={resetFilters} className="btn btn-sm btn-light-primary">
                                        <i className="ki-duotone ki-arrows-circle fs-3">
                                            <span className="path1"></span>
                                            <span className="path2"></span>
                                        </i>
                                        Reset Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="card">
                        {/* Card Header */}
                        <div className="card-header border-0 pt-6">
                            <div className="card-title">
                                <UsersSearch 
                                    onSearch={handleSearch}
                                    searchTerm={searchTerm}
                                />
                            </div>
                        </div>

                        {/* Card Body */}
                        <div className="card-body pt-0">
                            {/* Error Alert */}
                            {error && <ErrorAlert message={error} onClose={() => setError(null)} />}

                            {/* Loading Spinner */}
                            {loading && <LoadingSpinner />}

                            {/* Users Table */}
                            {!loading && (
                                <UsersTable
                                    users={users}
                                    sortConfig={sortConfig}
                                    onSort={handleSort}
                                    onDelete={handleDeleteUser}
                                    onStatusChange={handleStatusChange}
                                    pagination={pagination}
                                    onPageChange={handlePageChange}
                                />
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

export default UsersIndex;

