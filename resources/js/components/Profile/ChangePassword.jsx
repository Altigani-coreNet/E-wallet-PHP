import React, { useState } from 'react';
import { profileAPI } from '../../services/authService';

const ChangePassword = () => {
    const [formData, setFormData] = useState({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    const [showPasswords, setShowPasswords] = useState({
        current: false,
        new: false,
        confirm: false,
    });

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
        
        // Clear errors when user starts typing
        if (error) setError(null);
    };

    const togglePasswordVisibility = (field) => {
        setShowPasswords(prev => ({
            ...prev,
            [field]: !prev[field]
        }));
    };

    const validateForm = () => {
        if (formData.password.length < 8) {
            setError('New password must be at least 8 characters long');
            return false;
        }

        if (formData.password !== formData.password_confirmation) {
            setError('New password and confirmation do not match');
            return false;
        }

        if (formData.current_password === formData.password) {
            setError('New password must be different from current password');
            return false;
        }

        return true;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }

        try {
            setSubmitting(true);
            setError(null);
            setSuccess(null);

            const response = await profileAPI.changePassword(formData);
            
            if (response.status) {
                setSuccess('Password changed successfully!');
                
                // Reset form
                setFormData({
                    current_password: '',
                    password: '',
                    password_confirmation: '',
                });

                // Optional: Redirect to login after password change
                setTimeout(() => {
                    // window.location.href = '/login';
                }, 2000);
            }
        } catch (err) {
            console.error('Error changing password:', err);
            setError(err.message || 'Failed to change password');
        } finally {
            setSubmitting(false);
        }
    };

    const getPasswordStrength = (password) => {
        if (!password) return { strength: 0, label: '', color: 'secondary' };
        
        let strength = 0;
        
        // Length check
        if (password.length >= 8) strength += 25;
        if (password.length >= 12) strength += 25;
        
        // Complexity checks
        if (/[a-z]/.test(password)) strength += 12.5;
        if (/[A-Z]/.test(password)) strength += 12.5;
        if (/[0-9]/.test(password)) strength += 12.5;
        if (/[^a-zA-Z0-9]/.test(password)) strength += 12.5;

        let label = 'Weak';
        let color = 'danger';
        
        if (strength >= 75) {
            label = 'Strong';
            color = 'success';
        } else if (strength >= 50) {
            label = 'Medium';
            color = 'warning';
        }

        return { strength, label, color };
    };

    const passwordStrength = getPasswordStrength(formData.password);

    return (
        <div className="card">
            <div className="card-header">
                <h5 className="card-title mb-0">
                    <i className="fas fa-lock me-2"></i>
                    Change Password
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
                    {/* Current Password */}
                    <div className="mb-3">
                        <label htmlFor="current_password" className="form-label">
                            Current Password <span className="text-danger">*</span>
                        </label>
                        <div className="input-group">
                            <input
                                type={showPasswords.current ? "text" : "password"}
                                className="form-control"
                                id="current_password"
                                name="current_password"
                                value={formData.current_password}
                                onChange={handleChange}
                                required
                            />
                            <button 
                                className="btn btn-outline-secondary" 
                                type="button"
                                onClick={() => togglePasswordVisibility('current')}
                            >
                                <i className={`fas fa-eye${showPasswords.current ? '-slash' : ''}`}></i>
                            </button>
                        </div>
                    </div>

                    {/* New Password */}
                    <div className="mb-3">
                        <label htmlFor="password" className="form-label">
                            New Password <span className="text-danger">*</span>
                        </label>
                        <div className="input-group">
                            <input
                                type={showPasswords.new ? "text" : "password"}
                                className="form-control"
                                id="password"
                                name="password"
                                value={formData.password}
                                onChange={handleChange}
                                minLength={8}
                                required
                            />
                            <button 
                                className="btn btn-outline-secondary" 
                                type="button"
                                onClick={() => togglePasswordVisibility('new')}
                            >
                                <i className={`fas fa-eye${showPasswords.new ? '-slash' : ''}`}></i>
                            </button>
                        </div>
                        
                        {/* Password Strength Indicator */}
                        {formData.password && (
                            <div className="mt-2">
                                <div className="d-flex justify-content-between mb-1">
                                    <small className="text-muted">Password Strength:</small>
                                    <small className={`text-${passwordStrength.color}`}>
                                        {passwordStrength.label}
                                    </small>
                                </div>
                                <div className="progress" style={{ height: '5px' }}>
                                    <div 
                                        className={`progress-bar bg-${passwordStrength.color}`}
                                        role="progressbar" 
                                        style={{ width: `${passwordStrength.strength}%` }}
                                    ></div>
                                </div>
                                <small className="text-muted">
                                    Minimum 8 characters. Use uppercase, lowercase, numbers, and symbols for a stronger password.
                                </small>
                            </div>
                        )}
                    </div>

                    {/* Confirm New Password */}
                    <div className="mb-3">
                        <label htmlFor="password_confirmation" className="form-label">
                            Confirm New Password <span className="text-danger">*</span>
                        </label>
                        <div className="input-group">
                            <input
                                type={showPasswords.confirm ? "text" : "password"}
                                className="form-control"
                                id="password_confirmation"
                                name="password_confirmation"
                                value={formData.password_confirmation}
                                onChange={handleChange}
                                minLength={8}
                                required
                            />
                            <button 
                                className="btn btn-outline-secondary" 
                                type="button"
                                onClick={() => togglePasswordVisibility('confirm')}
                            >
                                <i className={`fas fa-eye${showPasswords.confirm ? '-slash' : ''}`}></i>
                            </button>
                        </div>
                        
                        {/* Password Match Indicator */}
                        {formData.password_confirmation && (
                            <small className={formData.password === formData.password_confirmation ? 'text-success' : 'text-danger'}>
                                <i className={`fas fa-${formData.password === formData.password_confirmation ? 'check' : 'times'} me-1`}></i>
                                {formData.password === formData.password_confirmation ? 'Passwords match' : 'Passwords do not match'}
                            </small>
                        )}
                    </div>

                    {/* Security Tips */}
                    <div className="alert alert-info">
                        <strong><i className="fas fa-info-circle me-2"></i>Password Security Tips:</strong>
                        <ul className="mb-0 mt-2">
                            <li>Use at least 8 characters (12+ recommended)</li>
                            <li>Mix uppercase and lowercase letters</li>
                            <li>Include numbers and special characters</li>
                            <li>Avoid common words or personal information</li>
                            <li>Don't reuse passwords from other accounts</li>
                        </ul>
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
                                    Changing Password...
                                </>
                            ) : (
                                <>
                                    <i className="fas fa-key me-2"></i>
                                    Change Password
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default ChangePassword;

