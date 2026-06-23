<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceCategoryResource extends JsonResource
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
            'type' => $this->type ?? 'service',
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'name' => $this->name, // Localized name
            'code' => $this->code,
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', function () {
                return [
                    'id' => $this->parent?->id,
                    'name_en' => $this->parent?->name_en,
                    'name_ar' => $this->parent?->name_ar,
                    'name' => $this->parent?->name,
                    'code' => $this->parent?->code,
                ];
            }),
            'children' => ServiceCategoryResource::collection($this->whenLoaded('children')),
            'sub_categories' => ServiceSubCategoryResource::collection($this->whenLoaded('subCategories')),
            'is_active' => $this->is_active,
            'image' => $this->image,
            'image_url' => $this->image
                ? (function_exists('coreservice_asset') ? coreservice_asset($this->image) : asset($this->image))
                : null,
            'description' => $this->description,
            'services_count' => $this->when(isset($this->services_count), $this->services_count),
            'sub_categories_count' => $this->when(isset($this->sub_categories_count), $this->sub_categories_count),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}










