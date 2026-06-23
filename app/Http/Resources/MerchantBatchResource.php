<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantBatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = strtolower($this->status ?? 'pending');

        return [
            'id' => $this->id,
            'batch_number' => $this->batch_number ?? 'N/A',
            'status' => $this->status ?? 'pending',
            'status_badge_class' => $this->statusBadgeClass($status),
            'currency_symbol' => $this->currency_symbol ?? '$',
            'total_amount' => (float) ($this->total_amount ?? 0),
            'transaction_count' => (int) ($this->transaction_count ?? 0),
            'created_at' => $this->created_at?->format('M d, Y H:i:s'),
            'created_at_raw' => $this->created_at?->toIso8601String(),
        ];
    }

    private function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'settled' => 'badge-light-success',
            'pending' => 'badge-light-warning',
            'failed' => 'badge-light-danger',
            default => 'badge-light-secondary',
        };
    }
}
