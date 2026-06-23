<?php

namespace App\Http\Resources;

use App\Models\Currency;
use App\Support\LocaleString;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Merchant transaction detail: merchant, country, user, currency, payment method, etc.
 * (Partner / service relations are not loaded or exposed.)
 */
class MerchantTransactionDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $currency = $this->resource->relationLoaded('currency')
            ? $this->resource->getRelation('currency')
            : null;
        $user = $this->resource->relationLoaded('user')
            ? $this->resource->getRelation('user')
            : null;

        $data['currency_symbol'] = LocaleString::one($currency?->symbol) ?? $this->resource->currency_symbol;
        $data['user_name'] = $user?->name;

        if ($this->resource->relationLoaded('merchant') && $this->resource->merchant) {
            $data['merchant'] = (new MerchantMerchantSnapshotResource($this->resource->merchant))->resolve();
        }

        foreach (['partner', 'service', 'service_category', 'partner_name', 'service_category_name', 'service_name'] as $key) {
            unset($data[$key]);
        }

        unset($data['country']);
        if ($this->resource->relationLoaded('country') && $this->resource->getRelation('country')) {
            $data['country'] = (new CountryResource($this->resource->getRelation('country')))->resolve();
        }

        // Transaction defines getCurrencyAttribute() (stdClass from currency_object), which shadows
        // $model->currency — always use the loaded relationship for the real Currency model.
        unset($data['currency']);
        if ($currency instanceof Currency) {
            $data['currency'] = (new CurrencyResource($currency))->resolve();
        }

        if ($this->resource->relationLoaded('user') && $this->resource->user) {
            $data['user'] = [
                'id' => $this->resource->user->id,
                'name' => $this->resource->user->name,
                'email' => $this->resource->user->email,
            ];
        }

        $data['refundable_amount'] = $this->resource->getAvailableAmountForRefund();
        $data['original_transaction_id'] = $this->resource->reference_transaction_id;

        return $data;
    }
}
