<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_number' => $this->batch_number,
            'merchant_id' => $this->merchant_id,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'transaction_count' => $this->transaction_count,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'settled_at' => $this->settled_at?->format('Y-m-d H:i:s'),
            'currency' => $this->currency,
            'total_amount_refunded' => $this->total_amount_refunded,
            'total_amount_voided' => $this->total_amount_voided,	
            // Include transactions if loaded
            'transactions' => $this->whenLoaded('transactions', function () {
                return TransactionResource::collection($this->transactions);
            }),

            'settlement_number' => $this->whenLoaded('settlement', function () {
                return $this->settlement?->settlement_number ?? '';
            }),
            
            // Include transaction summary
            'transaction_summary' => [
                'total_amount' => $this->total_amount,
                'transaction_count' => $this->transaction_count,
                'pending_count' => $this->whenLoaded('transactions', function () {
                    return $this->transactions->where('status', 'pending')->count();
                }, 0),
                'approved_count' => $this->whenLoaded('transactions', function () {
                    return $this->transactions->where('status', 'approved')->count();
                }, 0),
                'voided_count' => $this->whenLoaded('transactions', function () {
                    return $this->transactions->where('status', 'voided')->count();
                }, 0),
                'voided_amount' => $this->whenLoaded('transactions', function () {
                    return (double) number_format($this->transactions->sum('voided_amount'), 2);
                }, 0),
                'refunded_count' => $this->whenLoaded('transactions', function () {
                    return $this->transactions->where('status', 'refunded')->count();
                }, 0),
                'refunded_amount' => $this->whenLoaded('transactions', function () {
                    return (double) number_format($this->transactions->sum('refunded_amount'), 2);
                }, 0),
            ],
        ];
    }
}
