import React, { useState, useEffect } from 'react';
import { profileAPI } from '../../services/authService';

const ProfileEdit = ({ onSuccess }) => {
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        phone: '',
        mobile: '',
        gender: '',
    });
    
    const [profileImage, setProfileImage] = useState(null);
    const [previewImage, setPreviewImage] = useState(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    useEffect(() => {
        fetchUserInfo();
    }, []);

    const fetchUserInfo = async () => {
        try {
            setLoading(true);
            const response = await profileAPI.getUserInfo();
            
            if (response.status && response.data) {
                const user = response.data.user;
                setFormData({
                    name: user.name || '',
                    email: user.email || '',
                    phone: user.phone || '',
                    mobile: user.mobile || '',
                    gender: user.gender || '',
                });
                setPreviewImage(user.profile_image);
            }
        } catch (err) {
            console.error('Error fetching user info:', err);
            setError('Failed to load user information');
        } finally {
            setLoading(false);
        }
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setProfileImage(file);
            // Create preview
            const reader = new FileReader();
            reader.onloadend = () => {
                setPreviewImage(reader.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        try {
            setSubmitting(true);
            setError(null);
            setSuccess(null);

            const dataToSend = { ...formData };
            if (profileImage) {
                dataToSend.profile_image = profileImage;
            }

            const response = await profileAPI.updateProfile(dataToSend);
            
            if (response.status) {
                setSuccess('Profile updated successfully!');
                
                // Call onSuccess callback if provided
                if (onSuccess) {
                    onSuccess(response.data.user);
                }

                // Reload page after 2 seconds to reflect changes
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        } catch (err) {
            console.error('Error updating profile:', err);
            setError(err.message || 'Failed to update profile');
        } finally {
            setSubmitting(false);
        }
    };

    const handleDeleteImage = async () => {
        if (!confirm('Are you sure you want to delete your profile image?')) {
            return;
        }

        try {
            setSubmitting(true);
            const response = await profileAPI.deleteProfileImage();
            
            if (response.status) {
                setSuccess('Profile image deleted successfully!');
                setPreviewImage(null);
                setProfileImage(null);
                
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        } catch (err) {
            console.error('Error deleting profile image:', err);
            setError(err.message || 'Failed to delete profile image');
        } finally {
            setSubmitting(false);
        }
    };

    if (loading) {
        return (
            <div className="card">
                <div className="card-body">
                    <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '200px' }}>
                        <div className="spinner-border text-primary" role="status">
                            <span className="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="card">
            <div className="card-header">
                <h5 className="card-title mb-0">
                    <i className="fas fa-edit me-2"></i>
                    Edit Profile
                </h5>
            </div>
            <div className="card-body">
                {error && (
                    <div className="alert alert-danger alert-dismissible fade show" role="alert">
                        <i className="fas fa-exclamation-circle me-2"></i>
                        {error}
                        <button type="button" className="btn-close" onClick={() => setError(null)}></button>
                    </div>
                )}

                {success && (
                    <div className="alert alert-success alert-dismissible fade show" role="alert">
                        <i className="fas fa-check-circle me-2"></i>
                        {success}
                        <button type="button" className="btn-close" onClick={() => setSuccess(null)}></button>
                    </div>
                )}

                <form onSubmit={handleSubmit}>
                    {/* Profile Image */}
                    <div className="mb-4 text-center">
                        <div className="mb-3">
                            <img 
                                src={previewImage || '/assets/media/avatars/300-1.jpg'} 
                                alt="Profile Preview" 
                                className="rounded-circle"
                                style={{ width: '150px', height: '150px', objectFit: 'cover' }}
                            />
                        </div>
                        <div className="d-flex justify-content-center gap-2">
                            <label className="btn btn-sm btn-primary">
                                <i className="fas fa-upload me-2"></i>
                                Upload New Image
                                <input 
                                    type="file" 
                                    accept="image/*" 
                                    onChange={handleImageChange}
                                    style={{ display: 'none' }}
                                />
                            </label>
                            {previewImage && (
                                <button 
                                    type="button" 
                                    className="btn btn-sm btn-danger"
                                    onClick={handleDeleteImage}
                                    disabled={submitting}
                                >
                                    <i className="fas fa-trash me-2"></i>
                                    Delete Image
                                </button>
                            )}
                        </div>
                    </div>

                    <div className="row g-3">
                        {/* Name */}
                        <div className="col-md-6">
                            <label htmlFor="name" className="form-label">
                                Name <span className="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                className="form-control"
                                id="name"
                                name="name"
                                value={formData.name}
                                onChange={handleChange}
                                required
                            />
                        </div>

                        {/* Email */}
                        <div className="col-md-6">
                            <label htmlFor="email" className="form-label">
                                Email <span className="text-danger">*</span>
                            </label>
                            <input
                                type="email"
                                className="form-control"
                                id="email"
                                name="email"
                                value={formData.email}
                                onChange={handleChange}
                                required
                            />
                        </div>

                        {/* Phone */}
                        <div className="col-md-6">
                            <label htmlFor="phone" className="form-label">Phone</label>
                            <input
                                type="text"
                                className="form-control"
                                id="phone"
                                name="phone"
                                value={formData.phone}
                                onChange={handleChange}
                            />
                        </div>

                        {/* Mobile */}
                        <div className="col-md-6">
                            <label htmlFor="mobile" className="form-label">Mobile</label>
                            <input
                                type="text"
                                className="form-control"
                                id="mobile"
                                name="mobile"
                                value={formData.mobile}
                                onChange={handleChange}
                            />
                        </div>

                        {/* Gender */}
                        <div className="col-md-6">
                            <label htmlFor="gender" className="form-label">Gender</label>
                            <select
                                className="form-select"
                                id="gender"
                                name="gender"
                                value={formData.gender}
                                onChange={handleChange}
                            >
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    {/* Submit Button */}
                    <div className="mt-4">
                        <button 
                            type="submit" 
                            className="btn btn-primary"
                            disabled={submitting}
                        >
                            {submitting ? (
                                <>
                                    <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Updating...
                                </>
                            ) : (
                                <>
                                    <i className="fas fa-save me-2"></i>
                                    Update Profile
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default ProfileEdit;

