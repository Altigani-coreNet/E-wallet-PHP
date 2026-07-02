<?php

namespace App\Modules\CustomerAuth\Controllers;

use App\Models\Customer;
use App\Modules\CustomerAuth\Requests\ConfirmEmailVerificationRequest;
use App\Modules\CustomerAuth\Requests\UpdateRejectedFieldsRequest;
use App\Modules\CustomerAuth\Resources\CustomerAuthResource;
use App\Modules\CustomerAuth\Services\CustomerOtpService;
use App\Modules\CustomerAuth\Services\CustomerProfileService;
use App\Support\SuccessResponse;
use Illuminate\Support\Facades\Auth;

class CustomerProfileController
{
    public function __construct(
        private readonly CustomerProfileService $profileService,
        private readonly CustomerOtpService $otpService,
    ) {}

    public function rejectedFields()
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $data = $this->profileService->getRejectedFields($customer);

        return SuccessResponse::make($data);
    }

    public function updateRejectedFields(UpdateRejectedFieldsRequest $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $data = $this->profileService->updateRejectedFields(
            $customer,
            $request->validated(),
            $request,
        );

        return SuccessResponse::make($data, 'Profile updated successfully');
    }

    /**
     * Send a 6-digit OTP to the authenticated customer's email on file (no email in request body).
     */
    public function sendEmailVerification()
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $data = $this->otpService->sendEmailVerificationOtp($customer);

        return SuccessResponse::make($data, 'OTP sent to email', 201);
    }

    /**
     * Confirm email verification using OTP token + code for the authenticated customer's email on file.
     */
    public function confirmEmailVerification(ConfirmEmailVerificationRequest $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $validated = $request->validated();

        $customer = $this->otpService->confirmEmailVerification(
            $customer,
            $validated['token'],
            (int) $validated['code'],
        );

        return SuccessResponse::make([
            'email_verified' => true,
            'customer' => CustomerAuthResource::make($customer)->resolve(),
        ], 'Email verified successfully');
    }
}
