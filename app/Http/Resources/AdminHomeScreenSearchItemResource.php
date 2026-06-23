<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminHomeScreenSearchItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $image = $this->image ?? null;

        return [
            'id' => (string) ($this->id ?? ''),
            'type' => (string) ($this->type ?? ''),
            'label' => (string) ($this->label ?? ''),
            'subtitle' => $this->subtitle ?: null,
            'image' => $image ? coreservice_asset($image) : null,
            'is_active' => (bool) ($this->is_active ?? true),
        ];
    }
}
