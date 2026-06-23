import React, { useState, useRef, useEffect } from 'react';
import { deleteTerminal } from '../../services/terminalsService';
import Swal from 'sweetalert2';

const TerminalTableRow = ({ terminal, branch, isSelected, onSelect, onRefresh }) => {
    const [showDropdown, setShowDropdown] = useState(false);
    const dropdownRef = useRef(null);

    // Close dropdown when clicking outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setShowDropdown(false);
            }
        };

        if (showDropdown) {
            document.addEventListener('mousedown', handleClickOutside);
        }

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, [showDropdown]);

    const toggleDropdown = () => {
        setShowDropdown(!showDropdown);
    };

    const handleDelete = async () => {
        setShowDropdown(false);
        const result = await Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete terminal "${terminal.name}". This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        });

        if (result.isConfirmed) {
            try {
                const response = await deleteTerminal(terminal.id);
                if (response.success) {
                    await Swal.fire({
                        title: 'Deleted!',
                        text: 'Terminal has been deleted successfully.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    onRefresh();
                } else {
                    Swal.fire('Error!', response.error || 'Failed to delete terminal.', 'error');
                }
            } catch (error) {
                Swal.fire('Error!', 'An unexpected error occurred.', 'error');
            }
        }
    };

    const handleView = () => {
        setShowDropdown(false);
        window.location.href = `/merchant/terminals/${terminal.id}`;
    };

    const handleEdit = () => {
        setShowDropdown(false);
        window.location.href = `/merchant/terminals/${terminal.id}/edit`;
    };

    return (
        <tr>
            <td>
                <div className="form-check form-check-sm form-check-custom form-check-solid">
                    <input 
                        className="form-check-input" 
                        type="checkbox" 
                        checked={isSelected}
                        onChange={onSelect}
                    />
                </div>
            </td>
            <td>
                <a href={`/merchant/terminals/${terminal.id}`} className="text-gray-800 text-hover-primary">
                    {terminal.name}
                </a>
            </td>
            <td>
                <span className="badge badge-light-info">{terminal.terminal_id || 'N/A'}</span>
            </td>
            <td>{branch ? branch.name : (terminal.branch_id ? 'Loading...' : 'N/A')}</td>
            <td>{terminal.model || 'N/A'}</td>
            <td>{terminal.manufacturer || 'N/A'}</td>
            <td>
                {terminal.is_active ? (
                    <span className="badge badge-light-success">Active</span>
                ) : (
                    <span className="badge badge-light-danger">Inactive</span>
                )}
            </td>
            <td className="text-end">
                <div className="position-relative" ref={dropdownRef}>
                    <button
                        type="button"
                        className="btn btn-sm btn-icon btn-light btn-active-light-primary"
                        onClick={toggleDropdown}
                    >
                        <i className="ki-duotone ki-category fs-5 m-0">
                            <span className="path1"></span>
                            <span className="path2"></span>
                            <span className="path3"></span>
                            <span className="path4"></span>
                        </i>
                    </button>
                    {showDropdown && (
                        <div 
                            className="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4 show" 
                            style={{
                                position: 'absolute',
                                right: 0,
                                top: '100%',
                                zIndex: 105,
                                marginTop: '0.5rem'
                            }}
                        >
                            <div className="menu-item px-3">
                                <button onClick={handleView} className="menu-link px-3 w-100 text-start">
                                    View
                                </button>
                            </div>
                            <div className="menu-item px-3">
                                <button onClick={handleEdit} className="menu-link px-3 w-100 text-start">
                                    Edit
                                </button>
                            </div>
                            <div className="menu-item px-3">
                                <button onClick={handleDelete} className="menu-link px-3 w-100 text-start text-danger">
                                    Delete
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </td>
        </tr>
    );
};

export default TerminalTableRow;

