<?php

namespace App\Modules\CustomerAuth\Controllers;

use App\Modules\CustomerAuth\Resources\CustomerAuthResource;
use App\Modules\CustomerAuth\Services\CustomerOtpService;
use App\Support\SuccessResponse;
use Illuminate\Http\Request;

class CustomerEmailVerificationController
{
    public function __construct(
        private readonly CustomerOtpService $otpService,
    ) {}

    /**
     * Public endpoint — verify email when customer clicks the link from admin-triggered email.
     */
    public function verifyLink(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $customer = $this->otpService->verifyEmailByLink($validated['token']);

        return SuccessResponse::make([
            'email_verified' => true,
            'customer' => CustomerAuthResource::make($customer)->resolve(),
        ], 'Email verified successfully');
    }
}
