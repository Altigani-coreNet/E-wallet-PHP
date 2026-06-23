import React, { useRef, useEffect } from 'react';
import { create, registerPlugin } from 'filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';

// Import FilePond styles
import 'filepond/dist/filepond.min.css';

// Register the plugins
registerPlugin(
    FilePondPluginFileValidateType,
    FilePondPluginFileValidateSize
);

const FilePondTest = () => {
    const filePondRef = useRef(null);

    useEffect(() => {
        console.log('FilePondTest: useEffect triggered');
        if (filePondRef.current) {
            console.log('FilePondTest: Creating FilePond instance');
            
            const pond = create(filePondRef.current, {
                // Simple server configuration for testing
                server: {
                    process: {
                        url: '/api/softpos/test-upload',
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Authorization': `Bearer ${localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token') || ''}`
                        },
                        ondata: (formData) => {
                            formData.append('field_name', 'test_upload');
                            console.log('FilePondTest: Sending form data');
                            console.log('FilePondTest: Form data contents:', Array.from(formData.entries()));
                        },
                        onload: (response) => {
                            console.log('FilePondTest: Upload successful:', response);
                        },
                        onerror: (response) => {
                            console.log('FilePondTest: Upload error:', response);
                        }
                    }
                },
                
                // Basic configuration
                acceptedFileTypes: ['.jpg', '.jpeg', '.png', '.pdf'],
                maxFileSize: '10MB',
                allowMultiple: false,
                instantUpload: true,
                
                // Simple labels
                labelIdle: 'Drop files here or click to browse',
                labelFileProcessing: 'Uploading...',
                labelFileProcessingComplete: 'Upload complete',
                labelFileProcessingError: 'Upload failed'
            });

            // Add event listeners
            pond.on('addfile', (error, file) => {
                console.log('FilePondTest: File added', file);
            });
            
            pond.on('processfile', (error, file) => {
                console.log('FilePondTest: Processing file', file);
            });

            return () => {
                console.log('FilePondTest: Cleaning up');
                pond.destroy();
            };
        }
    }, []);

    return (
        <div className="card p-4">
            <h5>FilePond Test Component</h5>
            <p>This is a simple test to see if FilePond works at all.</p>
            
            <div className="filepond-container">
                <input
                    ref={filePondRef}
                    type="file"
                    className="filepond"
                />
            </div>
            
            <div className="mt-3 p-2 bg-light rounded">
                <small className="text-muted">
                    Auth Token: {localStorage.getItem('auth_token') ? 'Present' : 'Missing'}<br/>
                    CSRF Token: {document.querySelector('meta[name="csrf-token"]')?.content ? 'Present' : 'Missing'}
                </small>
            </div>
        </div>
    );
};

export default FilePondTest;
