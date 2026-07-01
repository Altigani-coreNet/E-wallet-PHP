<?php

namespace App\Http\Resources;

use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminWalletTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WalletTransaction $transaction */
        $transaction = $this->resource;
        $wallet = $transaction->relationLoaded('wallet') ? $transaction->wallet : null;
        $customer = $wallet && $wallet->relationLoaded('customer') ? $wallet->customer : null;
        $merchant = $customer && $customer->relationLoaded('merchant') ? $customer->merchant : null;

        return [
            'id' => $transaction->id,
            'type' => $transaction->type,
            'direction' => $transaction->direction,
            'amount' => (float) $transaction->amount,
            'signed_amount' => (float) ($transaction->getAttribute('signed_amount') ?? (
                $transaction->direction === 'debit'
                    ? -abs((float) $transaction->amount)
                    : abs((float) $transaction->amount)
            )),
            'balance_after' => (float) $transaction->balance_after,
            'reference' => $transaction->reference,
            'reference_id' => $transaction->reference_id,
            'operation_id' => $transaction->operation_id,
            'description' => $transaction->description,
            'note' => $transaction->note,
            'created_by' => $transaction->created_by,
            'wallet' => $wallet ? [
                'id' => $wallet->id,
                'wallet_id' => $wallet->wallet_id,
                'user_number' => $wallet->user_number,
                'type' => $wallet->resolveType(),
                'is_master' => $wallet->isMaster(),
            ] : null,
            'owner' => $customer ? [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'merchant_id' => $customer->merchant_id,
                'merchant_name' => $merchant?->business_name ?? $merchant?->name,
            ] : null,
            'counterparty' => $transaction->getAttribute('counterparty'),
            'created_at' => $transaction->created_at?->toIso8601String(),
        ];
    }
}
