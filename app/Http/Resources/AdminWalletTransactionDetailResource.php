<?php

namespace App\Http\Resources;

use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminWalletTransactionDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{transaction: WalletTransaction, related_transactions: iterable<int, WalletTransaction>, operation: array<string, mixed>} $payload */
        $payload = $this->resource;

        $transaction = $payload['transaction'];
        $relatedTransactions = collect($payload['related_transactions'] ?? []);

        return [
            'transaction' => (new AdminWalletTransactionResource($transaction))->resolve(),
            'related_transactions' => AdminWalletTransactionResource::collection($relatedTransactions)->resolve(),
            'operation' => $payload['operation'] ?? [],
        ];
    }
}
