<?php

namespace App\Modules\CustomerAuth\Resources;

use App\Models\Customer;
use App\Modules\CustomerAuth\Services\CustomerAttachmentService;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAuthResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Customer $customer */
        $customer = $this->resource;

        $wallet = $customer->relationLoaded('wallet') ? $customer->wallet : null;
        $attachments = app(CustomerAttachmentService::class)->getAttachmentUrls($customer);

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'birthDate' => $customer->birth_date?->toIso8601String(),
            'gender' => $customer->gender,
            'nationalId' => $customer->national_id,
            'profileImage' => $attachments['profile_image'] ?? $customer->getProfileImageApi(),
            'attachments' => $attachments,
            'address' => $customer->address,
            'countryId' => $customer->country_id,
            'cityId' => $customer->city_id,
            'state' => $customer->state,
            'zip' => $customer->zip,
            'status' => $customer->status,
            'profileCompleted' => (bool) $customer->profile_completed,
            'walletId' => $wallet?->wallet_id,
            'balance' => number_format((float) ($wallet?->balance ?? $customer->balance), 2, '.', ''),
            'availableBalance' => $wallet !== null
                ? number_format((float) $wallet->available_balance, 2, '.', '')
                : null,
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
