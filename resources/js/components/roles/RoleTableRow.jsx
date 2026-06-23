import React from 'react';
import { formatDate } from '../../utils/dateUtils';

const RoleTableRow = ({ role, onDelete, typeParam }) => {
    const handleDelete = () => {
        onDelete(role.id);
    };

    const editUrl = `/merchant/sales/roles/${role.id}/edit${typeParam ? `?type=${typeParam}` : ''}`;
    const viewUrl = `/merchant/sales/roles/${role.id}${typeParam ? `?type=${typeParam}` : ''}`;

    const isSystemRole = role.name === 'Super Admin' || role.is_system_role;

    return (
        <tr>
            <td>
                <span className="badge badge-light-primary">{role.id}</span>
            </td>
            <td>
                <div className="d-flex flex-column">
                    <span className="text-dark fw-bold">{role.name}</span>
                    {role.display_name && (
                        <span className="text-muted fs-7">{role.display_name}</span>
                    )}
                </div>
            </td>
            <td>
                {role.module === 'pos' && (
                    <span className="badge badge-light-primary">
                        <i className="ki-duotone ki-shop fs-6 me-1">
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        POS
                    </span>
                )}
                {role.module === 'sales' && (
                    <span className="badge badge-light-success">
                        <i className="ki-duotone ki-chart-line-up fs-6 me-1">
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        Sales
                    </span>
                )}
                {!role.module && (
                    <span className="badge badge-light-info">
                        <i className="ki-duotone ki-category fs-6 me-1">
                            <span className="path1"></span>
                            <span className="path2"></span>
                        </i>
                        All
                    </span>
                )}
            </td>
            <td>
                <span className="badge badge-light-success">
                    {role.permissions_count || role.permissions?.length || 0} permissions
                </span>
            </td>
            <td>
                <span className="text-muted">{formatDate(role.created_at)}</span>
            </td>
            <td className="text-end">
                {/* Actions Dropdown */}
                <button
                    className="btn btn-sm btn-light btn-active-light-primary"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >
                    Actions
                    <i className="ki-duotone ki-down fs-5 ms-1"></i>
                </button>
                <ul className="dropdown-menu dropdown-menu-end">
                    {/* View Action */}
                    <li>
                        <a className="dropdown-item" href={viewUrl}>
                            <i className="ki-duotone ki-eye fs-5 me-2">
                                <span className="path1"></span>
                                <span className="path2"></span>
                                <span className="path3"></span>
                            </i>
                            View Details
                        </a>
                    </li>
                    
                    {/* Edit Action */}
                    <li>
                        <a className="dropdown-item" href={editUrl}>
                            <i className="ki-duotone ki-pencil fs-5 me-2">
                                <span className="path1"></span>
                                <span className="path2"></span>
                            </i>
                            Edit Role
                        </a>
                    </li>

                    {/* Divider */}
                    {!isSystemRole && <li><hr className="dropdown-divider" /></li>}
                    
                    {/* Delete Action */}
                    {!isSystemRole && (
                        <li>
                            <button
                                className="dropdown-item text-danger"
                                onClick={handleDelete}
                            >
                                <i className="ki-duotone ki-trash fs-5 me-2">
                                    <span className="path1"></span>
                                    <span className="path2"></span>
                                    <span className="path3"></span>
                                    <span className="path4"></span>
                                    <span className="path5"></span>
                                </i>
                                Delete Role
                            </button>
                        </li>
                    )}

                    {/* System Role Message */}
                    {isSystemRole && (
                        <li>
                            <span className="dropdown-item text-muted disabled">
                                <i className="ki-duotone ki-shield-tick fs-5 me-2">
                                    <span className="path1"></span>
                                    <span className="path2"></span>
                                </i>
                                System Role (Protected)
                            </span>
                        </li>
                    )}
                </ul>
            </td>
        </tr>
    );
};

export default RoleTableRow;

