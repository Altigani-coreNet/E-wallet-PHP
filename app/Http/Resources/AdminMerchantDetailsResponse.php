<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMerchantDetailsResponse extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'merchant_code' => $this->merchant_code,
            'name' => $this->name,
            'owner_name' => $this->owner_name,
            'business_name' => $this->business_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'logo' => $this->logo ? (function_exists('coreservice_asset') ? coreservice_asset($this->logo) : asset($this->logo)) : null,
            'status' => $this->status,
            'is_active' => (bool) $this->is_active,
            'business_type' => $this->business_type,
            'scopes' => $this->scopes ?? [],

            'country_id' => $this->country_id,
            'city_id' => $this->city_id,
            'country_name' => $this->country?->name,
            'city_name' => $this->city?->name,

            'plan' => $this->whenLoaded('plan', function () {
                return [
                    'name' => $this->plan->name,
                    'text' => $this->plan->name,
                ];
            }),

            'trade_license_number' => $this->trade_license_number,
            'trade_license_start_date' => optional($this->trade_license_start_date)->toDateString(),
            'trade_license_expired_date' => optional($this->trade_license_expired_date)->toDateString(),
            'tax_number' => $this->tax_number,

            'attachments_count' => $this->when(isset($this->attachments_count), (int) $this->attachments_count),
            'users_count' => $this->when(isset($this->users_count), (int) $this->users_count),
            'terminals_count' => $this->when(isset($this->terminals_count), (int) $this->terminals_count),

            'user' => $this->whenLoaded('user', function () {
                return $this->user ? new AdminUserResponse($this->user) : null;
            }),

            'events' => $this->whenLoaded('logs', function () {
                return AdminMerchantEventResponse::collection($this->logs);
            }),

            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}

