import React, { useState } from 'react';
import { Modal, Button, Table, Badge, Alert, Spinner } from 'react-bootstrap';
import axios from 'axios';

const ImportPreviewModal = ({ show, onHide, previewData, file, onImportSuccess }) => {
    const [importing, setImporting] = useState(false);
    const [importResult, setImportResult] = useState(null);

    if (!previewData) return null;

    const { summary, products } = previewData;
    const API_BASE = 'http://localhost:8002'; // Your API base URL

    const handleConfirmImport = async () => {
        if (!file) {
            setImportResult({
                success: false,
                message: 'No file selected for import'
            });
            return;
        }

        try {
            setImporting(true);
            
            const token = localStorage.getItem('token');
            const formData = new FormData();
            formData.append('file', file);

            // Call the actual import API
            const response = await axios.post(
                `${API_BASE}/api/v1/products/import`,
                formData,
                {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'multipart/form-data'
                    }
                }
            );

            if (response.data.success) {
                setImportResult({
                    success: true,
                    message: response.data.message,
                    data: response.data.data
                });

                // Call success callback
                if (onImportSuccess) {
                    onImportSuccess(response.data.data);
                }

                // Close modal after 2 seconds
                setTimeout(() => {
                    onHide();
                    setImportResult(null); // Reset for next time
                }, 2000);
            } else {
                setImportResult({
                    success: false,
                    message: response.data.message || 'Import failed'
                });
            }

        } catch (error) {
            console.error('Import error:', error);
            setImportResult({
                success: false,
                message: error.response?.data?.message || 'Import failed: ' + error.message,
                errors: error.response?.data?.errors || []
            });
        } finally {
            setImporting(false);
        }
    };

    return (
        <Modal show={show} onHide={onHide} size="xl" centered>
            <Modal.Header closeButton>
                <Modal.Title>
                    📋 Import Preview - Validation Results
                </Modal.Title>
            </Modal.Header>

            <Modal.Body style={{ maxHeight: '70vh', overflowY: 'auto' }}>
                {/* Summary Alert */}
                <Alert variant={summary.total_rows === 0 ? 'info' : (summary.can_import ? 'success' : 'warning')} className="mb-3">
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Summary:</strong> {products.length} rows found, {summary.total_rows} actual products
                        </div>
                        <div>
                            <Badge bg="success" className="me-2">
                                ✅ Valid: {summary.valid_count}
                            </Badge>
                            {summary.invalid_count > 0 && (
                                <Badge bg="danger" className="me-2">
                                    ❌ Invalid: {summary.invalid_count}
                                </Badge>
                            )}
                            {products.filter(p => ['sample', 'instruction', 'empty'].includes(p.row_type)).length > 0 && (
                                <Badge bg="secondary">
                                    ⊘ Skipped: {products.filter(p => ['sample', 'instruction', 'empty'].includes(p.row_type)).length}
                                </Badge>
                            )}
                        </div>
                    </div>
                    <hr />
                    <div className="mt-2">
                        {summary.total_rows === 0 ? (
                            <span className="text-info">
                                ℹ️ No actual products found. This file contains only sample data or instructions. Please add your own products to the Excel template and upload again.
                            </span>
                        ) : summary.can_import ? (
                            <span className="text-success">
                                ✅ All {summary.total_rows} products are valid and ready to import!
                            </span>
                        ) : (
                            <span className="text-danger">
                                ⚠️ {summary.invalid_count} of {summary.total_rows} products have errors. Please review below.
                            </span>
                        )}
                    </div>
                </Alert>

                {/* Legend */}
                <Alert variant="light" className="mb-4 py-2">
                    <div className="d-flex justify-content-around align-items-center small">
                        <div><Badge bg="success">✅</Badge> <span className="ms-1">Valid - Will be imported</span></div>
                        <div><Badge bg="danger">❌</Badge> <span className="ms-1">Invalid - Has errors</span></div>
                        <div><Badge bg="info">ℹ️</Badge> <span className="ms-1">Instruction row - Skipped</span></div>
                        <div><Badge bg="warning">📝</Badge> <span className="ms-1">Sample data - Skipped</span></div>
                        <div><Badge bg="secondary">⊘</Badge> <span className="ms-1">Empty - Skipped</span></div>
                    </div>
                </Alert>

                {/* Import Result */}
                {importResult && (
                    <Alert variant={importResult.success ? 'success' : 'danger'} className="mb-4">
                        <strong>{importResult.message}</strong>
                        {importResult.success && importResult.data && (
                            <div className="mt-2">
                                <div>✅ Imported: {importResult.data.imported}</div>
                                <div>🔄 Updated: {importResult.data.updated}</div>
                                {importResult.data.failed > 0 && (
                                    <div className="text-danger">❌ Failed: {importResult.data.failed}</div>
                                )}
                            </div>
                        )}
                    </Alert>
                )}

                {/* Products Table */}
                <div className="table-responsive">
                    <Table striped bordered hover size="sm">
                        <thead className="table-dark">
                            <tr>
                                <th style={{ width: '50px' }}>Row</th>
                                <th>Product Name</th>
                                <th>SKU</th>
                                <th>Type</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Brand</th>
                                <th style={{ width: '120px', textAlign: 'center' }}>Is Valid?</th>
                                <th style={{ minWidth: '250px' }}>Validation Errors</th>
                            </tr>
                        </thead>
                        <tbody>
                            {products.map((product, index) => {
                                // Determine row styling based on row type and validity
                                let rowClass = '';
                                
                                if (product.row_type === 'instruction') {
                                    rowClass = 'table-info';
                                } else if (product.row_type === 'sample') {
                                    rowClass = 'table-warning';
                                } else if (product.row_type === 'empty') {
                                    rowClass = 'table-secondary';
                                } else if (!product.is_valid) {
                                    rowClass = 'table-danger';
                                }
                                
                                return (
                                    <tr key={index} className={rowClass}>
                                        <td className="text-center fw-bold">{product.row_number}</td>
                                        <td>
                                            {product.product_name || (
                                                <span className="text-muted fst-italic">Empty</span>
                                            )}
                                        </td>
                                        <td>
                                            {product.sku || (
                                                <span className="text-muted fst-italic">Empty</span>
                                            )}
                                        </td>
                                        <td className="small">{product.product_type}</td>
                                        <td>
                                            <div className="small">
                                                <div>Base: {product.base_price || '-'}</div>
                                                <div>Sale: {product.sale_price || '-'}</div>
                                            </div>
                                        </td>
                                        <td className="text-center">{product.quantity || '-'}</td>
                                        <td className="small">{product.brand || '-'}</td>
                                        
                                        {/* Is Valid Column */}
                                        <td className="text-center" style={{ verticalAlign: 'middle' }}>
                                            {product.is_valid && product.will_be_imported ? (
                                                <div>
                                                    <Badge bg="success" style={{ fontSize: '16px' }}>
                                                        ✓
                                                    </Badge>
                                                    <div className="small text-success mt-1">Valid</div>
                                                </div>
                                            ) : product.row_type === 'product' && !product.is_valid ? (
                                                <div>
                                                    <Badge bg="danger" style={{ fontSize: '16px' }}>
                                                        ✕
                                                    </Badge>
                                                    <div className="small text-danger mt-1">Invalid</div>
                                                </div>
                                            ) : (
                                                <div>
                                                    <Badge bg="secondary" style={{ fontSize: '16px' }}>
                                                        ⊘
                                                    </Badge>
                                                    <div className="small text-muted mt-1">Skip</div>
                                                </div>
                                            )}
                                        </td>
                                        
                                        {/* Validation Errors Column */}
                                        <td>
                                            {product.is_valid && product.will_be_imported ? (
                                                <div className="text-success small">
                                                    <strong>✅ Ready to import</strong>
                                                    <div className="text-muted mt-1">No errors found</div>
                                                </div>
                                            ) : product.validation_errors && product.validation_errors.length > 0 ? (
                                                <div className="small">
                                                    {product.validation_errors.map((error, idx) => (
                                                        <div 
                                                            key={idx} 
                                                            className="mb-2 p-2 rounded"
                                                            style={{
                                                                backgroundColor: product.row_type === 'product' ? '#f8d7da' : '#e2e3e5',
                                                                border: `1px solid ${product.row_type === 'product' ? '#f5c2c7' : '#d3d3d4'}`,
                                                                color: product.row_type === 'product' ? '#842029' : '#6c757d'
                                                            }}
                                                        >
                                                            <strong>{product.row_type === 'product' ? '❌' : '⚠️'}</strong> {error}
                                                        </div>
                                                    ))}
                                                </div>
                                            ) : (
                                                <span className="text-muted small fst-italic">No validation data</span>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </Table>
                </div>

                {/* Additional Product Details (Collapsible) */}
                <div className="mt-3">
                    <details>
                        <summary className="btn btn-sm btn-outline-secondary mb-2">
                            Show Full Product Details
                        </summary>
                        <div className="table-responsive">
                            <Table striped bordered size="sm">
                                <thead className="table-secondary">
                                    <tr>
                                        <th>Row</th>
                                        <th>Unit</th>
                                        <th>Tax</th>
                                        <th>Categories</th>
                                        <th>Tags</th>
                                        <th>Featured</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {products.map((product, index) => (
                                        <tr key={index}>
                                            <td>{product.row_number}</td>
                                            <td>{product.unit}</td>
                                            <td>{product.tax}</td>
                                            <td>{product.categories}</td>
                                            <td>{product.tags}</td>
                                            <td>{product.is_featured}</td>
                                            <td>
                                                <Badge 
                                                    bg={
                                                        product.product_status === 'published' ? 'success' :
                                                        product.product_status === 'draft' ? 'secondary' :
                                                        'warning'
                                                    }
                                                >
                                                    {product.product_status}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </Table>
                        </div>
                    </details>
                </div>
            </Modal.Body>

            <Modal.Footer>
                <div className="d-flex justify-content-between w-100 align-items-center">
                    <div>
                        {summary.total_rows === 0 ? (
                            <span className="text-info small">
                                ℹ️ No products to import. Add your products to the Excel file.
                            </span>
                        ) : !summary.can_import ? (
                            <span className="text-danger small">
                                ⚠️ Fix {summary.invalid_count} invalid product(s) before importing
                            </span>
                        ) : null}
                    </div>
                    <div>
                        <Button variant="secondary" onClick={onHide} disabled={importing}>
                            {summary.total_rows === 0 ? 'Close' : 'Cancel'}
                        </Button>
                        <Button 
                            variant={summary.can_import && summary.total_rows > 0 ? "success" : "secondary"}
                            onClick={handleConfirmImport}
                            disabled={!summary.can_import || importing || summary.total_rows === 0}
                            className="ms-2"
                        >
                            {importing ? (
                                <>
                                    <Spinner
                                        as="span"
                                        animation="border"
                                        size="sm"
                                        role="status"
                                        aria-hidden="true"
                                        className="me-2"
                                    />
                                    Importing...
                                </>
                            ) : summary.total_rows === 0 ? (
                                <>
                                    ⊘ No Products to Import
                                </>
                            ) : (
                                <>
                                    ✅ Confirm & Import {summary.valid_count} Product{summary.valid_count !== 1 ? 's' : ''}
                                </>
                            )}
                        </Button>
                    </div>
                </div>
            </Modal.Footer>
        </Modal>
    );
};

export default ImportPreviewModal;

