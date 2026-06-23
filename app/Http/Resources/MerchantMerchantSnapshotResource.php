<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Read-only merchant (payee) snapshot for transaction detail.
 */
class MerchantMerchantSnapshotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'business_name' => $this->business_name,
            'owner_name' => $this->owner_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'merchant_code' => $this->merchant_code,
            'logo' => $this->logo,
            'logo_url' => $this->logo ? coreservice_asset($this->logo) : null,
            'country_id' => $this->country_id,
            'country' => $this->whenLoaded('country', function () {
                return (new CountryResource($this->country))->resolve();
            }),
        ];
    }
}
