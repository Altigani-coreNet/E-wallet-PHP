<?php

namespace App\Modules\CustomerAuth\Controllers;

use App\Models\Customer;
use App\Modules\CustomerAuth\Requests\ConfirmEmailVerificationRequest;
use App\Modules\CustomerAuth\Resources\CustomerAuthResource;
use App\Modules\CustomerAuth\Services\CustomerOtpService;
use App\Support\SuccessResponse;
use Illuminate\Support\Facades\Auth;

class CustomerEmailVerificationController
{
    public function __construct(
        private readonly CustomerOtpService $otpService,
    ) {}

    public function send()
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $data = $this->otpService->sendEmailVerificationOtp($customer);

        return SuccessResponse::make($data, 'OTP sent to email', 201);
    }

    public function confirm(ConfirmEmailVerificationRequest $request)
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
