<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantBatchDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'batch_number' => $this->batch_number,
            'merchant_id' => $this->merchant_id,
            'status' => $this->status,
            'total_amount' => (float) ($this->total_amount ?? 0),
            'transaction_count' => (int) ($this->transaction_count ?? 0),
            'currency_symbol' => $this->currency_symbol ?? '$',
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'settled_at' => $this->settled_at?->format('Y-m-d H:i:s'),
        ];

        if ($this->resource->relationLoaded('merchant') && $this->merchant) {
            $data['merchant'] = (new MerchantMerchantSnapshotResource($this->merchant))->resolve();
        }

        if ($this->resource->relationLoaded('transactions')) {
            $data['transactions'] = $this->transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => (float) ($transaction->amount ?? 0),
                    'status' => $transaction->status,
                    'currency_symbol' => $transaction->currency_symbol,
                    'terminal_id' => $transaction->terminal_id,
                    'created_at' => $transaction->created_at?->format('Y-m-d H:i:s'),
                ];
            })->values()->all();
        }

        return $data;
    }
}
