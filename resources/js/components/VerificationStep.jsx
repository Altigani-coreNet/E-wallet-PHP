import React from 'react';
import VerificationInput from './VerificationInput';

const VerificationStep = ({ phone, onComplete }) => {
    const handleVerificationComplete = (code) => {
        // Here you would typically make an API call to verify the code
        onComplete(code);
    };

    return (
        <div className="current" data-kt-stepper-element="content">
            <div>
                <div className="text-center mb-10">
                    <span className="flex justify-center items-center">
                        <img 
                            alt="Logo" 
                            className="mh-125px" 
                            src="assets/media/svg/misc/smartphone.svg"
                        />
                    </span>
                </div>
                
                <div className="text-center mb-10">
                    <div className="text-muted fw-bold fs-5 mb-5">
                        Enter the verification code we sent to
                    </div>
                    <div className="fw-bolder text-dark fs-3">
                        {phone}
                    </div>
                </div>
                
                <div className="mb-xl-10 px-xl-10 my-2">
                    <input type="hidden" name="code" id="code" />
                    <div className="fw-bolder text-start text-dark fs-6 mb-1 ms-1">
                        Type your 6 digit security code
                    </div>
                    
                    <VerificationInput 
                        length={6} 
                        onComplete={handleVerificationComplete} 
                    />
                </div>
            </div>
        </div>
    );
};

export default VerificationStep;
