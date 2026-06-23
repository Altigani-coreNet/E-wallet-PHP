<?php

namespace App\Modules\CustomerAuth\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Modules\CustomerAuth\Resources\CustomerAuthResource;
use App\Modules\CustomerAuth\Support\CustomerJwtService;
use App\Modules\CustomerAuth\Support\OtpTokenCipher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class CustomerOnboardingService
{
    public function listCountries(): Collection
    {
        return Country::query()
            ->where('status', true)
            ->whereNull('deleted_at')
            ->orderBy('short_name')
            ->get()
            ->map(fn (Country $country) => [
                'id' => $country->id,
                'shortName' => $country->short_name,
                'code' => $country->code,
                'dialCode' => $country->dial_code,
                'name' => $this->localizedName($country),
            ]);
    }

    public function listCitiesByDialCode(string $dialCode): Collection
    {
        $normalized = ltrim(trim($dialCode), '+');

        $country = Country::query()
            ->where('dial_code', $normalized)
            ->where('status', true)
            ->whereNull('deleted_at')
            ->first();

        if (! $country) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(
                'Country not found'
            );
        }

        return City::query()
            ->where('country_id', $country->id)
            ->where('status', true)
            ->whereNull('deleted_at')
            ->orderBy('created_at')
            ->get()
            ->map(fn (City $city) => [
                'id' => $city->id,
                'name' => $this->localizedName($city),
                'countryId' => $city->country_id,
            ]);
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
