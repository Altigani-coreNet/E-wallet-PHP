import React from 'react';

const ProfileCompletion = ({ completion, merchantCompletion }) => {
    const getProgressColor = (percentage) => {
        if (percentage >= 80) return 'success';
        if (percentage >= 50) return 'warning';
        return 'danger';
    };

    return (
        <div className="card">
            <div className="card-body">
                <h5 className="card-title mb-4">
                    <i className="fas fa-chart-line me-2"></i>
                    Profile Completion
                </h5>
                
                <div className="row g-4">
                    {/* User Profile Completion */}
                    {completion && (
                        <div className="col-md-6">
                            <h6 className="fw-bold mb-3">Personal Profile</h6>
                            <div className="mb-3">
                                <div className="d-flex justify-content-between mb-2">
                                    <span>Completion</span>
                                    <span className="fw-bold">{completion.completion}%</span>
                                </div>
                                <div className="progress" style={{ height: '20px' }}>
                                    <div 
                                        className={`progress-bar bg-${getProgressColor(completion.completion)}`}
                                        role="progressbar" 
                                        style={{ width: `${completion.completion}%` }}
                                        aria-valuenow={completion.completion} 
                                        aria-valuemin="0" 
                                        aria-valuemax="100"
                                    >
                                        {completion.completion}%
                                    </div>
                                </div>
                            </div>
                            
                            {completion.missing && completion.missing.length > 0 && (
                                <div className="alert alert-warning">
                                    <strong>Missing Fields:</strong>
                                    <ul className="mb-0 mt-2">
                                        {completion.missing.map((field, index) => (
                                            <li key={index}>{field}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Merchant Profile Completion */}
                    {merchantCompletion && (
                        <div className="col-md-6">
                            <h6 className="fw-bold mb-3">Business Profile</h6>
                            <div className="mb-3">
                                <div className="d-flex justify-content-between mb-2">
                                    <span>Completion</span>
                                    <span className="fw-bold">{merchantCompletion.completion}%</span>
                                </div>
                                <div className="progress" style={{ height: '20px' }}>
                                    <div 
                                        className={`progress-bar bg-${getProgressColor(merchantCompletion.completion)}`}
                                        role="progressbar" 
                                        style={{ width: `${merchantCompletion.completion}%` }}
                                        aria-valuenow={merchantCompletion.completion} 
                                        aria-valuemin="0" 
                                        aria-valuemax="100"
                                    >
                                        {merchantCompletion.completion}%
                                    </div>
                                </div>
                            </div>
                            
                            {merchantCompletion.missing && merchantCompletion.missing.length > 0 && (
                                <div className="alert alert-warning">
                                    <strong>Missing Requirements:</strong>
                                    <ul className="mb-0 mt-2">
                                        {merchantCompletion.missing.map((field, index) => (
                                            <li key={index}>{field}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            {/* Additional merchant stats */}
                            <div className="mt-3">
                                <div className="row g-2">
                                    <div className="col-6">
                                        <div className="card bg-light">
                                            <div className="card-body py-2">
                                                <small className="text-muted">Documents</small>
                                                <h6 className="mb-0">
                                                    {merchantCompletion.documents?.uploaded || 0} / {merchantCompletion.documents?.total_required || 4}
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-6">
                                        <div className="card bg-light">
                                            <div className="card-body py-2">
                                                <small className="text-muted">Users</small>
                                                <h6 className="mb-0">{merchantCompletion.users_count || 0}</h6>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-6">
                                        <div className="card bg-light">
                                            <div className="card-body py-2">
                                                <small className="text-muted">Terminals</small>
                                                <h6 className="mb-0">{merchantCompletion.terminals_count || 0}</h6>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-6">
                                        <div className="card bg-light">
                                            <div className="card-body py-2">
                                                <small className="text-muted">Status</small>
                                                <h6 className="mb-0">
                                                    <span className={`badge ${
                                                        merchantCompletion.status === 'approved' ? 'bg-success' : 
                                                        merchantCompletion.status === 'rejected' ? 'bg-danger' : 
                                                        'bg-warning'
                                                    }`}>
                                                        {merchantCompletion.status || 'pending'}
                                                    </span>
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default ProfileCompletion;

