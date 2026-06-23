import React, { useState } from 'react';
import { uploadFile } from '../../../utils/api';
import { API_ENDPOINTS } from '../../../utils/constants';

export default function ImageUploader({ 
    type = 'thumbnail', // 'thumbnail' or 'gallery'
    multiple = false,
    onUploadSuccess,
    existingImages = [],
    label = 'Upload Image'
}) {
    const [uploading, setUploading] = useState(false);
    const [images, setImages] = useState(existingImages);
    const [previewImages, setPreviewImages] = useState(existingImages.map(img => img.url || img));

    const handleFileChange = async (event) => {
        const files = event.target.files;
        if (!files || files.length === 0) return;

        setUploading(true);

        try {
            const uploadPromises = Array.from(files).map(async (file) => {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('type', type);

                const response = await uploadFile(API_ENDPOINTS.PRODUCTS.UPLOAD, formData);
                
                if (response.data.success !== false) {
                    return response.data.data;
                }
                return null;
            });

            const results = await Promise.all(uploadPromises);
            const successfulUploads = results.filter(r => r !== null);

            if (multiple) {
                const newImages = [...images, ...successfulUploads];
                const newPreviews = [...previewImages, ...successfulUploads.map(u => u.url)];
                setImages(newImages);
                setPreviewImages(newPreviews);
                onUploadSuccess && onUploadSuccess(newImages);
            } else {
                if (successfulUploads.length > 0) {
                    setImages([successfulUploads[0]]);
                    setPreviewImages([successfulUploads[0].url]);
                    onUploadSuccess && onUploadSuccess(successfulUploads[0]);
                }
            }

        } catch (error) {
            console.error('Upload error:', error);
            alert('Failed to upload image(s)');
        } finally {
            setUploading(false);
        }
    };

    const removeImage = (index) => {
        const newImages = images.filter((_, i) => i !== index);
        const newPreviews = previewImages.filter((_, i) => i !== index);
        setImages(newImages);
        setPreviewImages(newPreviews);
        
        if (multiple) {
            onUploadSuccess && onUploadSuccess(newImages);
        } else {
            onUploadSuccess && onUploadSuccess(null);
        }
    };

    return (
        <div className="image-uploader">
            <label className="form-label">{label}</label>
            
            <div className="image-input-wrapper mb-3">
                {/* Preview */}
                {previewImages.length > 0 && (
                    <div className={`d-flex flex-wrap gap-3 mb-3 ${multiple ? '' : 'justify-content-center'}`}>
                        {previewImages.map((preview, index) => (
                            <div key={index} className="position-relative" style={{ width: multiple ? '100px' : '150px', height: multiple ? '100px' : '150px' }}>
                                <img 
                                    src={preview} 
                                    alt={`Preview ${index + 1}`} 
                                    className="w-100 h-100 rounded"
                                    style={{ objectFit: 'cover' }}
                                />
                                <button
                                    type="button"
                                    className="btn btn-icon btn-sm btn-circle btn-active-color-primary position-absolute top-0 end-0 bg-white shadow"
                                    style={{ marginTop: '-5px', marginRight: '-5px' }}
                                    onClick={() => removeImage(index)}
                                >
                                    <i className="ki-duotone ki-cross fs-2">
                                        <span className="path1"></span>
                                        <span className="path2"></span>
                                    </i>
                                </button>
                            </div>
                        ))}
                    </div>
                )}

                {/* Upload Button */}
                {(multiple || previewImages.length === 0) && (
                    <div className="text-center p-5 border border-dashed border-gray-300 rounded cursor-pointer hover:bg-gray-50"
                         onClick={() => document.getElementById(`file-input-${type}`).click()}>
                        <input
                            id={`file-input-${type}`}
                            type="file"
                            accept="image/*"
                            multiple={multiple}
                            onChange={handleFileChange}
                            className="d-none"
                            disabled={uploading}
                        />
                        
                        {uploading ? (
                            <div className="d-flex flex-column align-items-center">
                                <div className="spinner-border text-primary mb-2" role="status">
                                    <span className="visually-hidden">Uploading...</span>
                                </div>
                                <span className="text-muted">Uploading...</span>
                            </div>
                        ) : (
                            <div className="d-flex flex-column align-items-center">
                                <i className="ki-duotone ki-file-up text-primary fs-3x mb-3">
                                    <span className="path1"></span>
                                    <span className="path2"></span>
                                </i>
                                <h3 className="fs-5 fw-bold text-gray-900 mb-1">
                                    {multiple ? 'Drop files here or click to upload' : 'Upload Image'}
                                </h3>
                                <span className="fs-7 fw-semibold text-gray-500">
                                    {multiple ? 'Upload up to 10 files' : 'Max file size: 5MB'}
                                </span>
                            </div>
                        )}
                    </div>
                )}
            </div>
            
            <div className="text-muted fs-7">
                Supported formats: JPG, PNG, GIF. Max file size: 5MB
            </div>
        </div>
    );
}

