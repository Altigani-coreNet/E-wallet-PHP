<?php

namespace App\Modules\CustomerAuth\Controllers;

use App\Modules\CustomerAuth\Services\CustomerOnboardingService;
use App\Support\SuccessResponse;

class CustomerOnboardingController
{
    public function __construct(
        private readonly CustomerOnboardingService $onboardingService,
    ) {}

    public function listCountries()
    {
        $data = $this->onboardingService->listCountries();

        return SuccessResponse::make($data, 'Countries retrieved successfully');
    }

    public function listCities(string $dialCode)
    {
        $data = $this->onboardingService->listCitiesByDialCode($dialCode);

        return SuccessResponse::make($data, 'Cities retrieved successfully');
    }
}
