<?php

namespace App\Modules\CustomerAuth\Controllers;

use App\Modules\CustomerAuth\Requests\CompleteProfileRequest;
use App\Modules\CustomerAuth\Requests\CustomerForgotPasswordRequest;
use App\Modules\CustomerAuth\Requests\CustomerLoginRequest;
use App\Modules\CustomerAuth\Requests\CustomerRegisterRequest;
use App\Modules\CustomerAuth\Requests\CustomerResetPasswordRequest;
use App\Modules\CustomerAuth\Services\CustomerAuthService;
use App\Support\SuccessResponse;
use Illuminate\Support\Facades\Auth;

class CustomerAuthController
{
    public function __construct(
        private readonly CustomerAuthService $authService,
    ) {}

    public function register(CustomerRegisterRequest $request)
    {
        $data = $this->authService->register($request->validated());

        return SuccessResponse::make($data, 'Registration successful', 201);
    }

    public function login(CustomerLoginRequest $request)
    {
        $data = $this->authService->login($request->validated());

        return SuccessResponse::make($data, 'Login successful');
    }

    public function forgotPassword(CustomerForgotPasswordRequest $request)
    {
        $data = $this->authService->forgotPassword($request->validated('phone'));

        return SuccessResponse::make($data, 'If an account exists, an OTP has been sent via SMS', 201);
    }

    public function resetPassword(CustomerResetPasswordRequest $request)
    {
        $data = $this->authService->resetPassword($request->validated());

        return SuccessResponse::make($data, 'Password reset successfully');
    }

    public function profile()
    {
        /** @var \App\Models\Customer $customer */
        $customer = Auth::guard('customer')->user();
        $data = $this->authService->profile($customer);

        return SuccessResponse::make($data);
    }

    public function completeProfile(CompleteProfileRequest $request)
    {
        /** @var \App\Models\Customer $customer */
        $customer = Auth::guard('customer')->user();
        $data = $this->authService->completeProfile($customer, $request->validated());

        return SuccessResponse::make($data, 'Profile completed successfully');
    }

    public function logout()
    {
        $data = $this->authService->logout();

        return SuccessResponse::make($data, 'Logged out successfully');
    }
}
