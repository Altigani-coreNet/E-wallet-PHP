import React, { useRef, useState, useEffect } from 'react';
import VerificationInput from './VerificationInput';

const ForgotPasswordCard = () => {
    const [step, setStep] = useState('request'); // request -> verify -> reset -> done
    const [email, setEmail] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [verifying, setVerifying] = useState(false);
    const [resetting, setResetting] = useState(false);
    const [message, setMessage] = useState(null);
    const [error, setError] = useState(null);
    const [token, setToken] = useState('');
    const [code, setCode] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);
    const [passwordValidation, setPasswordValidation] = useState({
        length: false,
        uppercase: false,
        lowercase: false,
        number: false,
        match: false,
    });
    const [resendTimer, setResendTimer] = useState(0);
    const [isResendDisabled, setIsResendDisabled] = useState(false);
    const [isResending, setIsResending] = useState(false);
    const verificationInputRef = useRef(null);

    const csrf = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const handleRequestReset = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        setMessage(null);
        setError(null);

        try {
            const response = await fetch('/api/softpos/password/request-reset', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({ email }),
            });

            const data = await response.json().catch(() => ({}));
            if (response.ok && data.success) {
                setToken(data.token);
                setStep('verify');
                setMessage('If the email exists, a reset code has been sent.');
                startResendTimer();
                resetVerificationInputs();
            } else {
                setError(data.message || 'Unable to start password reset.');
            }
        } catch (err) {
            setError('Network error. Please try again.');
        } finally {
            setSubmitting(false);
        }
    };

    const handleVerifyCode = async (value) => {
        // value is the freshly completed code from OTP inputs; fall back to state
        const codeToSubmit = typeof value === 'string' && value.length ? value : code;
        setVerifying(true); 
        setError(null);
        setMessage(null);
        try {
            const response = await fetch('/api/softpos/register/verify-code', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({ token, code: codeToSubmit, type: 'email' }),
            });
            const data = await response.json().catch(() => ({}));
            if (response.ok && data.success) {
                setStep('reset');
                setMessage('Verification successful. Please enter your new password.');
            } else {
                setError(data.message || 'Invalid or expired code.');
            }
        } catch (err) {
            setError('Network error while verifying.');
        } finally {
            setVerifying(false);
        }
    };

    const handleResetPassword = async (e) => {
        e.preventDefault();
        // client-side guard
        if (!isPasswordValid()) {
            setError('Please meet all password requirements before proceeding.');
            return;
        }
        setResetting(true);
        setError(null);
        setMessage(null);
        try {
            const response = await fetch('/api/softpos/password/reset', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({ token, password, password_confirmation: passwordConfirmation }),
            });
            const data = await response.json().catch(() => ({}));
            if (response.ok && data.success) {
                setStep('done');
                setMessage('Password reset successful. You can now log in.');
            } else {
                const errors = data.errors ? Object.values(data.errors).flat().join('\n') : '';
                setError(errors || data.message || 'Failed to reset password.');
            }
        } catch (err) {
            setError('Network error while resetting password.');
        } finally {
            setResetting(false);
        }
    };

    const validatePassword = (pwd, confirmation = passwordConfirmation) => {
        setPasswordValidation({
            length: pwd.length >= 8,
            uppercase: /[A-Z]/.test(pwd),
            lowercase: /[a-z]/.test(pwd),
            number: /[0-9]/.test(pwd),
            match: pwd === confirmation && pwd !== ''
        });
    };

    const isPasswordValid = () => {
        return Object.values(passwordValidation).every((v) => v === true);
    };

    const handlePasswordChange = (e) => {
        const { name, value } = e.target;
        if (name === 'password') {
            setPassword(value);
            validatePassword(value, passwordConfirmation);
        } else if (name === 'password_confirmation') {
            setPasswordConfirmation(value);
            validatePassword(password, value);
        }
    };

    const startResendTimer = () => {
        setResendTimer(60);
        setIsResendDisabled(true);
    };

    useEffect(() => {
        let interval = null;
        if (resendTimer > 0) {
            interval = setInterval(() => {
                setResendTimer((t) => {
                    if (t <= 1) {
                        setIsResendDisabled(false);
                        return 0;
                    }
                    return t - 1;
                });
            }, 1000);
        }
        return () => interval && clearInterval(interval);
    }, [resendTimer]);

    const resetVerificationInputs = () => {
        if (verificationInputRef.current && verificationInputRef.current.resetInputs) {
            verificationInputRef.current.resetInputs();
        }
        setCode('');
    };

    const resendCode = async () => {
        if (isResendDisabled || isResending) return;
        setIsResending(true);
        setError(null);
        try {
            const response = await fetch('/api/softpos/password/request-reset', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({ email }),
            });
            const data = await response.json().catch(() => ({}));
            if (response.ok && data.success) {
                setToken(data.token);
                startResendTimer();
                resetVerificationInputs();
                setMessage('A new code has been sent if the email exists.');
            } else {
                setError(data.message || 'Failed to resend code.');
            }
        } catch (err) {
            setError('Network error while resending code.');
        } finally {
            setIsResending(false);
        }
    };

    const maskEmail = (value) => {
        if (!value) return '';
        const [user, domain] = value.split('@');
        if (!domain) return value;
        const maskedUser = user.length > 4 ? `${user.slice(0, 4)}****${user.slice(-2)}` : user;
        return `${maskedUser}@${domain}`;
    };

    return (
        <div className="bg-body d-flex flex-column flex-center rounded-4 w-md-600px p-10">
            <div className="d-flex flex-center flex-column align-items-stretch h-lg-100 w-md-400px">
                <div className="d-flex flex-center flex-column flex-column-fluid pb-15 pb-lg-20 w-100">
                    {step === 'request' && (
                        <form className="form w-100" onSubmit={handleRequestReset} noValidate>
                            <div className="text-center mb-10">
                                <h1 className="text-gray-900 fw-bolder mb-3">Forgot Password ?</h1>
                                <div className="text-gray-500 fw-semibold fs-6">Enter your email to reset your password.</div>
                            </div>

                            {message && <div className="alert alert-success" role="alert">{message}</div>}
                            {error && <div className="alert alert-danger" role="alert">{error}</div>}

                            <div className="fv-row mb-8">
                                <input
                                    type="email"
                                    placeholder="Email"
                                    name="email"
                                    autoComplete="off"
                                    className="form-control bg-transparent"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    required
                                />
                            </div>

                            <div className="d-flex flex-wrap justify-content-center pb-lg-0">
                                <button type="submit" className="btn btn-primary me-4" disabled={submitting}>
                                    <span className="indicator-label">{submitting ? 'Please wait...' : 'Submit'}</span>
                                    {submitting && (
                                        <span className="indicator-progress">
                                            <span className="spinner-border spinner-border-sm align-middle ms-2"></span>
                                        </span>
                                    )}
                                </button>
                                <a href="/merchant/login" className="btn btn-light">Cancel</a>
                            </div>
                        </form>
                    )}

                    {step === 'verify' && (
                        <div className="w-100">
                            <div className="text-center mb-10">
                                <h1 className="text-gray-900 fw-bolder mb-3">Verify Code</h1>
                                <div className="text-gray-500 fw-semibold fs-6">We sent a 6-digit code to: <strong>{maskEmail(email)}</strong></div>
                            </div>

                            {message && <div className="alert alert-success" role="alert">{message}</div>}
                            {error && <div className="alert alert-danger" role="alert">{error}</div>}

                            <div className="mb-10">
                                <div className="fw-bolder text-start text-dark fs-6 mb-1 ms-1">Type your 6 digit security code</div>
                                <VerificationInput
                                    ref={verificationInputRef}
                                    length={6}
                                    onComplete={(val) => {
                                        setCode(val);
                                        handleVerifyCode(val);
                                    }}
                                />

                                {verifying && (
                                    <div className="d-flex justify-content-center mt-5">
                                        <div className="spinner-border text-primary" role="status">
                                            <span className="visually-hidden">Verifying...</span>
                                        </div>
                                    </div>
                                )}

                                <div className="text-center mt-5">
                                    <button
                                        type="button"
                                        className={`btn ${isResendDisabled ? 'btn-secondary' : 'btn-link'}`}
                                        disabled={isResendDisabled || isResending}
                                        onClick={resendCode}
                                    >
                                        {isResending ? (
                                            <>
                                                <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                                Sending Code...
                                            </>
                                        ) : isResendDisabled ? (
                                            `Resend Code in ${resendTimer}s`
                                        ) : (
                                            'Resend Code'
                                        )}
                                    </button>
                                </div>
                            </div>

                            <div className="d-flex flex-wrap justify-content-center pb-lg-0">
                                <button type="button" className="btn btn-light" onClick={() => setStep('request')} disabled={verifying}>Back</button>
                            </div>
                        </div>
                    )}

                    {step === 'reset' && (
                        <form className="form w-100" onSubmit={handleResetPassword} noValidate>
                            <div className="text-center mb-10">
                                <h1 className="text-gray-900 fw-bolder mb-3">Set New Password</h1>
                                <div className="text-gray-500 fw-semibold fs-6">Enter and confirm your new password.</div>
                            </div>

                            {message && <div className="alert alert-success" role="alert">{message}</div>}
                            {error && <div className="alert alert-danger" role="alert">{error}</div>}

                            <div className="fv-row mb-8">
                                <label className="form-label fw-bolder text-dark fs-6">Password</label>
                                <div className="position-relative">
                                    <input
                                        type={showPassword ? 'text' : 'password'}
                                        placeholder="Enter password"
                                        name="password"
                                        className={`form-control form-control-lg form-control-solid ${isPasswordValid() ? 'is-valid' : 'is-invalid'}`}
                                        value={password}
                                        onChange={handlePasswordChange}
                                        style={{ textTransform: 'none', paddingLeft: '40px' }}
                                        required
                                    />
                                    <span
                                        className="btn btn-sm btn-icon position-absolute translate-middle-y top-50 start-0 ms-2"
                                        onClick={() => setShowPassword(!showPassword)}
                                    >
                                        <i className={`fas fa-${showPassword ? 'eye-slash' : 'eye'}`}></i>
                                    </span>
                                </div>
                            </div>

                            <div className="fv-row mb-8">
                                <label className="form-label fw-bolder text-dark fs-6">Confirm Password</label>
                                <div className="position-relative">
                                    <input
                                        type={showPasswordConfirmation ? 'text' : 'password'}
                                        placeholder="Confirm password"
                                        name="password_confirmation"
                                        className={`form-control form-control-lg form-control-solid ${passwordValidation.match ? 'is-valid' : 'is-invalid'}`}
                                        value={passwordConfirmation}
                                        onChange={handlePasswordChange}
                                        style={{ textTransform: 'none', paddingLeft: '40px' }}
                                        required
                                    />
                                    <span
                                        className="btn btn-sm btn-icon position-absolute translate-middle-y top-50 start-0 ms-2"
                                        onClick={() => setShowPasswordConfirmation(!showPasswordConfirmation)}
                                    >
                                        <i className={`fas fa-${showPasswordConfirmation ? 'eye-slash' : 'eye'}`}></i>
                                    </span>
                                </div>
                                {!passwordValidation.match && passwordConfirmation && (
                                    <div className="invalid-feedback">Passwords do not match</div>
                                )}
                            </div>

                            <div className="fv-row mb-8">
                                <div className="password-validation mt-3">
                                    <div className={`validation-item ${passwordValidation.length ? 'text-success' : 'text-danger'}`}>
                                        <i className={`fas fa-${passwordValidation.length ? 'check' : 'times'} me-2`}></i>
                                        At least 8 characters
                                    </div>
                                    <div className={`validation-item ${passwordValidation.uppercase ? 'text-success' : 'text-danger'}`}>
                                        <i className={`fas fa-${passwordValidation.uppercase ? 'check' : 'times'} me-2`}></i>
                                        One uppercase letter
                                    </div>
                                    <div className={`validation-item ${passwordValidation.lowercase ? 'text-success' : 'text-danger'}`}>
                                        <i className={`fas fa-${passwordValidation.lowercase ? 'check' : 'times'} me-2`}></i>
                                        One lowercase letter
                                    </div>
                                    <div className={`validation-item ${passwordValidation.number ? 'text-success' : 'text-danger'}`}>
                                        <i className={`fas fa-${passwordValidation.number ? 'check' : 'times'} me-2`}></i>
                                        One number
                                    </div>
                                </div>
                            </div>

                            <div className="d-flex flex-wrap justify-content-center pb-lg-0">
                                <button type="submit" className="btn btn-primary me-4" disabled={resetting}>
                                    <span className="indicator-label">{resetting ? 'Saving...' : 'Reset Password'}</span>
                                    {resetting && (
                                        <span className="indicator-progress">
                                            <span className="spinner-border spinner-border-sm align-middle ms-2"></span>
                                        </span>
                                    )}
                                </button>
                                <button type="button" className="btn btn-light" onClick={() => setStep('verify')} disabled={resetting}>Back</button>
                            </div>
                        </form>
                    )}

                    {step === 'done' && (
                        <div className="w-100 text-center">
                            <div className="alert alert-success" role="alert">{message || 'Password reset successful.'}</div>
                            <a href="/merchant/login" className="btn btn-primary">Go to Login</a>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default ForgotPasswordCard;

