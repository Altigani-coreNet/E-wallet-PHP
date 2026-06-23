import React, { useState, useEffect } from 'react';
import Swal from 'sweetalert2';
import { POS_API_V_2 } from '../../utils/constants';
import { get, post, put, del } from '../../utils/api';

export default function Warehouse() {
    const [warehouses, setWarehouses] = useState([]);
    const [loading, setLoading] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [total, setTotal] = useState(0);
    const [showModal, setShowModal] = useState(false);
    const [editingWarehouse, setEditingWarehouse] = useState(null);
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        phone: '',
        address: ''
    });

    const API_BASE_URL = POS_API_V_2;

    useEffect(() => {
        fetchWarehouses();
    }, [searchTerm, currentPage]);

    const fetchWarehouses = async () => {
        setLoading(true);
        try {
            const response = await get(`${API_BASE_URL}/api/v1/warehouses`, {
                params: {
                    search: searchTerm,
                    page: currentPage,
                    per_page: 10
                }
            });
            setWarehouses(response.data.data.warehouses);
            setTotal(response.data.data.total);
            setTotalPages(response.data.data.last_page || 1);
        } catch (error) {
            console.error('Error fetching warehouses:', error);
            Swal.fire('Error', 'Failed to fetch warehouses', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);

        try {
            if (editingWarehouse) {
                await put(`${API_BASE_URL}/api/v1/warehouses/update/${editingWarehouse.id}`, formData);
                Swal.fire('Success', 'Warehouse updated successfully', 'success');
            } else {
                await post(`${API_BASE_URL}/api/v1/warehouses/store`, formData);
                Swal.fire('Success', 'Warehouse created successfully', 'success');
                // Reset to page 1 to see the new item
                setCurrentPage(1);
            }
            handleCloseModal();
            // Small delay to ensure data is saved
            setTimeout(() => {
                fetchWarehouses();
            }, 300);
        } catch (error) {
            console.error('Error saving warehouse:', error);
            Swal.fire('Error', error.response?.data?.message || 'Failed to save warehouse', 'error');
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
                await del(`${API_BASE_URL}/api/v1/warehouses/delete/${id}`);
                Swal.fire('Deleted!', 'Warehouse has been deleted.', 'success');
                // Refresh the list after deletion
                setTimeout(() => {
                    fetchWarehouses();
                }, 300);
            } catch (error) {
                console.error('Error deleting warehouse:', error);
                Swal.fire('Error', 'Failed to delete warehouse', 'error');
            }
        }
    };

    const handleEdit = (warehouse) => {
        setEditingWarehouse(warehouse);
        setFormData({
            name: warehouse.name,
            email: warehouse.email || '',
            phone: warehouse.phone || '',
            address: warehouse.address || ''
        });
        setShowModal(true);
    };

    const handleCloseModal = () => {
        setShowModal(false);
        setEditingWarehouse(null);
        setFormData({
            name: '',
            email: '',
            phone: '',
            address: ''
        });
    };

    const handleOpenModal = () => {
        setEditingWarehouse(null);
        setFormData({
            name: '',
            email: '',
            phone: '',
            address: ''
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
                                Warehouse Management
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
                                <li className="breadcrumb-item text-muted">Warehouse</li>
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
                                            placeholder="Search warehouse..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                        />
                                    </div>
                                </div>
                                <div className="card-toolbar">
                                    <div className="d-flex justify-content-end">
                                        <button className="btn btn-primary" onClick={handleOpenModal}>
                                            <i className="ki-duotone ki-plus fs-2"></i>
                                            Add Warehouse
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
                                                        <th className="min-w-125px">Email</th>
                                                        <th className="min-w-100px">Phone</th>
                                                        <th className="min-w-125px">Address</th>
                                                        <th className="min-w-100px">Purchases</th>
                                                        <th className="text-end min-w-100px">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="text-gray-600 fw-semibold">
                                                    {warehouses.length > 0 ? (
                                                        warehouses.map((warehouse) => (
                                                            <tr key={warehouse.id}>
                                                                <td>{warehouse.id}</td>
                                                                <td>
                                                                    <div className="d-flex align-items-center">
                                                                        <div className="d-flex flex-column">
                                                                            <span className="text-gray-800 mb-1">{warehouse.name}</span>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>{warehouse.email || '-'}</td>
                                                                <td>{warehouse.phone || '-'}</td>
                                                                <td>
                                                                    <span className="text-gray-700">
                                                                        {warehouse.address ? (warehouse.address.length > 30 ? warehouse.address.substring(0, 30) + '...' : warehouse.address) : '-'}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span className="badge badge-light-info">
                                                                        {warehouse.total_purchages || 0}
                                                                    </span>
                                                                </td>
                                                                <td className="text-end">
                                                                    <button
                                                                        className="btn btn-sm btn-icon btn-light btn-active-light-primary me-2"
                                                                        onClick={() => handleEdit(warehouse)}
                                                                        title="Edit"
                                                                    >
                                                                        <i className="ki-duotone ki-pencil fs-2">
                                                                            <span className="path1"></span>
                                                                            <span className="path2"></span>
                                                                        </i>
                                                                    </button>
                                                                    <button
                                                                        className="btn btn-sm btn-icon btn-light btn-active-light-danger"
                                                                        onClick={() => handleDelete(warehouse.id)}
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
                                                            <td colSpan="7" className="text-center py-10">
                                                                <div className="text-gray-600 fs-5">No warehouses found</div>
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
                                                    Showing {warehouses.length} of {total} entries
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
                    <div className="modal-dialog modal-dialog-centered mw-650px">
                        <div className="modal-content">
                            <div className="modal-header">
                                <h2 className="fw-bold">{editingWarehouse ? 'Edit Warehouse' : 'Add New Warehouse'}</h2>
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
                                        <label className="required fs-6 fw-semibold mb-2">Warehouse Name</label>
                                        <input
                                            type="text"
                                            className="form-control form-control-solid"
                                            placeholder="Enter warehouse name"
                                            value={formData.name}
                                            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                            required
                                        />
                                    </div>
                                    <div className="fv-row mb-7">
                                        <label className="fs-6 fw-semibold mb-2">Email</label>
                                        <input
                                            type="email"
                                            className="form-control form-control-solid"
                                            placeholder="Enter email address"
                                            value={formData.email}
                                            onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                        />
                                    </div>
                                    <div className="fv-row mb-7">
                                        <label className="fs-6 fw-semibold mb-2">Phone</label>
                                        <input
                                            type="text"
                                            className="form-control form-control-solid"
                                            placeholder="Enter phone number"
                                            value={formData.phone}
                                            onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                                        />
                                    </div>
                                    <div className="fv-row mb-7">
                                        <label className="fs-6 fw-semibold mb-2">Address</label>
                                        <textarea
                                            className="form-control form-control-solid"
                                            rows="3"
                                            placeholder="Enter warehouse address"
                                            value={formData.address}
                                            onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                                        ></textarea>
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
                                            <span className="indicator-label">{editingWarehouse ? 'Update' : 'Create'}</span>
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

