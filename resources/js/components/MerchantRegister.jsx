import React, { useState } from 'react';
import { useFormik } from 'formik';
import * as Yup from 'yup';
import classNames from 'classnames';
import Swal from 'sweetalert2';

// Step Components
import AccountDetails from './steps/AccountDetails';
import AccountVerification from './steps/AccountVerification';
import CompanyProfile from './steps/CompanyProfile';
import BusinessDocuments from './steps/BusinessDocuments';
import CompletionStep from './steps/CompletionStep';

// AuthService base URL
const AUTH_SERVICE_BASE_URL = import.meta.env.VITE_AUTH_SERVICE_URL || 'http://localhost:8000';

const steps = [
    {
        title: 'Account Details',
        description: 'Setup Your Account Details'
    },
    {
        title: 'Account Verification',
        description: 'Verify Your Account & Set Your Passport'
    },
    {
        title: 'Merchant Profile',
        description: 'Setup Your Merchant Profile Details'
    },
    {
        title: 'Business Documents',
        description: 'Attach Documents & Complete Registration'
    },
    {
        title: 'Completed',
        description: 'Woah, we are here'
    }
];

const MerchantRegister = () => {
    const [currentStep, setCurrentStep] = useState(0); // Start at step 1 (Account Details)
    const [fieldErrors, setFieldErrors] = useState({});
    const [isLoading, setIsLoading] = useState(false);
    const [formData, setFormData] = useState({
        // Account Details
        email: '',
        first_name: '',
        last_name: '',
        phone: '',
        
        // Company Profile Details
        owner_name: '',
        business_name: '',
        business_type: '',
        business_phone: '',
        business_address: '',
        country: '',
        city: '',
        
        // Trade License Details
        trade_license_number: '',
        trade_license_start_date: '',
        trade_license_expired_date: '',
      
        
        // Tax Details
        tax_number: '',
       
        
        // Document uploads
        company_logo: null,
        trade_license: null,
        tax_certification: null,
        user_id_document: null,
        
        // Terms and Conditions
        accept_terms: false
    });

    const handleNext = async () => {
        if (currentStep === 0) {
            // Clear previous error
            setFieldErrors({});

            try {
                const response = await fetch(`${AUTH_SERVICE_BASE_URL}/api/softpos/register/validate-details`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        email: formData.email,
                        first_name: formData.first_name,
                        last_name: formData.last_name,
                        phone: formData.phone,
                        type: 'email'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    setCurrentStep(currentStep + 1);
                } else {
                    if (response.status === 422 && data.errors) {
                        setFieldErrors(data.errors);
                        const errorMessages = Object.entries(data.errors).map(([field, errors]) => {
                            return `${field.charAt(0).toUpperCase() + field.slice(1)}: ${errors[0]}`;
                        });
                        
                        await Swal.fire({
                            icon: 'error',
                            title: 'Please Fix the Following Errors',
                            html: errorMessages.join('<br>'),
                            confirmButtonText: 'OK'
                        });
                    } else {
                        await Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to send verification code. Please try again.',
                        });
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                await Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred. Please try again.',
                });
            }
        } else if (currentStep === 2) {
            // Company Profile step - Send merchant details before going to Business Documents
            
            // Client-side validation for required fields including accept_terms
            const validationErrors = {};
            
            // Check required fields
            const requiredFields = {
                owner_name: 'Owner Name',
                business_name: 'Business Name',
                business_type: 'Business Type',
                business_phone: 'Business Phone',
                business_address: 'Business Address',
                country: 'Country',
                city: 'City',
                trade_license_number: 'Trade License Number',
                trade_license_start_date: 'Trade License Start Date',
                trade_license_expired_date: 'Trade License Expired Date',
                tax_number: 'Tax Number'
            };
            
            // Validate required fields
            Object.entries(requiredFields).forEach(([field, label]) => {
                if (!formData[field] || formData[field].toString().trim() === '') {
                    validationErrors[field] = [`${label} is required.`];
                }
            });
            
            // Validate accept_terms specifically
            if (!formData.accept_terms) {
                validationErrors.accept_terms = ['You must accept the Terms and Conditions to continue.'];
            }
            
            // If there are validation errors, show them and don't proceed
            if (Object.keys(validationErrors).length > 0) {
                setFieldErrors(validationErrors);
                
                const errorMessages = Object.entries(validationErrors).map(([field, errors]) => {
                    return `${field.charAt(0).toUpperCase() + field.slice(1).replace('_', ' ')}: ${errors[0]}`;
                });
                
                await Swal.fire({
                    icon: 'error',
                    title: 'Please Fix the Following Errors',
                    html: errorMessages.join('<br>'),
                    confirmButtonText: 'OK'
                });
                return; // Don't proceed to API call
            }
            
            try {
                console.log('=== MERCHANT REGISTRATION DEBUG ===');
                console.log('Full formData:', formData);
                console.log('City value:', formData.city);
                console.log('Country value:', formData.country);
                console.log('Business type value:', formData.business_type);
                console.log('Business name:', formData.business_name);
                console.log('Owner name:', formData.owner_name);
                console.log('Accept terms:', formData.accept_terms);
                console.log('=====================================');
                
                // Get bearer token from localStorage or sessionStorage
                const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
                
                const response = await fetch(`${AUTH_SERVICE_BASE_URL}/api/softpos/register/merchant`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Authorization': token ? `Bearer ${token}` : ''
                    },
                    body: JSON.stringify({
                        // Account Details
                        email: formData.email,
                        first_name: formData.first_name,
                        last_name: formData.last_name,
                        phone: formData.phone,
                        nationality: formData.nationality,
                        
                        // Company Profile Details
                        owner_name: formData.owner_name,
                        business_name: formData.business_name,
                        business_type: formData.business_type,
                        business_phone: formData.business_phone,
                        business_address: formData.business_address,
                        country: formData.country,
                        city: formData.city,
                        
                        // Trade License Details
                        trade_license_number: formData.trade_license_number,
                        trade_license_start_date: formData.trade_license_start_date,
                        trade_license_expired_date: formData.trade_license_expired_date,
                        trade_license_authority: formData.trade_license_authority,
                        
                        // Tax Details
                        tax_number: formData.tax_number,
                        tax_certified_number: formData.tax_certified_number,
                        tax_id_number: formData.tax_id_number,
                        vat_number: formData.vat_number,
                        tax_registration_date: formData.tax_registration_date,
                        tax_authority: formData.tax_authority,
                        annual_turnover: formData.annual_turnover,
                        
                        // Terms and Conditions
                        accept_terms: formData.accept_terms
                    })
                });

                const data = await response.json();
                console.log('Merchant registration response:', data); // Debug log

                if (data.success || data.status) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Merchant details saved successfully!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setCurrentStep(currentStep + 1);
                } else {
                    if (response.status === 422 && data.errors) {
                        setFieldErrors(data.errors);
                        const errorMessages = Object.entries(data.errors).map(([field, errors]) => {
                            return `${field.charAt(0).toUpperCase() + field.slice(1)}: ${errors[0]}`;
                        });
                        
                        await Swal.fire({
                            icon: 'error',
                            title: 'Please Fix the Following Errors',
                            html: errorMessages.join('<br>'),
                            confirmButtonText: 'OK'
                        });
                    } else {
                        await Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to save merchant details. Please try again.',
                        });
                    }
                }
            } catch (error) {
                console.error('Error saving merchant details:', error);
                await Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while saving merchant details. Please try again.',
                });
            }
        } else if (currentStep === 3) {
            // Business Documents step - Send continuation email before going to Completion
            setIsLoading(true);
            try {
                // Get bearer token from localStorage or sessionStorage
                const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
                
                const response = await fetch(`${AUTH_SERVICE_BASE_URL}/api/softpos/register/merchant/send-continuation-email`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Authorization': token ? `Bearer ${token}` : ''
                    }
                });

                const data = await response.json();

                // Always proceed to next step regardless of email success/failure
                setCurrentStep(currentStep + 1);
            } catch (error) {
                console.error('Error sending continuation email:', error);
                // Still proceed to next step even if email fails
                setCurrentStep(currentStep + 1);
            } finally {
                setIsLoading(false);
            }
        } else if (currentStep < steps.length - 1) {
            setCurrentStep(currentStep + 1);
        }
    };

    const handlePrevious = () => {
        if (currentStep > 0) {
            setCurrentStep(currentStep - 1);
        }
    };

    const handleFieldChange = (name, value) => {
        console.log(`=== FORM FIELD CHANGE DEBUG ===`);
        console.log(`Field: ${name}, Value: ${value}`);
        console.log(`Current formData:`, formData);
        console.log(`================================`);
        
        setFormData(prev => {
            const newFormData = {
                ...prev,
                [name]: value
            };
            console.log(`Updated formData:`, newFormData);
            return newFormData;
        });
        
        // Clear error for this field when user starts typing
        if (fieldErrors[name]) {
            setFieldErrors(prev => ({
                ...prev,
                [name]: undefined
            }));
        }
    };

    const renderStepContent = () => {
        // Debug current form state
        console.log('=== CURRENT FORM STATE ===');
        console.log('Current step:', currentStep);
        console.log('Form data:', formData);
        console.log('Field errors:', fieldErrors);
        console.log('=========================');
        
        const commonProps = {
            formData,
            setFormData: handleFieldChange,
            fieldErrors
        };

        switch (currentStep) {
            case 0:
                return <AccountDetails {...commonProps} />;
            case 1:
                return <AccountVerification {...commonProps} onNextStep={() => setCurrentStep(currentStep + 1)} />;
            case 2:
                return <CompanyProfile {...commonProps} />;
            case 3:
                return <BusinessDocuments {...commonProps} />;
            case 4:
                return <CompletionStep />;
            default:
                return null;
        }
    };

    return (
        <div className="d-flex flex-column flex-root min-vh-90">
            <div className="d-flex flex-column flex-lg-row flex-column-fluid stepper stepper-pills stepper-column min-vh-100"
                id="kt_create_account_stepper">
                {/* Aside */}
                <div className="d-flex flex-column flex-lg-row-auto w-xl-500px bg-lighten shadow-sm">
                    <div className="d-flex flex-column position-xl-fixed top-0 bottom-0 w-xl-500px scroll-y">
                        <div className="d-flex flex-row-fluid flex-column flex-center p-10 pt-lg-20">
                            <div className="stepper-nav">
                                {steps.map((step, index) => (
                                    <div
                                        key={index}
                                        className={classNames('stepper-item', {
                                            'current': currentStep === index,
                                            'completed': currentStep > index,
                                            'pending': currentStep < index
                                        })}
                                    >
                                        <div className="stepper-line w-40px"></div>
                                        <div className="stepper-icon w-40px h-40px">
                                            <i className="stepper-check fas fa-check"></i>
                                            <span className="stepper-number">{index + 1}</span>
                                        </div>
                                        <div className="stepper-label">
                                            <h3 className="stepper-title">{step.title}</h3>
                                            <div className="stepper-desc fw-bold">{step.description}</div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                        <div className="d-flex flex-row-auto bgi-no-repeat bgi-position-x-center bgi-size-contain bgi-position-y-bottom min-h-150px min-h-lg-300px"
                            style={{ backgroundImage: "url(/assets/media/illustrations/sketchy-1/16.png)" }}></div>
                    </div>
                </div>

                {/* Main Content */}
                <div className="d-flex flex-column flex-lg-row-fluid py-10 min-vh-100">
                    <div className="d-flex flex-center flex-column flex-column-fluid">
                        <div className="w-lg-700px p-10 p-lg-15 mx-auto">
                            <form className="my-auto pb-5" noValidate>
                                {renderStepContent()}

                                {currentStep < steps.length - 1 && (
                                    <div className="d-flex flex-stack pt-15">
                                        <div className="mr-2">
                                            {currentStep > 0 && (
                                                <button
                                                    type="button"
                                                    className="btn btn-lg btn-light-primary me-3"
                                                    disabled={currentStep > 0}
                                                    onClick={handlePrevious}
                                                >
                                                    <span className="svg-icon svg-icon-4 me-1">
                                                        <i className="fas fa-arrow-left"></i>
                                                    </span>
                                                    Previous
                                                </button>
                                            )}
                                        </div>
                                        <div>
                                            <button
                                                type="button"
                                                className="btn btn-lg btn-primary"
                                                disabled={currentStep === 1 || isLoading}
                                                onClick={handleNext}
                                            >
                                                {isLoading ? (
                                                    <>
                                                        <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                                        {currentStep === 3 ? 'Loading ...' : 'Loading...'}
                                                    </>
                                                ) : (
                                                    <>
                                                        Next
                                                        <span className="svg-icon svg-icon-4 ms-1">
                                                            <i className="fas fa-arrow-right"></i>
                                                        </span>
                                                    </>
                                                )}
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default MerchantRegister;