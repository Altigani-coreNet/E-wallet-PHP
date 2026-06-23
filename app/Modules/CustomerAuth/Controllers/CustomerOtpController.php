<?php

namespace App\Modules\CustomerAuth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CustomerAuth\Requests\SendEmailOtpRequest;
use App\Modules\CustomerAuth\Requests\SendSmsOtpRequest;
use App\Modules\CustomerAuth\Requests\VerifyOtpRequest;
use App\Modules\CustomerAuth\Services\CustomerOtpService;
use App\Support\SuccessResponse;
use Illuminate\Http\JsonResponse;

class CustomerOtpController extends Controller
{
    public function __construct(
        private readonly CustomerOtpService $otpService,
    ) {
    }

    public function sendSms(SendSmsOtpRequest $request): JsonResponse
    {
        $data = $this->otpService->generateAndSendSmsOtp($request->validated('phone'));

        return SuccessResponse::make($data, 'OTP sent via SMS');
    }

    public function sendEmail(SendEmailOtpRequest $request): JsonResponse
    {
        $data = $this->otpService->generateAndSendEmailOtp($request->validated('email'));

        return SuccessResponse::make($data, 'OTP sent to email');
    }

    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $data = $this->otpService->verifyAndResolveAccount(
                $request->validated('token'),
                (int) $request->validated('code'),
            );
        } catch (\InvalidArgumentException $e) {
            return SuccessResponse::error($e->getMessage(), 400);
        }

        return SuccessResponse::make($data, 'OTP verified successfully');
    }
}
