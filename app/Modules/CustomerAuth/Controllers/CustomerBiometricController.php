<?php

namespace App\Modules\CustomerAuth\Controllers;

use App\Models\Customer;
use App\Modules\CustomerAuth\Requests\BiometricChallengeRequest;
use App\Modules\CustomerAuth\Requests\BiometricDisableRequest;
use App\Modules\CustomerAuth\Requests\BiometricEnrollRequest;
use App\Modules\CustomerAuth\Requests\BiometricLoginRequest;
use App\Modules\CustomerAuth\Services\CustomerBiometricService;
use App\Support\SuccessResponse;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class CustomerBiometricController
{
    public function __construct(
        private readonly CustomerBiometricService $biometricService,
    ) {}

    public function enroll(BiometricEnrollRequest $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        try {
            $data = $this->biometricService->enroll($customer, $request->validated());

            return SuccessResponse::make($data, 'Biometric device enrolled successfully', 201);
        } catch (InvalidArgumentException $exception) {
            return SuccessResponse::error($exception->getMessage(), 422);
        } catch (ConflictHttpException $exception) {
            return SuccessResponse::error($exception->getMessage(), 409);
        }
    }

    public function devices()
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $data = $this->biometricService->listDevices($customer);

        return SuccessResponse::make($data, 'Biometric devices retrieved successfully');
    }

    public function revoke(string $id)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        try {
            $this->biometricService->revokeDevice($customer, $id);

            return SuccessResponse::make(null, 'Biometric device revoked successfully');
        } catch (InvalidArgumentException $exception) {
            return SuccessResponse::error($exception->getMessage(), 422);
        }
    }

    public function disable(BiometricDisableRequest $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        try {
            $this->biometricService->disableDevice($customer, $request->validated('device_id'));

            return SuccessResponse::make(null, 'Biometric login disabled for this device');
        } catch (InvalidArgumentException $exception) {
            return SuccessResponse::error($exception->getMessage(), 422);
        }
    }

    public function challenge(BiometricChallengeRequest $request)
    {
        try {
            $data = $this->biometricService->issueChallenge($request->validated('device_id'));

            return SuccessResponse::make($data, 'Biometric challenge issued successfully');
        } catch (UnauthorizedHttpException $exception) {
            return SuccessResponse::error($exception->getMessage() ?: 'Invalid credentials', 401);
        } catch (TooManyRequestsHttpException $exception) {
            return SuccessResponse::error($exception->getMessage() ?: 'Too many requests', 429);
        }
    }

    public function login(BiometricLoginRequest $request)
    {
        try {
            $data = $this->biometricService->login($request->validated());

            return SuccessResponse::make($data, 'Login successful');
        } catch (InvalidArgumentException $exception) {
            return SuccessResponse::error($exception->getMessage(), 422);
        } catch (UnauthorizedHttpException $exception) {
            return SuccessResponse::error($exception->getMessage() ?: 'Invalid credentials', 401);
        }
    }
}
