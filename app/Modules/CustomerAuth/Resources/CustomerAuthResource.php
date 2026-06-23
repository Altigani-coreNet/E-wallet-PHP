<?php

namespace App\Modules\CustomerAuth\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAuthResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @param Customer $resource
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'birthDate' => $this->birth_date?->toIso8601String(),
            'gender' => $this->gender,
            'address' => $this->address,
            'countryId' => $this->country_id,
            'merchantCountryId' => $this->merchant_country_id,
            'cityId' => $this->city_id,
            'state' => $this->state,
            'zip' => $this->zip,
            'merchantId' => $this->merchant_id,
            'profileCompleted' => (bool) $this->profile_completed,
            'balance' => number_format((float) $this->balance, 2, '.', ''),
            'country' => $this->whenLoaded('country', function () {
                if (!$this->country) {
                    return null;
                }

                return [
                    'id' => $this->country->id,
                    'name' => $this->localizedName($this->country->name),
                    'dialCode' => $this->country->code,
                ];
            }),
            'city' => $this->whenLoaded('city', function () {
                if (!$this->city) {
                    return null;
                }

                return [
                    'id' => $this->city->id,
                    'name' => $this->localizedName($this->city->name),
                ];
            }),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function localizedName(mixed $name): string
    {
        if (is_array($name)) {
            $locale = app()->getLocale();

            return (string) ($name[$locale] ?? reset($name) ?: '');
        }

        return (string) $name;
    }
}
