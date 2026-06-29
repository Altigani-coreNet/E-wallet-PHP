<?php

namespace App\Http\Resources;

use App\Models\Customer;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminWalletOwnerResource extends JsonResource
{
    /**
     * @return array<string, mixed>|null
     */
    public function toArray(Request $request): ?array
    {
        if ($this->resource instanceof Customer) {
            $customer = $this->resource;
            $merchant = $customer->relationLoaded('merchant') ? $customer->merchant : null;

            return [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'merchant_id' => $customer->merchant_id,
                'merchant_name' => $merchant?->business_name ?? $merchant?->name,
            ];
        }

        if ($this->resource instanceof Wallet && $this->resource->isMaster()) {
            return [
                'name' => 'Master Wallet',
                'email' => null,
                'phone' => null,
                'merchant_id' => null,
                'merchant_name' => null,
                'description' => 'System equity / funding pool',
            ];
        }

        return null;
    }
}
