import React, { useState, useRef } from 'react';
import { FilePond, registerPlugin } from 'react-filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';

// Import FilePond styles
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';

// Register the plugins
registerPlugin(
    FilePondPluginFileValidateType,
    FilePondPluginFileValidateSize,
    FilePondPluginImagePreview
);

// AuthService base URL
const AUTH_SERVICE_BASE_URL = import.meta.env.VITE_AUTH_SERVICE_URL || 'http://localhost:8000';

const FilePondUpload = ({ 
    title, 
    name, 
    accept, 
    formData, 
    setFormData, 
    merchantCode,
    maxSize = 10 * 1024 * 1024, // 10MB default
    onUploadSuccess,
    onUploadError,
    fieldErrors = {},
    isImage = false
}) => {
    const [files, setFiles] = useState([]);
    const [uploadedFileId, setUploadedFileId] = useState(null);
    const filePondRef = useRef(null);

    // Function to convert file extensions to MIME types
    const convertExtensionsToMimeTypes = (extensions) => {
        const extensionToMime = {
            '.jpg': 'image/jpeg',
            '.jpeg': 'image/jpeg',
            '.png': 'image/png',
            '.gif': 'image/gif',
            '.pdf': 'application/pdf',
            '.doc': 'application/msword',
            '.docx': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            '.txt': 'text/plain'
        };

        return extensions
            .split(',')
            .map(ext => ext.trim())
            .map(ext => extensionToMime[ext.toLowerCase()] || ext)
            .filter(Boolean);
    };

    // Custom file type detection function
    const detectFileType = (file) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();

            reader.onloadend = () => {
                const arr = (new Uint8Array(reader.result)).subarray(0, 4);
                let header = "";
                for (let i = 0; i < arr.length; i++) {
                    header += arr[i].toString(16).padStart(2, '0');
                }


                // Check the file signature (magic numbers)
                switch (header) {
                    case "89504e47":
                        resolve("image/png");
                        break;
                    case "ffd8ffe0":
                    case "ffd8ffe1":
                    case "ffd8ffe2":
                    case "ffd8ffe3":
                    case "ffd8ffe8":
                        resolve("image/jpeg");
                        break;
                    case "47494638":
                        resolve("image/gif");
                        break;
                    case "25504446":
                        resolve("application/pdf");
                        break;
                    default:
                        // Fallback to browser MIME type
                        resolve(file.type || 'unknown');
                }
            };

            reader.onerror = () => {
                console.error('Error reading file for type detection');
                reject("Error reading file");
            };

            reader.readAsArrayBuffer(file.slice(0, 4));
        });
    };

    // Get accepted file types as MIME types
    const getAcceptedFileTypes = (accept) => {
        return convertExtensionsToMimeTypes(accept);
    };

                // Server configuration
    const serverConfig = {
                    process: {
                        url: `${AUTH_SERVICE_BASE_URL}/api/softpos/upload-merchant-file`,
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Authorization': `Bearer ${localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token') || ''}`
                        },
                        ondata: (formData) => {
                            // Add field name to form data
                            formData.append('field_name', name);
                
                return formData;
                        },
                        onload: (response) => {
                            try {
                                const data = JSON.parse(response);
                                
                                if (data.success) {
                        // Save the file ID for deletion
                        setUploadedFileId(data.data.id);
                        
                                    // Update form data with uploaded file info
                                    setFormData(prev => ({
                                        ...prev,
                                        [name]: {
                                            serverData: data.data,
                                uploaded: true,
                                fileId: data.data.id
                                        }
                                    }));
                                    
                                    if (onUploadSuccess) {
                                        onUploadSuccess(data.data);
                                    }
                                } else {
                                    throw new Error(data.message || 'Upload failed');
                                }
                            } catch (error) {
                                if (onUploadError) {
                                    onUploadError(error);
                                }
                            }
                        },
                        onerror: (response) => {
                            if (onUploadError) {
                                onUploadError(new Error('Upload failed'));
                            }
                        }
                    }
    };

    // Event handlers
    const handleInit = () => {
        // FilePond instance initialized
    };

    const handleAddFile = (error, file) => {
        if (error) {
            if (onUploadError) {
                onUploadError(new Error(`File validation failed: ${error.message || error}`));
            }
        }
    };

    const handleProcessFile = (error, file) => {
        // File processing
    };

    const handleProcessFileProgress = (file, progress) => {
        // Upload progress
    };

    // Delete file function
    const handleDeleteFile = async () => {
        if (!uploadedFileId) {
            console.error('No file ID available for deletion');
            return;
        }

        try {
            const response = await fetch(`${AUTH_SERVICE_BASE_URL}/api/softpos/delete-merchant-file/${uploadedFileId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Authorization': `Bearer ${localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token') || ''}`
                },
                body: JSON.stringify({
                    field_name: name
                })
            });

            const data = await response.json();
            
            if (data.success) {
                // Clear the file from FilePond
                setFiles([]);
                setUploadedFileId(null);
                
                // Update form data
                setFormData(prev => ({
                    ...prev,
                    [name]: {
                        serverData: null,
                        uploaded: false,
                        fileId: null
                    }
                }));
                
                // Show success message
                alert('File deleted successfully');
            } else {
                throw new Error(data.message || 'Delete failed');
            }
        } catch (error) {
            console.error('Delete error:', error);
            alert(`Failed to delete file: ${error.message}`);
        }
    };

    const hasError = fieldErrors[name];

    return (
        <div className="card p-4 fv-row">
            <label className="form-label fw-bold mb-3">
                {title} <span className="text-danger">*</span>
            </label>
            
            <div className={`filepond-container ${hasError ? 'is-invalid' : ''}`}>
                <FilePond
                    ref={filePondRef}
                    files={files}
                    onupdatefiles={setFiles}
                    server={serverConfig}
                    acceptedFileTypes={getAcceptedFileTypes(accept)}
                    fileValidateTypeDetectType={(file, type) => 
                        detectFileType(file).then((detectedType) => {
                            return detectedType;
                        }).catch(() => {
                            return type;
                        })
                    }
                    maxFileSize={maxSize}
                    allowMultiple={false}
                    allowRevert={true}
                    instantUpload={true}
                    checkValidity={true}
                    imagePreviewHeight={isImage ? 120 : undefined}
                    oninit={handleInit}
                    onaddfile={handleAddFile}
                    onprocessfile={handleProcessFile}
                    onprocessfileprogress={handleProcessFileProgress}
                    name="file"
                    id={name}
                    labelIdle={`Drag & Drop your file here or <span class="filepond--label-action">Browse</span>`}
                    labelFileProcessing="Uploading..."
                    labelFileProcessingComplete="Upload complete"
                    labelFileProcessingError="Upload failed"
                    labelButtonRemoveItem="Remove"
                    labelButtonProcessItem="Upload"
                />
            </div>

            {hasError && (
                <div className="alert alert-danger mt-3 mb-0">
                    <i className="fas fa-exclamation-triangle me-2"></i>
                    {fieldErrors[name]}
                </div>
            )}

            <small className="form-text text-muted mt-2">
                <i className="fas fa-info-circle me-1"></i>
                Files are uploaded to the server immediately for processing
            </small>
            
            {/* Delete button - only show when file is uploaded */}
            {uploadedFileId && (
                <div className="mt-3">
                    <button
                        type="button"
                        className="btn btn-danger btn-sm"
                        onClick={handleDeleteFile}
                        title="Delete uploaded file"
                    >
                        <i className="fas fa-trash me-1"></i>
                        Delete File
                    </button>
            </div>
            )}
            
        </div>
    );
};

export default FilePondUpload;
