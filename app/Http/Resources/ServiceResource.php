<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        $descriptionLocalized = $this->getTranslation('description', $locale, false);
        if ($descriptionLocalized === null || $descriptionLocalized === '') {
            $desc = $this->description;
            if (is_array($desc)) {
                $descriptionLocalized = $desc[$locale] ?? $desc['en'] ?? $desc['ar'] ?? null;
            }
        }

        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category_name' => $this->whenLoaded('category', fn () => $this->category->name),
            'sub_category_id' => $this->sub_category_id,
            'sub_category_name' => $this->whenLoaded('subCategory', fn () => $this->subCategory?->name),
            'country_id' => $this->country_id,
            'country_name' => $this->whenLoaded('country', fn () => $this->country->name),
            'country_short_name' => $this->whenLoaded('country', fn () => $this->country->short_name),
            'partner_id' => $this->partner_id,
            'partner_name' => $this->whenLoaded('partner', fn () => $this->partner->name),
            'content_provider_id' => $this->partner_id, // Legacy key
            'merchant_id' => $this->partner_id, // Legacy key
            'service_type' => $this->service_type?->value ?? null,
            'service_type_display' => $this->service_type?->getDisplayName() ?? null,
            'service_name' => [
                'en' => $this->serviceNameForLocale('en'),
                'ar' => $this->serviceNameForLocale('ar'),
            ],
            'image' => $this->image,
            'image_url' => coreservice_asset($this->image) ?? null,
            'description' => $this->description,
            'description_text' => $descriptionLocalized,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'products_count' => $this->when(isset($this->products_count), $this->products_count),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
