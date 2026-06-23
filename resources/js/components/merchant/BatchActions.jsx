import React from 'react';
import { Link } from 'react-router-dom';

const BatchActions = ({ batch }) => {
    return (
        <div className="d-flex justify-content-end">
            <Link
                to={`/merchant/batches/${batch.id}`}
                className="btn btn-sm btn-light btn-active-light-primary"
                title="View Details"
            >
                <i className="ki-duotone ki-eye fs-5">
                    <span className="path1"></span>
                    <span className="path2"></span>
                    <span className="path3"></span>
                </i>
                View
            </Link>
        </div>
    );
};

export default BatchActions;

