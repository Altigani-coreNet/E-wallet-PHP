import React from 'react';

const TransactionActions = ({ transaction, onView }) => {
    return (
        <button
            className="btn btn-sm btn-light btn-active-light-primary"
            onClick={() => onView(transaction)}
            title="View Details"
        >
            <i className="ki-duotone ki-eye fs-5 me-2">
                <span className="path1"></span>
                <span className="path2"></span>
                <span className="path3"></span>
            </i>
            View
        </button>
    );
};

export default TransactionActions;

