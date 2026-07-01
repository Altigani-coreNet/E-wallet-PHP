<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Single service detail for merchant apps (catalog-shaped payload, no admin-only fields).
 */
class MerchantServiceDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'partner_id' => $this->partner_id,
            'service_name' => $this->service_name,
            'description' => $this->description,
            'service_type' => $this->service_type?->value,
            'image_url' => $this->image ? coreservice_asset($this->image) : null,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name_en' => $this->category->name_en,
                    'name_ar' => $this->category->name_ar,
                    'code' => $this->category->code,
                    'category_image' => $this->category->image
                        ? coreservice_asset($this->category->image)
                        : coreservice_asset('settings.png'),
                ];
            }),
            'products' => MerchantCatalogProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
