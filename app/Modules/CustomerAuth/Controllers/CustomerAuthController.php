<?php

namespace App\Modules\CustomerAuth\Controllers;

use App\Models\Customer;
use App\Modules\CustomerAuth\Requests\CompleteProfileRequest;
use App\Modules\CustomerAuth\Requests\CustomerForgotPasswordRequest;
use App\Modules\CustomerAuth\Requests\CustomerLoginRequest;
use App\Modules\CustomerAuth\Requests\CustomerRegisterRequest;
use App\Modules\CustomerAuth\Requests\CustomerResetPasswordRequest;
use App\Modules\CustomerAuth\Requests\UpdateProfileRequest;
use App\Modules\CustomerAuth\Services\CustomerAuthService;
use App\Modules\CustomerAuth\Support\CustomerJwtService;
use App\Support\SuccessResponse;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerAuthController
{
    public function __construct(
        private readonly CustomerAuthService $authService,
        private readonly CustomerJwtService $jwtService,
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

    public function refreshToken(Request $request)
    {
        $token = $this->extractBearerToken($request);

        if (! $token) {
            return SuccessResponse::error('Unauthorized', 401);
        }

        try {
            $payload = $this->jwtService->decodeForRefresh($token);
        } catch (ExpiredException|SignatureInvalidException|\UnexpectedValueException) {
            return SuccessResponse::error('Unauthorized', 401);
        }

        if (($payload->type ?? null) !== 'customer') {
            return SuccessResponse::error('Customer access required', 403);
        }

        $customer = Customer::query()->find($payload->sub ?? null);

        if (! $customer) {
            return SuccessResponse::error('Unauthorized', 401);
        }

        $data = $this->authService->refreshToken($customer);

        return SuccessResponse::make($data, 'Token refreshed successfully');
    }

    public function profile()
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $data = $this->authService->profile($customer);

        return SuccessResponse::make($data);
    }

    public function completeProfile(CompleteProfileRequest $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $data = $this->authService->completeProfile($customer, $request->validated(), $request);

        return SuccessResponse::make($data, 'Profile completed successfully');
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $data = $this->authService->updateProfile($customer, $request->validated(), $request);

        return SuccessResponse::make($data, 'Profile updated successfully');
    }

    public function logout()
    {
        $data = $this->authService->logout();

        return SuccessResponse::make($data, 'Logged out successfully');
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
