<?php

namespace App\Http\Resources;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminWalletResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Wallet $wallet */
        $wallet = $this->resource;
        $customer = $wallet->relationLoaded('customer') ? $wallet->customer : null;

        return [
            'id' => $wallet->id,
            'wallet_id' => $wallet->wallet_id,
            'user_number' => $wallet->user_number,
            'type' => $wallet->resolveType(),
            'is_master' => $wallet->isMaster(),
            'status' => $wallet->status,
            'balance' => (float) $wallet->balance,
            'available_balance' => (float) $wallet->available_balance,
            'currency_code' => $wallet->currency_code,
            'merchant_id' => $wallet->merchant_id,
            'customer_id' => $wallet->customer_id,
            'owner' => $customer
                ? (new AdminWalletOwnerResource($customer))->resolve()
                : ($wallet->isMaster() ? (new AdminWalletOwnerResource($wallet))->resolve() : null),
            'summary' => $wallet->getAttribute('summary'),
            'created_at' => $wallet->created_at?->toIso8601String(),
            'updated_at' => $wallet->updated_at?->toIso8601String(),
        ];
    }
}
