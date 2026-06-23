import React, { useState, useEffect } from 'react';
import { getServiceFees, getServiceFeeTypes } from '../../services/serviceFeesService';
import Toolbar from '../common/Toolbar';
import ServiceFeesTable from './ServiceFeesTable';
import ServiceFeesFilters from './ServiceFeesFilters';
import LoadingSpinner from '../common/LoadingSpinner';

const ServiceFeesIndex = () => {
    const [serviceFees, setServiceFees] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showFilters, setShowFilters] = useState(false);
    const [types, setTypes] = useState([]);
    const [pagination, setPagination] = useState({
        current_page: 1,
        per_page: 15,
        total: 0,
        last_page: 1
    });
    const [filters, setFilters] = useState({
        search: '',
        type: '',
        date_from: '',
        date_to: '',
    });

    const breadcrumbs = [
        { label: 'Home', url: '/' },
        { label: 'Merchant', url: '/merchant/dashboard' },
        { label: 'Service Fees', active: true }
    ];

    useEffect(() => {
        fetchServiceFees();
        fetchTypes();
    }, [pagination.current_page, pagination.per_page]);

    const fetchServiceFees = async () => {
        setLoading(true);
        try {
            const params = {
                page: pagination.current_page,
                per_page: pagination.per_page,
                ...filters
            };

            const response = await getServiceFees(params);
            console.log('Service Fees Response:', response);
            
            if (response.success) {
                const feesData = Array.isArray(response.data) ? response.data : (response.data?.data || response.data || []);
                setServiceFees(feesData);
                if (response.pagination) {
                    setPagination(prev => ({
                        ...prev,
                        ...response.pagination
                    }));
                }
            } else {
                console.error('Failed to fetch service fees:', response.error);
            }
        } catch (error) {
            console.error('Error fetching service fees:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchTypes = async () => {
        try {
            const response = await getServiceFeeTypes();
            if (response.success) {
                setTypes(response.data || []);
            }
        } catch (error) {
            console.error('Error fetching types:', error);
        }
    };

    const handleApplyFilters = () => {
        setPagination(prev => ({ ...prev, current_page: 1 }));
        fetchServiceFees();
    };

    const handleClearFilters = () => {
        setFilters({
            search: '',
            type: '',
            date_from: '',
            date_to: '',
        });
        setPagination(prev => ({ ...prev, current_page: 1 }));
        setTimeout(() => fetchServiceFees(), 100);
    };

    const handlePageChange = (newPage) => {
        setPagination(prev => ({ ...prev, current_page: newPage }));
    };

    const handlePerPageChange = (newPerPage) => {
        setPagination(prev => ({ ...prev, per_page: newPerPage, current_page: 1 }));
    };

    return (
        <>
            <Toolbar 
                pageTitle="Service Fees"
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
                </div>
            </Toolbar>

            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-xxl">
                    {/* Filters */}
                    {showFilters && (
                        <ServiceFeesFilters
                            filters={filters}
                            types={types}
                            onFilterChange={setFilters}
                            onApply={handleApplyFilters}
                            onClear={handleClearFilters}
                        />
                    )}

                    {/* Table */}
                    <div className="card">
                        <div className="card-header border-0 pt-6">
                            <div className="card-title">
                                <h3>Service Fees</h3>
                            </div>
                        </div>

                        <div className="card-body pt-0">
                            {loading ? (
                                <LoadingSpinner />
                            ) : (
                                <ServiceFeesTable
                                    serviceFees={serviceFees}
                                    pagination={pagination}
                                    onPageChange={handlePageChange}
                                    onPerPageChange={handlePerPageChange}
                                />
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

export default ServiceFeesIndex;

