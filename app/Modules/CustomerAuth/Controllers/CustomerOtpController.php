<?php

namespace App\Modules\CustomerAuth\Controllers;

use App\Modules\CustomerAuth\Requests\SendEmailOtpRequest;
use App\Modules\CustomerAuth\Requests\SendSmsOtpRequest;
use App\Modules\CustomerAuth\Requests\VerifyOtpRequest;
use App\Modules\CustomerAuth\Services\CustomerOtpService;
use App\Support\SuccessResponse;

class CustomerOtpController
{
    public function __construct(
        private readonly CustomerOtpService $otpService,
    ) {}

    public function sendSms(SendSmsOtpRequest $request)
    {
        $data = $this->otpService->generateAndSendSmsOtp($request->validated('phone'));

        return SuccessResponse::make($data, 'OTP sent via SMS', 201);
    }

    public function sendEmail(SendEmailOtpRequest $request)
    {
        $data = $this->otpService->generateAndSendEmailOtp($request->validated('email'));

        return SuccessResponse::make($data, 'OTP sent to email', 201);
    }

    public function verify(VerifyOtpRequest $request)
    {
        $validated = $request->validated();
        $data = $this->otpService->verifyAndResolveAccount(
            $validated['token'],
            (int) $validated['code'],
        );

        return SuccessResponse::make($data, 'OTP verified successfully', 201);
    }
}
