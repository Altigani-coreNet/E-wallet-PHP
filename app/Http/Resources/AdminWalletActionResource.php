<?php

namespace App\Http\Resources;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminWalletActionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $payload */
        $payload = is_array($this->resource) ? $this->resource : $this->resource->toArray();

        $wallet = $payload['wallet'] ?? null;

        return [
            'message' => $payload['message'] ?? '',
            'wallet' => $wallet instanceof Wallet
                ? (new AdminWalletResource($wallet))->resolve()
                : ($payload['wallet'] ?? null),
        ];
    }
}
