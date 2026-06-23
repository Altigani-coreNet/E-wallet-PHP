<?php

namespace App\Http\Resources;

use App\Support\LocaleString;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        // getRelation() throws if the relation was not eager-loaded; show() omits some list-only relations.
        $currency = $this->resource->relationLoaded('currency')
            ? $this->resource->getRelation('currency')
            : null;
        $country = $this->resource->relationLoaded('country')
            ? $this->resource->getRelation('country')
            : null;
        $user = $this->resource->relationLoaded('user')
            ? $this->resource->getRelation('user')
            : null;

        // Keep full payload as-is, only override currency_symbol from relation.
        $data['currency_symbol'] = $currency?->symbol ?? $this->resource->currency_symbol;

        $data['user_name'] = $user?->name;

        foreach (['partner_name', 'service_category_name', 'service_name'] as $key) {
            unset($data[$key]);
        }

        // Drop heavy / redundant nested objects.
        foreach (['merchant', 'partner', 'service_category', 'service', 'user', 'currency'] as $key) {
            unset($data[$key]);
        }

        if ($country) {
            $data['country'] = (new CountryResource($country))->resolve();
        } else {
            unset($data['country']);
        }

        return $data;
    }
}
