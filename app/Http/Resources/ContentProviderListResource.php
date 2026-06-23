<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin list payload for partners / content providers: flat locale-aware labels, legacy IDs, no heavy nested graphs.
 */
class ContentProviderListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content_provider_id' => $this->id,
            'merchant_id' => $this->id,

            'name' => $this->name,
            'business_name' => $this->business_name,
            'owner_name' => $this->owner_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'business_phone' => $this->business_phone,
            'address' => $this->address,

            'status' => $this->status,
            'merchant_code' => $this->merchant_code,
            'is_active' => $this->is_active,
            'is_parent' => $this->is_parent,
            'parent_id' => $this->parent_id,

            'country_id' => $this->country_id,
            'partner_category_id' => $this->partner_category_id,

            'business_type' => $this->business_type?->value ?? $this->business_type,

            'logo' => $this->logo,
            'logo_url' => $this->logo_url,

            'user_id' => $this->user_id,

            'country_name' => $this->whenLoaded('country', fn () => $this->country?->name),
            'country_short_name' => $this->whenLoaded('country', fn () => $this->country?->short_name),

            'partner_category_name' => $this->whenLoaded('partnerCategory', fn () => $this->partnerCategory?->name),

            'parent_partner' => $this->whenLoaded('parentPartner', function () {
                if (! $this->parentPartner) {
                    return null;
                }

                return [
                    'id' => $this->parentPartner->id,
                    'name' => $this->parentPartner->name,
                    'business_name' => $this->parentPartner->business_name,
                ];
            }),
            'parent_partner_name' => $this->whenLoaded('parentPartner', function () {
                if (! $this->parentPartner) {
                    return null;
                }

                return $this->parentPartner->business_name ?? $this->parentPartner->name;
            }),

            'sub_partners_count' => $this->when(isset($this->sub_partners_count), (int) $this->sub_partners_count),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
