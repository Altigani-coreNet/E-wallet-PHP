<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantServiceCatalogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_name' => $this->service_name,
            'description' => $this->description,
            'service_type' => $this->service_type?->value,
            'image_url' => $this->image ? coreservice_asset($this->image) : null,
            'products' => MerchantCatalogProductResource::collection($this->whenLoaded('products')),
        ];
    }
}

