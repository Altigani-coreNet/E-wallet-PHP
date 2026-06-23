import React, { useState } from 'react';
import { importTerminals, previewImport, exportTemplate } from '../../services/terminalsService';
import Swal from 'sweetalert2';

const ImportTerminalsModal = ({ show, onClose, onSuccess }) => {
    const [file, setFile] = useState(null);
    const [importing, setImporting] = useState(false);
    const [previewing, setPreviewing] = useState(false);
    const [previewData, setPreviewData] = useState(null);

    const handleFileChange = (e) => {
        const selectedFile = e.target.files[0];
        if (selectedFile) {
            setFile(selectedFile);
            setPreviewData(null);
        }
    };

    const handleDownloadTemplate = async () => {
        try {
            const response = await exportTemplate();
            if (!response.success) {
                Swal.fire('Error!', response.error || 'Failed to download template.', 'error');
            }
        } catch (error) {
            Swal.fire('Error!', 'An unexpected error occurred.', 'error');
        }
    };

    const handlePreview = async () => {
        if (!file) {
            Swal.fire('Warning!', 'Please select a file first.', 'warning');
            return;
        }

        setPreviewing(true);
        try {
            const response = await previewImport(file);
            if (response.success) {
                setPreviewData(response.rows || []);
            } else {
                Swal.fire('Error!', response.message || 'Failed to preview import.', 'error');
            }
        } catch (error) {
            Swal.fire('Error!', 'An unexpected error occurred.', 'error');
        } finally {
            setPreviewing(false);
        }
    };

    const handleImport = async () => {
        if (!file) {
            Swal.fire('Warning!', 'Please select a file first.', 'warning');
            return;
        }

        setImporting(true);
        try {
            const response = await importTerminals(file);
            if (response.success) {
                await Swal.fire({
                    title: 'Success!',
                    text: response.message || 'Terminals imported successfully.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                onSuccess();
            } else {
                let errorMessage = response.message || 'Failed to import terminals.';
                if (response.errors && response.errors.length > 0) {
                    errorMessage += '\n\nErrors:\n' + response.errors.join('\n');
                }
                Swal.fire('Error!', errorMessage, 'error');
            }
        } catch (error) {
            Swal.fire('Error!', 'An unexpected error occurred.', 'error');
        } finally {
            setImporting(false);
        }
    };

    if (!show) return null;

    return (
        <>
            {/* Modal Backdrop */}
            <div className="modal-backdrop fade show" onClick={onClose}></div>

            {/* Modal */}
            <div className="modal fade show d-block" tabIndex="-1" role="dialog">
                <div className="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div className="modal-content">
                        {/* Header */}
                        <div className="modal-header">
                            <h5 className="modal-title">Import Terminals</h5>
                            <button type="button" className="btn-close" onClick={onClose}></button>
                        </div>

                        {/* Body */}
                        <div className="modal-body">
                            {/* Download Template */}
                            <div className="mb-5">
                                <div className="alert alert-info d-flex align-items-center">
                                    <i className="ki-duotone ki-information-5 fs-2x text-info me-3">
                                        <span className="path1"></span>
                                        <span className="path2"></span>
                                        <span className="path3"></span>
                                    </i>
                                    <div className="d-flex flex-column">
                                        <span>Download the import template to see the required format.</span>
                                        <button 
                                            type="button" 
                                            className="btn btn-sm btn-light-info mt-2 align-self-start"
                                            onClick={handleDownloadTemplate}
                                        >
                                            Download Template
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {/* File Upload */}
                            <div className="mb-5">
                                <label className="form-label required">Select File</label>
                                <input
                                    type="file"
                                    className="form-control"
                                    accept=".xlsx,.xls,.csv"
                                    onChange={handleFileChange}
                                />
                                <div className="form-text">Accepted formats: Excel (.xlsx, .xls) or CSV (.csv)</div>
                            </div>

                            {/* Preview Button */}
                            {file && !previewData && (
                                <div className="mb-5">
                                    <button 
                                        type="button" 
                                        className="btn btn-light-primary"
                                        onClick={handlePreview}
                                        disabled={previewing}
                                    >
                                        {previewing ? (
                                            <>
                                                <span className="spinner-border spinner-border-sm me-2"></span>
                                                Previewing...
                                            </>
                                        ) : (
                                            'Preview Data'
                                        )}
                                    </button>
                                </div>
                            )}

                            {/* Preview Data */}
                            {previewData && previewData.length > 0 && (
                                <div className="mb-5">
                                    <h6 className="mb-3">Preview ({previewData.length} rows)</h6>
                                    <div className="table-responsive" style={{ maxHeight: '300px', overflowY: 'auto' }}>
                                        <table className="table table-sm table-bordered">
                                            <thead className="table-light">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Terminal ID</th>
                                                    <th>Branch</th>
                                                    <th>Model</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {previewData.slice(0, 10).map((row, index) => (
                                                    <tr key={index}>
                                                        <td>{row.name || 'N/A'}</td>
                                                        <td>{row.terminal_id || 'Auto'}</td>
                                                        <td>{row.branch || 'N/A'}</td>
                                                        <td>{row.model || 'N/A'}</td>
                                                        <td>{row.is_active || 'Active'}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                        {previewData.length > 10 && (
                                            <div className="text-muted text-center mt-2">
                                                Showing 10 of {previewData.length} rows
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Footer */}
                        <div className="modal-footer">
                            <button type="button" className="btn btn-light" onClick={onClose}>
                                Cancel
                            </button>
                            <button 
                                type="button" 
                                className="btn btn-primary"
                                onClick={handleImport}
                                disabled={!file || importing}
                            >
                                {importing ? (
                                    <>
                                        <span className="spinner-border spinner-border-sm me-2"></span>
                                        Importing...
                                    </>
                                ) : (
                                    'Import Terminals'
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

export default ImportTerminalsModal;

