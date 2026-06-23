<?php

namespace App\Modules\CustomerAuth\Controllers;

use App\Models\Advertisement;
use App\Models\Customer;
use App\Support\AdvertisementPublicListing;
use App\Support\SuccessResponse;
use Illuminate\Support\Facades\Auth;

class CustomerBannerController
{
    public function index()
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $query = Advertisement::query();

        if ($customer->country_id) {
            $query->where('country_id', $customer->country_id);
        }

        $advertisements = $query
            ->visibleInPublicApiListing()
            ->get()
            ->map(fn ($ad) => AdvertisementPublicListing::apiPayload($ad));

        return SuccessResponse::make($advertisements);
    }
}
