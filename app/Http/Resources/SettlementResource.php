<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettlementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'settlement_number' => $this->settlement_number,
            'batch_id' => $this->batch_id,
            'merchant_id' => $this->merchant_id,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'transaction_count' => $this->transaction_count,
            'settled_at' => $this->settled_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'batch_number' => $this->batch->batch_number,
            'transactions' => TransactionResource::collection($this->transactions),
        ];
    }
}
