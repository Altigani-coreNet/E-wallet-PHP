<?php

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Customer $customer */
        $customer = $this->resource;

        return [
            'uuid' => $customer->uuid,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'national_id' => $customer->national_id,
            'address' => $customer->address,
            'country_id' => $customer->country_id,
            'city_id' => $customer->city_id,
            'state' => $customer->state,
            'zip' => $customer->zip,
            'status' => $customer->status ?? Customer::STATUS_PENDING,
            'balance' => (float) ($customer->balance ?? 0),
            'profile_image_url' => $customer->getProfileImageApi(),
            'profile_completed' => (bool) $customer->profile_completed,
            'merchant_id' => $customer->merchant_id,
            'country_name' => $customer->relationLoaded('country') && $customer->country
                ? $this->localizedName($customer->country)
                : null,
            'city_name' => $customer->relationLoaded('city') && $customer->city
                ? $this->localizedName($customer->city)
                : null,
            'country' => $customer->relationLoaded('country') && $customer->country
                ? [
                    'id' => $customer->country->id,
                    'name' => $this->localizedName($customer->country),
                    'text' => $this->localizedName($customer->country),
                    'code' => $customer->country->code ?? null,
                ]
                : null,
            'city' => $customer->relationLoaded('city') && $customer->city
                ? [
                    'id' => $customer->city->id,
                    'name' => $this->localizedName($customer->city),
                    'text' => $this->localizedName($customer->city),
                ]
                : null,
            'merchant' => $customer->relationLoaded('merchant') && $customer->merchant
                ? [
                    'id' => $customer->merchant->id,
                    'name' => $customer->merchant->name,
                    'business_name' => $customer->merchant->business_name ?? null,
                ]
                : null,
            'created_at' => $customer->created_at?->toIso8601String(),
            'updated_at' => $customer->updated_at?->toIso8601String(),
        ];
    }

    private function localizedName(object $model): string
    {
        if (method_exists($model, 'getTranslation')) {
            $locale = app()->getLocale();

            return $model->getTranslation('name', $locale, false)
                ?: $model->getTranslation('name', 'en', false)
                ?: $model->getTranslation('name', 'ar', false)
                ?: '';
        }

        $name = $model->name ?? '';

        return is_string($name) ? $name : '';
    }
}
