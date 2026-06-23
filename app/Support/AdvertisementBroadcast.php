<?php

namespace App\Support;

use App\Events\BannersListingUpdatedEvent;
use App\Models\Advertisement;

class AdvertisementBroadcast
{
    /**
     * Broadcast the full current list of publicly visible banners (same query/transform as GET /advertisements).
     */
    public static function broadcastFullListing(): void
    {
        $data = Advertisement::query()
            ->visibleInPublicApiListing()
            ->orderBy('id')
            ->get()
            ->map(fn ($ad) => AdvertisementPublicListing::apiPayload($ad))
            ->values()
            ->all();

        event(new BannersListingUpdatedEvent($data));
    }
}
