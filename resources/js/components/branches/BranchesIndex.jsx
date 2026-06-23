import React, { useState, useEffect } from 'react';
import { getBranches, bulkDeleteBranches, exportBranches } from '../../services/branchesService';
import Toolbar from '../common/Toolbar';
import BranchesTable from './BranchesTable';
import BranchesFilters from './BranchesFilters';
import ImportBranchesModal from './ImportBranchesModal';
import LoadingSpinner from '../common/LoadingSpinner';
import Swal from 'sweetalert2';

const BranchesIndex = () => {
    const [branches, setBranches] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedIds, setSelectedIds] = useState([]);
    const [showFilters, setShowFilters] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [pagination, setPagination] = useState({
        current_page: 1,
        per_page: 15,
        total: 0,
        last_page: 1
    });
    const [filters, setFilters] = useState({
        search: '',
        status: '',
        date_from: '',
        date_to: '',
    });

    const breadcrumbs = [
        { label: 'Home', url: '/' },
        { label: 'Merchant', url: '/merchant/dashboard' },
        { label: 'Branches', active: true }
    ];

    useEffect(() => {
        fetchBranches();
    }, [pagination.current_page, pagination.per_page]);

    const fetchBranches = async () => {
        setLoading(true);
        try {
            const params = {
                page: pagination.current_page,
                per_page: pagination.per_page,
                ...filters
            };

            const response = await getBranches(params);
            console.log('Branches Response:', response);
            if (response.success) {
                // Handle both response.data and response.data.data structures
                const branchesData = Array.isArray(response.data) ? response.data : (response.data?.data || response.data || []);
                console.log('Branches Data:', branchesData);
                setBranches(branchesData);
                if (response.pagination) {
                    setPagination(prev => ({
                        ...prev,
                        ...response.pagination
                    }));
                }
            } else {
                console.error('Failed to fetch branches:', response.error);
            }
        } catch (error) {
            console.error('Error fetching branches:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleApplyFilters = () => {
        setPagination(prev => ({ ...prev, current_page: 1 }));
        fetchBranches();
    };

    const handleClearFilters = () => {
        setFilters({
            search: '',
            status: '',
            date_from: '',
            date_to: '',
        });
        setPagination(prev => ({ ...prev, current_page: 1 }));
        setTimeout(() => fetchBranches(), 100);
    };

    const handleBulkDelete = async () => {
        if (selectedIds.length === 0) return;

        const result = await Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete ${selectedIds.length} branch(es). This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel'
        });

        if (!result.isConfirmed) return;

        try {
            const response = await bulkDeleteBranches(selectedIds);
            if (response.success) {
                Swal.fire({
                    title: 'Deleted!',
                    text: 'Branches deleted successfully!',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                setSelectedIds([]);
                fetchBranches();
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: response.error || 'Failed to delete branches',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        } catch (error) {
            console.error('Error deleting branches:', error);
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while deleting branches',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    };

    const handleExport = async () => {
        try {
            const response = await exportBranches(filters);
            if (response.success) {
                Swal.fire({
                    title: 'Success!',
                    text: 'Export started! The file will download shortly.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: response.error || 'Failed to export data',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        } catch (error) {
            console.error('Error exporting branches:', error);
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while exporting',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    };

    const handlePageChange = (newPage) => {
        setPagination(prev => ({ ...prev, current_page: newPage }));
    };

    const handlePerPageChange = (newPerPage) => {
        setPagination(prev => ({ ...prev, per_page: newPerPage, current_page: 1 }));
    };

    const handleImportSuccess = () => {
        setShowImportModal(false);
        fetchBranches();
    };

    return (
        <>
            <Toolbar 
                pageTitle="My Branches"
                breadcrumbs={breadcrumbs}
            >
                <div className="d-flex align-items-center gap-2 gap-lg-3">
                    {/* Filter toggle button */}
                    <button 
                        onClick={() => setShowFilters(!showFilters)}
                        className="btn btn-sm btn-flex btn-secondary fw-bold"
                    >
                        <i className={`ki-duotone ki-filter fs-6 text-muted me-1 ${showFilters ? '' : 'rotate-90'}`}>
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        Toggle Filters
                    </button>

                    {/* Import button */}
                    <button 
                        onClick={() => setShowImportModal(true)}
                        className="btn btn-sm fw-bold btn-success"
                    >
                        <i className="ki-duotone ki-file-up fs-3">
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        Import Branches
                    </button>

                    {/* Create button */}
                    <a 
                        href="/merchant/branches/create" 
                        className="btn btn-sm fw-bold btn-primary"
                    >
                        <i className="ki-duotone ki-plus fs-3">
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        Request New Branch
                    </a>
                </div>
            </Toolbar>

            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-xxl">
                    {/* Filters */}
                    {showFilters && (
                        <BranchesFilters
                            filters={filters}
                            onFilterChange={setFilters}
                            onApply={handleApplyFilters}
                            onClear={handleClearFilters}
                            onExport={handleExport}
                        />
                    )}

                    {/* Table */}
                    <div className="card">
                        <div className="card-header border-0 pt-6">
                            <div className="card-title">
                                <h3>My Branches</h3>
                            </div>
                            <div className="card-toolbar">
                                {selectedIds.length > 0 && (
                                    <div className="d-flex justify-content-end align-items-center">
                                        <div className="fw-bolder me-5">
                                            <span className="me-2">{selectedIds.length}</span>
                                            Selected
                                        </div>
                                        <button 
                                            type="button" 
                                            className="btn btn-danger"
                                            onClick={handleBulkDelete}
                                        >
                                            Delete Selected
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="card-body pt-0">
                            {loading ? (
                                <LoadingSpinner />
                            ) : (
                                <BranchesTable
                                    branches={branches}
                                    selectedIds={selectedIds}
                                    onSelectChange={setSelectedIds}
                                    onRefresh={fetchBranches}
                                    pagination={pagination}
                                    onPageChange={handlePageChange}
                                    onPerPageChange={handlePerPageChange}
                                />
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Import Modal */}
            {showImportModal && (
                <ImportBranchesModal
                    onClose={() => setShowImportModal(false)}
                    onSuccess={handleImportSuccess}
                />
            )}
        </>
    );
};

export default BranchesIndex;


