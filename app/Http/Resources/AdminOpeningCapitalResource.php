<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOpeningCapitalResource extends JsonResource
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
            'posting_reference' => $payload['posting_reference'] ?? null,
        ];
    }
}
