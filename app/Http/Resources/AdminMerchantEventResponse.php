<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMerchantEventResponse extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'metadata' => $this->metadata,
            'message' => $this->message,
            'label' => $this->label,
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}

