<?php

namespace App\Support;

use App\Models\Advertisement;

class AdvertisementPublicListing
{
    /**
     * Same shape as GET advertisements (getByCountry) item payload.
     */
    public static function apiPayload(Advertisement $ad): array
    {
        return [
            'id' => $ad->id,
            'name' => $ad->name,
            'image_url' => $ad->image_url,
            'country_id' => $ad->country_id,
        ];
    }
}
