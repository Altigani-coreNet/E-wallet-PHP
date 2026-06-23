import React, { useState, useEffect } from 'react';
import Swal from 'sweetalert2';
import { POS_API_V_2 } from '../../utils/constants';
import { get, post, del } from '../../utils/api';

export default function Taxes() {
    const [taxes, setTaxes] = useState([]);
    const [loading, setLoading] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [total, setTotal] = useState(0);
    const [showModal, setShowModal] = useState(false);
    const [editingTax, setEditingTax] = useState(null);
    const [formData, setFormData] = useState({
        name: { en: '' },
        rate: '',
        type: 'STANDARD',
        status: 1
    });

    const API_BASE_URL = POS_API_V_2;
    const TAX_TYPES = ['STANDARD', 'EXEMPTED', 'ZERO_RATED', 'RCM'];

    useEffect(() => {
        fetchTaxes();
    }, [searchTerm, currentPage]);

    const fetchTaxes = async () => {
        setLoading(true);
        try {
            const response = await get(`${API_BASE_URL}/taxes`, {
                params: {
                    search: searchTerm,
                    page: currentPage,
                    per_page: 10
                }
            });
            setTaxes(response.data.data.taxes);
            setTotal(response.data.data.total);
            setTotalPages(response.data.data.last_page || 1);
        } catch (error) {
            console.error('Error fetching taxes:', error);
            Swal.fire('Error', 'Failed to fetch taxes', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);

        try {
            if (editingTax) {
                await post(`${API_BASE_URL}/taxes/update/${editingTax.id}`, formData);
                Swal.fire('Success', 'Tax updated successfully', 'success');
            } else {
                await post(`${API_BASE_URL}/taxes/store`, formData);
                Swal.fire('Success', 'Tax created successfully', 'success');
                // Reset to page 1 to see the new item
                setCurrentPage(1);
            }
            handleCloseModal();
            // Small delay to ensure data is saved
            setTimeout(() => {
                fetchTaxes();
            }, 300);
        } catch (error) {
            console.error('Error saving tax:', error);
            Swal.fire('Error', error.response?.data?.message || 'Failed to save tax', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async (id) => {
        const result = await Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        });

        if (result.isConfirmed) {
            try {
                await del(`${API_BASE_URL}/api/v1/taxes/delete/${id}`);
                Swal.fire('Deleted!', 'Tax has been deleted.', 'success');
                // Refresh the list after deletion
                setTimeout(() => {
                    fetchTaxes();
                }, 300);
            } catch (error) {
                console.error('Error deleting tax:', error);
                Swal.fire('Error', 'Failed to delete tax', 'error');
            }
        }
    };

    const handleEdit = (tax) => {
        setEditingTax(tax);
        setFormData({
            name: typeof tax.name === 'object' ? tax.name : { en: tax.name },
            rate: tax.rate,
            type: tax.type || 'STANDARD',
            status: tax.status || 1
        });
        setShowModal(true);
    };

    const handleCloseModal = () => {
        setShowModal(false);
        setEditingTax(null);
        setFormData({
            name: { en: '' },
            rate: '',
            type: 'STANDARD',
            status: 1
        });
    };

    const handleOpenModal = () => {
        setEditingTax(null);
        setFormData({
            name: { en: '' },
            rate: '',
            type: 'STANDARD',
            status: 1
        });
        setShowModal(true);
    };

    return (
        <>
            {/* Breadcrumbs */}
            <div className="d-flex flex-column flex-column-fluid">
                <div id="kt_app_toolbar" className="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" className="app-container container-xxl d-flex flex-stack">
                        <div className="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                            <h1 className="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                                Taxes Management
                            </h1>
                            <ul className="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li className="breadcrumb-item text-muted">
                                    <a href="/merchant/sales/dashboard" className="text-muted text-hover-primary">Home</a>
                                </li>
                                <li className="breadcrumb-item">
                                    <span className="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li className="breadcrumb-item text-muted">Product Management</li>
                                <li className="breadcrumb-item">
                                    <span className="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li className="breadcrumb-item text-muted">Taxes</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {/* Main Content */}
                <div id="kt_app_content" className="app-content flex-column-fluid">
                    <div id="kt_app_content_container" className="app-container container-xxl">
                        <div className="card">
                            {/* Card Header */}
                            <div className="card-header border-0 pt-6">
                                <div className="card-title">
                                    {/* Search */}
                                    <div className="d-flex align-items-center position-relative my-1">
                                        <i className="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                                            <span className="path1"></span>
                                            <span className="path2"></span>
                                        </i>
                                        <input
                                            type="text"
                                            className="form-control form-control-solid w-250px ps-13"
                                            placeholder="Search taxes..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                        />
                                    </div>
                                </div>
                                <div className="card-toolbar">
                                    <div className="d-flex justify-content-end">
                                        <button className="btn btn-primary" onClick={handleOpenModal}>
                                            <i className="ki-duotone ki-plus fs-2"></i>
                                            Add Tax
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {/* Card Body */}
                            <div className="card-body pt-0">
                                {loading ? (
                                    <div className="text-center py-10">
                                        <div className="spinner-border text-primary" role="status">
                                            <span className="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                ) : (
                                    <>
                                        <div className="table-responsive">
                                            <table className="table align-middle table-row-dashed fs-6 gy-5">
                                                <thead>
                                                    <tr className="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                                        <th className="min-w-50px">ID</th>
                                                        <th className="min-w-125px">Name</th>
                                                        <th className="min-w-100px">Rate (%)</th>
                                                        <th className="text-end min-w-100px">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="text-gray-600 fw-semibold">
                                                    {taxes.length > 0 ? (
                                                        taxes.map((tax) => (
                                                            <tr key={tax.id}>
                                                                <td>{tax.id}</td>
                                                                <td>
                                                                    <div className="d-flex align-items-center">
                                                                        <div className="d-flex flex-column">
                                                                            <span className="text-gray-800 mb-1">
                                                                                {typeof tax.name === 'object' ? tax.name.en : tax.name}
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span className="badge badge-light-success fs-7 fw-bold">
                                                                        {tax.rate}%
                                                                    </span>
                                                                </td>
                                                                <td className="text-end">
                                                                    <button
                                                                        className="btn btn-sm btn-icon btn-light btn-active-light-primary me-2"
                                                                        onClick={() => handleEdit(tax)}
                                                                        title="Edit"
                                                                    >
                                                                        <i className="ki-duotone ki-pencil fs-2">
                                                                            <span className="path1"></span>
                                                                            <span className="path2"></span>
                                                                        </i>
                                                                    </button>
                                                                    <button
                                                                        className="btn btn-sm btn-icon btn-light btn-active-light-danger"
                                                                        onClick={() => handleDelete(tax.id)}
                                                                        title="Delete"
                                                                    >
                                                                        <i className="ki-duotone ki-trash fs-2">
                                                                            <span className="path1"></span>
                                                                            <span className="path2"></span>
                                                                            <span className="path3"></span>
                                                                            <span className="path4"></span>
                                                                            <span className="path5"></span>
                                                                        </i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        ))
                                                    ) : (
                                                        <tr>
                                                            <td colSpan="4" className="text-center py-10">
                                                                <div className="text-gray-600 fs-5">No taxes found</div>
                                                            </td>
                                                        </tr>
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>

                                        {/* Pagination */}
                                        {totalPages > 1 && (
                                            <div className="d-flex flex-stack flex-wrap pt-10">
                                                <div className="fs-6 fw-semibold text-gray-700">
                                                    Showing {taxes.length} of {total} entries
                                                </div>
                                                <ul className="pagination">
                                                    <li className={`page-item previous ${currentPage === 1 ? 'disabled' : ''}`}>
                                                        <button
                                                            className="page-link"
                                                            onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
                                                        >
                                                            <i className="previous"></i>
                                                        </button>
                                                    </li>
                                                    {[...Array(totalPages)].map((_, i) => (
                                                        <li key={i} className={`page-item ${currentPage === i + 1 ? 'active' : ''}`}>
                                                            <button
                                                                className="page-link"
                                                                onClick={() => setCurrentPage(i + 1)}
                                                            >
                                                                {i + 1}
                                                            </button>
                                                        </li>
                                                    ))}
                                                    <li className={`page-item next ${currentPage === totalPages ? 'disabled' : ''}`}>
                                                        <button
                                                            className="page-link"
                                                            onClick={() => setCurrentPage(prev => Math.min(prev + 1, totalPages))}
                                                        >
                                                            <i className="next"></i>
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        )}
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Modal */}
            {showModal && (
                <div className="modal fade show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content">
                            <div className="modal-header">
                                <h2 className="fw-bold">{editingTax ? 'Edit Tax' : 'Add New Tax'}</h2>
                                <div className="btn btn-icon btn-sm btn-active-icon-primary" onClick={handleCloseModal}>
                                    <i className="ki-duotone ki-cross fs-1">
                                        <span className="path1"></span>
                                        <span className="path2"></span>
                                    </i>
                                </div>
                            </div>
                            <form onSubmit={handleSubmit}>
                                <div className="modal-body py-10 px-lg-17">
                                    <div className="fv-row mb-7">
                                        <label className="required fs-6 fw-semibold mb-2">Tax Name</label>
                                        <input
                                            type="text"
                                            className="form-control form-control-solid"
                                            placeholder="Enter tax name"
                                            value={formData.name.en}
                                            onChange={(e) => setFormData({ ...formData, name: { en: e.target.value } })}
                                            required
                                        />
                                    </div>
                                    <div className="fv-row mb-7">
                                        <label className="required fs-6 fw-semibold mb-2">Tax Rate (%)</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            className="form-control form-control-solid"
                                            placeholder="Enter tax rate"
                                            value={formData.rate}
                                            onChange={(e) => setFormData({ ...formData, rate: e.target.value })}
                                            required
                                        />
                                    </div>
                                    <div className="fv-row mb-7">
                                        <label className="fs-6 fw-semibold mb-2">Tax Type</label>
                                        <select
                                            className="form-select form-select-solid"
                                            value={formData.type}
                                            onChange={(e) => setFormData({ ...formData, type: e.target.value })}
                                        >
                                            {TAX_TYPES.map((type) => (
                                                <option key={type} value={type}>
                                                    {type.replace('_', ' ')}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="fv-row">
                                        <label className="form-check form-switch form-check-custom form-check-solid">
                                            <input
                                                className="form-check-input"
                                                type="checkbox"
                                                checked={formData.status === 1}
                                                onChange={(e) => setFormData({ ...formData, status: e.target.checked ? 1 : 0 })}
                                            />
                                            <span className="form-check-label fw-semibold text-muted">
                                                Active Status
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <div className="modal-footer flex-center">
                                    <button type="button" className="btn btn-light me-3" onClick={handleCloseModal}>
                                        Cancel
                                    </button>
                                    <button type="submit" className="btn btn-primary" disabled={loading}>
                                        {loading ? (
                                            <span className="indicator-progress">
                                                Please wait... <span className="spinner-border spinner-border-sm align-middle ms-2"></span>
                                            </span>
                                        ) : (
                                            <span className="indicator-label">{editingTax ? 'Update' : 'Create'}</span>
                                        )}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
