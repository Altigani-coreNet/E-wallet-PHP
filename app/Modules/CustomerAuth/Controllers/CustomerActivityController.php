<?php

namespace App\Modules\CustomerAuth\Controllers;

use App\Models\Customer;
use App\Modules\CustomerAuth\Requests\CustomerActivityIndexRequest;
use App\Modules\CustomerAuth\Resources\CustomerActivityLogResource;
use App\Modules\CustomerAuth\Services\CustomerActivityService;
use App\Support\SuccessResponse;
use Illuminate\Support\Facades\Auth;

class CustomerActivityController
{
    public function __construct(
        private readonly CustomerActivityService $activityService,
    ) {}

    public function index(CustomerActivityIndexRequest $request)
    {
        /** @var Customer|null $customer */
        $customer = Auth::guard('customer')->user();

        if (! $customer instanceof Customer) {
            return SuccessResponse::error('Unauthorized', 401);
        }

        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 15);
        $actionFilter = isset($validated['action']) ? (string) $validated['action'] : null;

        $logs = $this->activityService->paginateForCustomer(
            $customer,
            $actionFilter,
            $perPage,
        );

        return SuccessResponse::make([
            'data' => CustomerActivityLogResource::collection($logs->getCollection())->resolve(),
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'per_page' => $logs->perPage(),
            'total' => $logs->total(),
        ]);
    }
}
