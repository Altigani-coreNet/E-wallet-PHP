<?php

namespace App\Modules\CustomerAuth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CustomerAuth\Requests\CompleteProfileRequest;
use App\Modules\CustomerAuth\Requests\CustomerRegisterRequest;
use App\Modules\CustomerAuth\Services\CustomerAuthService;
use App\Support\SuccessResponse;
use Illuminate\Http\JsonResponse;

class CustomerAuthController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $customerAuthService,
    ) {
    }

    public function register(CustomerRegisterRequest $request): JsonResponse
    {
        try {
            $data = $this->customerAuthService->register($request->validated());
        } catch (\InvalidArgumentException $e) {
            return SuccessResponse::error($e->getMessage(), 400);
        } catch (\DomainException $e) {
            return SuccessResponse::error($e->getMessage(), 409);
        }

        return SuccessResponse::make($data, 'Registration successful');
    }

    public function profile(): JsonResponse
    {
        $customer = auth()->guard('customer')->user();
        $data = $this->customerAuthService->profile($customer);

        return SuccessResponse::make($data);
    }

    public function completeProfile(CompleteProfileRequest $request): JsonResponse
    {
        $customer = auth()->guard('customer')->user();

        try {
            $data = $this->customerAuthService->completeProfile($customer, $request->validated());
        } catch (\InvalidArgumentException $e) {
            return SuccessResponse::error($e->getMessage(), 400);
        } catch (\DomainException $e) {
            return SuccessResponse::error($e->getMessage(), 409);
        }

        return SuccessResponse::make($data, 'Profile completed successfully');
    }
}
