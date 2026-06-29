<?php

namespace App\Http\Resources;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminWalletMoneyOperationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $payload */
        $payload = is_array($this->resource) ? $this->resource : $this->resource->toArray();

        return [
            'amount' => (float) ($payload['amount'] ?? 0),
            'description' => $payload['description'] ?? null,
            'wallet' => $this->resolveWallet($payload['wallet'] ?? null),
            'transaction' => $this->resolveTransaction($payload['transaction'] ?? null),
            'posting_reference' => $payload['posting_reference'] ?? null,
        ];
    }

    private function resolveWallet(mixed $wallet): ?array
    {
        if ($wallet instanceof Wallet) {
            return (new AdminWalletResource($wallet))->resolve();
        }

        if (is_array($wallet) && ! empty($wallet['id'])) {
            $model = Wallet::query()
                ->with(['customer.merchant'])
                ->find($wallet['id']);

            if ($model) {
                return (new AdminWalletResource($model))->resolve();
            }
        }

        return is_array($wallet) ? $wallet : null;
    }

    private function resolveTransaction(mixed $transaction): ?array
    {
        if ($transaction instanceof WalletTransaction) {
            return (new AdminWalletTransactionResource($transaction))->resolve();
        }

        if (is_array($transaction) && ! empty($transaction['id'])) {
            $model = WalletTransaction::query()->find($transaction['id']);

            if ($model) {
                return (new AdminWalletTransactionResource($model))->resolve();
            }
        }

        return is_array($transaction) ? $transaction : null;
    }
}
