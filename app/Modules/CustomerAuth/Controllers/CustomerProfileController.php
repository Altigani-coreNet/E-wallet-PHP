<?php

namespace App\Modules\CustomerAuth\Controllers;

use App\Models\Customer;
use App\Modules\CustomerAuth\Requests\UpdateRejectedFieldsRequest;
use App\Modules\CustomerAuth\Services\CustomerProfileService;
use App\Support\SuccessResponse;
use Illuminate\Support\Facades\Auth;

class CustomerProfileController
{
    public function __construct(
        private readonly CustomerProfileService $profileService,
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
}
