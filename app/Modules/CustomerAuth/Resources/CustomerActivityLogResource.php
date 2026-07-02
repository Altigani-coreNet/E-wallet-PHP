<?php

namespace App\Modules\CustomerAuth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Log */
class CustomerActivityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'message' => $this->message ?? $this->text,
            'label' => $this->label,
            'created_at' => $this->created_at?->toIso8601String(),
            'time' => $this->time,
        ];
    }
}
