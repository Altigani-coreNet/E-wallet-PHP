import React, { useState, useRef } from 'react';
import { Card, Button, Form, Alert, Spinner, ProgressBar } from 'react-bootstrap';
import axios from 'axios';
import ImportPreviewModal from './ImportPreviewModal';

const ProductImport = () => {
    const [selectedFile, setSelectedFile] = useState(null);
    const [loading, setLoading] = useState(false);
    const [previewData, setPreviewData] = useState(null);
    const [showPreviewModal, setShowPreviewModal] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    const fileInputRef = useRef(null);

    const API_BASE = 'http://localhost:8002'; // Your API base URL

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            // Validate file type
            const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
            if (!validTypes.includes(file.type)) {
                setError('Please upload a valid Excel file (.xlsx or .xls)');
                setSelectedFile(null);
                return;
            }

            // Validate file size (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                setError('File size must be less than 10MB');
                setSelectedFile(null);
                return;
            }

            setSelectedFile(file);
            setError(null);
            setSuccess(null);
        }
    };

    const handleDownloadTemplate = async () => {
        try {
            setLoading(true);
            const token = localStorage.getItem('token'); // Get your auth token
            
            const response = await axios.get(`${API_BASE}/api/v1/products/export-template`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                },
                responseType: 'blob'
            });

            // Create download link
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', 'products_import_template.xlsx');
            document.body.appendChild(link);
            link.click();
            link.remove();
            
            setSuccess('Template downloaded successfully!');
        } catch (err) {
            setError('Failed to download template: ' + (err.response?.data?.message || err.message));
        } finally {
            setLoading(false);
        }
    };

    const handlePreview = async () => {
        if (!selectedFile) {
            setError('Please select a file first');
            return;
        }

        try {
            setLoading(true);
            setError(null);
            
            const token = localStorage.getItem('token');
            const formData = new FormData();
            formData.append('file', selectedFile);

            const response = await axios.post(
                `${API_BASE}/api/v1/products/import-preview`,
                formData,
                {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'multipart/form-data'
                    }
                }
            );

            if (response.data.success) {
                setPreviewData(response.data.data);
                setShowPreviewModal(true);
            } else {
                setError(response.data.message || 'Failed to preview file');
            }

        } catch (err) {
            console.error('Preview error:', err);
            setError('Failed to preview file: ' + (err.response?.data?.message || err.message));
        } finally {
            setLoading(false);
        }
    };

    const handleImportSuccess = (result) => {
        setSuccess(
            `Import completed! ${result.imported} products imported, ${result.updated} updated.`
        );
        
        // Reset form
        setSelectedFile(null);
        setPreviewData(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }

        // Optional: Refresh your products list
        // refreshProductsList();
    };

    const handleCloseModal = () => {
        setShowPreviewModal(false);
        setPreviewData(null);
    };

    return (
        <div className="container mt-4">
            <Card>
                <Card.Header className="bg-primary text-white">
                    <h5 className="mb-0">
                        📥 Import Products from Excel
                    </h5>
                </Card.Header>
                <Card.Body>
                    {/* Instructions */}
                    <Alert variant="info">
                        <h6>📋 How to Import Products:</h6>
                        <ol className="mb-0">
                            <li>Download the Excel template below</li>
                            <li>Fill in your product data (use dropdowns in the template)</li>
                            <li>Upload the completed file</li>
                            <li>Preview and validate your data</li>
                            <li>Confirm to import to database</li>
                        </ol>
                    </Alert>

                    {/* Success Message */}
                    {success && (
                        <Alert variant="success" dismissible onClose={() => setSuccess(null)}>
                            {success}
                        </Alert>
                    )}

                    {/* Error Message */}
                    {error && (
                        <Alert variant="danger" dismissible onClose={() => setError(null)}>
                            {error}
                        </Alert>
                    )}

                    {/* Download Template */}
                    <div className="mb-4">
                        <h6>Step 1: Download Template</h6>
                        <p className="text-muted small">
                            The template includes dropdown lists and reference sheets for easy data entry.
                        </p>
                        <Button 
                            variant="outline-primary" 
                            onClick={handleDownloadTemplate}
                            disabled={loading}
                        >
                            {loading ? (
                                <>
                                    <Spinner animation="border" size="sm" className="me-2" />
                                    Downloading...
                                </>
                            ) : (
                                <>
                                    📥 Download Excel Template
                                </>
                            )}
                        </Button>
                    </div>

                    <hr />

                    {/* Upload File */}
                    <div className="mb-4">
                        <h6>Step 2: Upload Your File</h6>
                        <Form.Group>
                            <Form.Control
                                ref={fileInputRef}
                                type="file"
                                accept=".xlsx,.xls"
                                onChange={handleFileChange}
                                disabled={loading}
                            />
                            {selectedFile && (
                                <div className="mt-2">
                                    <small className="text-success">
                                        ✅ Selected: {selectedFile.name} ({(selectedFile.size / 1024).toFixed(2)} KB)
                                    </small>
                                </div>
                            )}
                        </Form.Group>
                    </div>

                    {/* Preview Button */}
                    <div className="d-flex gap-2">
                        <Button 
                            variant="primary" 
                            onClick={handlePreview}
                            disabled={!selectedFile || loading}
                            size="lg"
                        >
                            {loading ? (
                                <>
                                    <Spinner animation="border" size="sm" className="me-2" />
                                    Validating...
                                </>
                            ) : (
                                <>
                                    🔍 Preview & Validate
                                </>
                            )}
                        </Button>

                        {selectedFile && (
                            <Button 
                                variant="outline-secondary" 
                                onClick={() => {
                                    setSelectedFile(null);
                                    setError(null);
                                    if (fileInputRef.current) {
                                        fileInputRef.current.value = '';
                                    }
                                }}
                            >
                                Clear
                            </Button>
                        )}
                    </div>

                    {/* Progress Bar (optional - can be shown during preview) */}
                    {loading && (
                        <div className="mt-3">
                            <ProgressBar animated now={100} label="Processing..." />
                        </div>
                    )}
                </Card.Body>

                <Card.Footer className="text-muted">
                    <small>
                        💡 Tip: Use the dropdown arrows in the Excel template to select valid values. 
                        Check the reference sheets (Categories, Brands, Tags, etc.) for available options.
                    </small>
                </Card.Footer>
            </Card>

            {/* Preview Modal */}
            <ImportPreviewModal
                show={showPreviewModal}
                onHide={handleCloseModal}
                previewData={previewData}
                file={selectedFile}
                onImportSuccess={handleImportSuccess}
            />
        </div>
    );
};

export default ProductImport;

