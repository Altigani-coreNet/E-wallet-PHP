<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
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
            'country' => $this->country,
            'name' => $this->name,
            'symbol' => $this->getTranslation('symbol', app()->getLocale(), false)
                ?? $this->getTranslation('symbol', 'en', false)
                ?? '',
            'currency_code' => $this->getTranslation('currency_code', app()->getLocale(), false)
                ?? $this->getTranslation('currency_code', 'en', false)
                ?? '',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
