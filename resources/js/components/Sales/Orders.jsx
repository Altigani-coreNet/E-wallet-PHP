import React from 'react';
import { Link } from 'react-router-dom';

export default function Orders() {
    const orders = [
        { id: 'ORD-001', customer: 'John Doe', amount: '$250.00', status: 'Completed', date: '2024-10-22' },
        { id: 'ORD-002', customer: 'Jane Smith', amount: '$180.00', status: 'Pending', date: '2024-10-22' },
        { id: 'ORD-003', customer: 'Mike Johnson', amount: '$320.00', status: 'Completed', date: '2024-10-21' },
        { id: 'ORD-004', customer: 'Sarah Williams', amount: '$150.00', status: 'Processing', date: '2024-10-21' },
        { id: 'ORD-005', customer: 'Tom Brown', amount: '$420.00', status: 'Completed', date: '2024-10-20' },
    ];

    const getStatusBadge = (status) => {
        const statusColors = {
            'Completed': 'success',
            'Pending': 'warning',
            'Processing': 'info',
            'Cancelled': 'danger'
        };
        return `badge bg-${statusColors[status] || 'secondary'}`;
    };

    return (
        <div className="container-fluid">
            <div className="row">
                <div className="col-12">
                    <div className="card">
                        <div className="card-header d-flex justify-content-between align-items-center">
                            <h4 className="card-title mb-0">Orders Management</h4>
                            <button className="btn btn-primary">
                                <i className="bx bx-plus me-1"></i> New Order
                            </button>
                        </div>
                        <div className="card-body">
                            <div className="row mb-3">
                                <div className="col-md-6">
                                    <div className="input-group">
                                        <input 
                                            type="text" 
                                            className="form-control" 
                                            placeholder="Search orders..." 
                                        />
                                        <button className="btn btn-outline-secondary" type="button">
                                            <i className="bx bx-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div className="col-md-6">
                                    <select className="form-select">
                                        <option value="">All Status</option>
                                        <option value="completed">Completed</option>
                                        <option value="pending">Pending</option>
                                        <option value="processing">Processing</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>

                            <div className="table-responsive">
                                <table className="table table-hover table-striped table-nowrap mb-0">
                                    <thead className="table-light">
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {orders.map((order) => (
                                            <tr key={order.id}>
                                                <td>
                                                    <Link to={`/merchant/sales/orders/${order.id}`} className="text-primary">
                                                        {order.id}
                                                    </Link>
                                                </td>
                                                <td>{order.customer}</td>
                                                <td className="fw-bold">{order.amount}</td>
                                                <td>
                                                    <span className={getStatusBadge(order.status)}>
                                                        {order.status}
                                                    </span>
                                                </td>
                                                <td>{order.date}</td>
                                                <td>
                                                    <div className="btn-group" role="group">
                                                        <button className="btn btn-sm btn-info" title="View">
                                                            <i className="bx bx-show"></i>
                                                        </button>
                                                        <button className="btn btn-sm btn-warning" title="Edit">
                                                            <i className="bx bx-edit"></i>
                                                        </button>
                                                        <button className="btn btn-sm btn-danger" title="Delete">
                                                            <i className="bx bx-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="row mt-3">
                                <div className="col-12">
                                    <nav aria-label="Page navigation">
                                        <ul className="pagination justify-content-end">
                                            <li className="page-item disabled">
                                                <a className="page-link" href="#" tabIndex="-1">Previous</a>
                                            </li>
                                            <li className="page-item active"><a className="page-link" href="#">1</a></li>
                                            <li className="page-item"><a className="page-link" href="#">2</a></li>
                                            <li className="page-item"><a className="page-link" href="#">3</a></li>
                                            <li className="page-item">
                                                <a className="page-link" href="#">Next</a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

