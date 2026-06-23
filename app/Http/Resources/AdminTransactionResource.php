<?php

namespace App\Http\Resources;

use App\Support\LocaleString;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin transaction list: flat display fields only (no nested merchant/partner objects).
 */
class AdminTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $merchant = $this->resource->relationLoaded('merchant')
            ? $this->resource->getRelation('merchant')
            : null;
        $partner = $this->resource->relationLoaded('partner')
            ? $this->resource->getRelation('partner')
            : null;
        $country = $this->resource->relationLoaded('country')
            ? $this->resource->getRelation('country')
            : null;
        $serviceCategory = $this->resource->relationLoaded('serviceCategory')
            ? $this->resource->getRelation('serviceCategory')
            : null;
        $currency = $this->resource->relationLoaded('currency')
            ? $this->resource->getRelation('currency')
            : null;
        $paymentMethod = $this->resource->relationLoaded('paymentMethod')
            ? $this->resource->getRelation('paymentMethod')
            : null;

        $countryName = null;
        if ($country) {
            $countryName = LocaleString::one($country->name) ?? $country->short_name ?? null;
        }

        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'amount' => $this->amount,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'currency_symbol' => LocaleString::one($currency?->symbol) ?? $this->currency_symbol ?? '$',
            'country_name' => $countryName,
            'partner_name' => $partner?->name ?? $this->partner_name,
            'merchant_name' => $merchant?->business_name
                ?? $merchant?->name
                ?? null,
            'service_category_name' => $serviceCategory?->name_en
                ?? $this->service_category_name
                ?? null,
            'payment_type' => $this->payment_type,
            'method' => $this->method ?? $paymentMethod?->card_type ?? null,
        ];
    }
}
