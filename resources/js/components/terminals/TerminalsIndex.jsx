import React, { useState, useEffect } from 'react';
import { getTerminals, bulkDeleteTerminals, exportTerminals } from '../../services/terminalsService';
import { getBranchesByIds } from '../../services/branchesService';
import Toolbar from '../common/Toolbar';
import TerminalsTable from './TerminalsTable';
import TerminalsFilters from './TerminalsFilters';
import ImportTerminalsModal from './ImportTerminalsModal';
import Swal from 'sweetalert2';

const TerminalsIndex = () => {
    const [terminals, setTerminals] = useState([]);
    const [branches, setBranches] = useState({});  // Map of branch_id -> branch object
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
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
        { label: 'Terminals', active: true }
    ];

    useEffect(() => {
        fetchTerminals();
    }, [pagination.current_page, pagination.per_page]);

    const fetchTerminals = async () => {
        setLoading(true);
        setError(null);
        try {
            const params = {
                page: pagination.current_page,
                per_page: pagination.per_page,
                ...filters
            };

            const response = await getTerminals(params);
            console.log('Terminals Response:', response);
            if (response.success) {
                const terminalsData = Array.isArray(response.data) ? response.data : (response.data?.data || response.data || []);
                console.log('Terminals Data:', terminalsData);
                setTerminals(terminalsData);
                
                if (response.pagination) {
                    setPagination(prev => ({
                        ...prev,
                        ...response.pagination
                    }));
                }

                // Fetch branches from AuthService for terminals that have branch_id
                if (terminalsData.length > 0) {
                    const branchIds = terminalsData
                        .map(terminal => terminal.branch_id)
                        .filter(id => id != null);
                    
                    if (branchIds.length > 0) {
                        console.log('Fetching branches from AuthService for IDs:', branchIds);
                        const branchesResponse = await getBranchesByIds(branchIds);
                        
                        if (branchesResponse.success && branchesResponse.data) {
                            // Create a map of branch_id -> branch object for quick lookup
                            const branchesMap = {};
                            branchesResponse.data.forEach(branch => {
                                branchesMap[branch.id] = branch;
                            });
                            console.log('Branches from AuthService:', branchesMap);
                            setBranches(branchesMap);
                        }
                    } else {
                        setBranches({});
                    }
                }
            } else {
                setError(response.error || 'Failed to fetch terminals');
                Swal.fire({
                    title: 'Error!',
                    text: response.error || 'Failed to fetch terminals.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        } catch (error) {
            console.error('Error fetching terminals:', error);
            setError('An unexpected error occurred while fetching terminals');
            Swal.fire({
                title: 'Error!',
                text: 'An unexpected error occurred while fetching terminals.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        } finally {
            setLoading(false);
        }
    };

    const handleApplyFilters = () => {
        setPagination(prev => ({ ...prev, current_page: 1 }));
        fetchTerminals();
    };

    const handleClearFilters = () => {
        setFilters({
            search: '',
            status: '',
            date_from: '',
            date_to: '',
        });
        setPagination(prev => ({ ...prev, current_page: 1 }));
        setTimeout(() => fetchTerminals(), 100);
    };

    const handleBulkDelete = async () => {
        if (selectedIds.length === 0) return;

        const result = await Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete ${selectedIds.length} terminal(s). This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel'
        });

        if (result.isConfirmed) {
            try {
                const response = await bulkDeleteTerminals(selectedIds);
                if (response.success) {
                    await Swal.fire({
                        title: 'Deleted!',
                        text: 'Terminals have been deleted successfully.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setSelectedIds([]);
                    fetchTerminals();
                } else {
                    Swal.fire('Error!', response.error || 'Failed to delete terminals.', 'error');
                }
            } catch (error) {
                Swal.fire('Error!', 'An unexpected error occurred.', 'error');
            }
        }
    };

    const handleExport = async () => {
        try {
            const response = await exportTerminals(filters);
            if (response.success) {
                await Swal.fire({
                    title: 'Success!',
                    text: 'Terminals exported successfully.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire('Error!', response.error || 'Failed to export terminals.', 'error');
            }
        } catch (error) {
            Swal.fire('Error!', 'An unexpected error occurred.', 'error');
        }
    };

    const handlePageChange = (newPage) => {
        setPagination(prev => ({ ...prev, current_page: newPage }));
    };

    const handlePerPageChange = (newPerPage) => {
        setPagination(prev => ({ 
            ...prev, 
            per_page: newPerPage,
            current_page: 1 
        }));
    };

    const handleImportSuccess = () => {
        setShowImportModal(false);
        fetchTerminals();
    };

    return (
        <div className="terminals-index">
            {/* Toolbar */}
            <Toolbar
                pageTitle="Terminals"
                breadcrumbs={breadcrumbs}
            >
                <div className="d-flex align-items-center gap-2 gap-lg-3">
                    {/* Filters Button */}
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

                    {/* Bulk Delete Button */}
                    {selectedIds.length > 0 && (
                        <button 
                            onClick={handleBulkDelete}
                            className="btn btn-sm fw-bold btn-danger"
                        >
                            <i className="ki-duotone ki-trash fs-3">
                                <span className="path1"></span>
                                <span className="path2"></span>
                                <span className="path3"></span>
                                <span className="path4"></span>
                                <span className="path5"></span>
                            </i>
                            Delete Selected ({selectedIds.length})
                        </button>
                    )}

                    {/* Export Button */}
                    <button 
                        onClick={handleExport}
                        className="btn btn-sm fw-bold btn-success"
                    >
                        <i className="ki-duotone ki-file-down fs-3">
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        Export
                    </button>

                    {/* Import Button */}
                    <button 
                        onClick={() => setShowImportModal(true)}
                        className="btn btn-sm fw-bold btn-info"
                    >
                        <i className="ki-duotone ki-file-up fs-3">
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        Import
                    </button>

                    {/* Add Terminal Button */}
                    <a 
                        href="/merchant/terminals/create"
                        className="btn btn-sm fw-bold btn-primary"
                    >
                        <i className="ki-duotone ki-plus fs-3">
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        Add Terminal
                    </a>
                </div>
            </Toolbar>

            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-xxl">
                    {/* Filters Sidebar */}
                    {showFilters && (
                        <TerminalsFilters
                            filters={filters}
                            setFilters={setFilters}
                            onApply={handleApplyFilters}
                            onClear={handleClearFilters}
                            onClose={() => setShowFilters(false)}
                        />
                    )}

                    {/* Terminals Table */}
                    <div className="card">
                        <div className="card-body pt-0">
                            <TerminalsTable
                                terminals={terminals}
                                branches={branches}
                                selectedIds={selectedIds}
                                setSelectedIds={setSelectedIds}
                                pagination={pagination}
                                onPageChange={handlePageChange}
                                onPerPageChange={handlePerPageChange}
                                onRefresh={fetchTerminals}
                                loading={loading}
                                error={error}
                            />
                        </div>
                    </div>
                </div>
            </div>

            {/* Import Modal */}
            {showImportModal && (
                <ImportTerminalsModal
                    show={showImportModal}
                    onClose={() => setShowImportModal(false)}
                    onSuccess={handleImportSuccess}
                />
            )}
        </div>
    );
};

export default TerminalsIndex;

