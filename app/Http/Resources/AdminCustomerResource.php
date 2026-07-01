<?php

namespace App\Http\Resources;

use App\Models\ChangeRequest;
use App\Models\Customer;
use App\Models\CustomerRejection;
use App\Modules\CustomerAuth\Services\CustomerAttachmentService;
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
            'id' => $customer->id,
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
            'balance' => (float) ($customer->relationLoaded('wallet') && $customer->wallet
                ? $customer->wallet->balance
                : 0),
            'wallet_uuid' => $customer->relationLoaded('wallet') && $customer->wallet
                ? $customer->wallet->id
                : null,
            'wallet_public_id' => $customer->relationLoaded('wallet') && $customer->wallet
                ? $customer->wallet->wallet_id
                : null,
            'profile_image_url' => $customer->getProfileImageApi(),
            'attachments' => app(CustomerAttachmentService::class)->getAttachmentsForAdmin($customer),
            'profile_completed' => (bool) $customer->profile_completed,
            'profile_completion' => Customer::calculateProfileCompletion($customer),
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
            'latest_rejection' => $this->latestRejection($customer),
            'has_pending_change' => $this->hasPendingChange($customer),
            'created_at' => $customer->created_at?->toIso8601String(),
            'updated_at' => $customer->updated_at?->toIso8601String(),
        ];
    }

    private function latestRejection(Customer $customer): ?array
    {
        $rejection = $customer->relationLoaded('rejections')
            ? $customer->rejections->first()
            : CustomerRejection::query()
                ->where('customer_id', $customer->id)
                ->latest()
                ->first();

        if (! $rejection) {
            return null;
        }

        return [
            'id' => $rejection->id,
            'rejection_reason' => $rejection->rejection_reason,
            'invalid_fields' => $rejection->invalid_fields ?? [],
            'missing_attachments' => $rejection->missing_attachments ?? [],
            'rejected_by' => $rejection->rejected_by,
            'created_at' => $rejection->created_at?->toIso8601String(),
        ];
    }

    private function hasPendingChange(Customer $customer): bool
    {
        return ChangeRequest::query()
            ->where('changeable_type', Customer::class)
            ->where('changeable_id', $customer->id)
            ->where('status', 'pending')
            ->exists();
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
