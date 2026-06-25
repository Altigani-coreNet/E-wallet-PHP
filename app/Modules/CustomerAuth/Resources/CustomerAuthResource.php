<?php

namespace App\Modules\CustomerAuth\Resources;

use App\Models\Customer;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAuthResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Customer $customer */
        $customer = $this->resource;

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'birthDate' => $customer->birth_date?->toIso8601String(),
            'gender' => $customer->gender,
            'profileImage' => $customer->getProfileImageApi(),
            'address' => $customer->address,
            'countryId' => $customer->country_id,
            'merchantCountryId' => $customer->merchant_country_id,
            'cityId' => $customer->city_id,
            'state' => $customer->state,
            'zip' => $customer->zip,
            'merchantId' => $customer->merchant_id,
            'profileCompleted' => (bool) $customer->profile_completed,
            'balance' => number_format((float) $customer->balance, 2, '.', ''),
            'country' => $customer->relationLoaded('country') && $customer->country
                ? [
                    'id' => $customer->country->id,
                    'name' => $this->localizedName($customer->country),
                    'code' => $customer->country->code,
                ]
                : null,
            'city' => $customer->relationLoaded('city') && $customer->city
                ? [
                    'id' => $customer->city->id,
                    'name' => $this->localizedName($customer->city),
                ]
                : null,
            'createdAt' => $customer->created_at?->toIso8601String(),
            'updatedAt' => $customer->updated_at?->toIso8601String(),
        ];
    }

    private function localizedName(object $model): string
    {
        if (method_exists($model, 'getTranslation')) {
            return $model->getTranslation('name', app()->getLocale(), false)
                ?: $model->getTranslation('name', 'en', false)
                ?: '';
        }

        return '';
    }
}
