<?php

namespace App\Http\Resources;

use App\Services\ProductServiceFormsSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Pick description translation from Accept-Language (ar vs en), with sensible fallbacks.
     */
    protected function descriptionForRequest(Request $request): ?string
    {
        $preferred = $request->getPreferredLanguage(['ar', 'en', 'en-US', 'ar-SA']);
        $locale = 'en';
        if ($preferred !== null && str_starts_with(strtolower($preferred), 'ar')) {
            $locale = 'ar';
        }

        $text = $this->getTranslation('description', $locale, false);
        if ($text !== null && $text !== '') {
            return $text;
        }

        return $this->getTranslation('description', 'en', false)
            ?: $this->getTranslation('description', 'ar', false);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'service_sub_category_id' => $this->service_sub_category_id,
            'type_id' => $this->type_id,
            'country_id' => $this->country_id,
            'country' => $this->whenLoaded('country', function () {
                return [
                    'id' => $this->country->id,
                    'name' => $this->country->name,
                    'short_name' => $this->country->short_name ?? null,
                ];
            }),
            'service' => $this->whenLoaded('service', function () {
                return [
                    'id' => $this->service->id,
                    'service_name' => [
                        'en' => $this->service->serviceNameForLocale('en'),
                        'ar' => $this->service->serviceNameForLocale('ar'),
                    ],
                    'service_type' => $this->service->service_type?->value ?? null,
                    'country' => $this->service->relationLoaded('country') && $this->service->country
                        ? [
                            'id' => $this->service->country->id,
                            'name' => $this->service->country->name,
                            'short_name' => $this->service->country->short_name ?? null,
                        ]
                        : null,
                    'partner' => $this->service->relationLoaded('partner') && $this->service->partner
                        ? [
                            'id' => $this->service->partner->id,
                            'name' => $this->service->partner->name,
                            'parent_id' => $this->service->partner->parent_id,
                            'parent_name' => $this->service->partner->parentPartner?->name,
                        ]
                        : null,
                ];
            }),
            'name' => $this->name,
            'name_en' => $this->getTranslation('name', 'en', false),
            'name_ar' => $this->getTranslation('name', 'ar', false),
            'description' => $this->description,
            'description_en' => $this->getTranslation('description', 'en', false),
            'description_ar' => $this->getTranslation('description', 'ar', false),
            'description_text' => $this->descriptionForRequest($request),
            'service_url' => $this->service_url,
            'notify_url' => $this->notify_url,
            'prepay_url' => $this->prepay_url,
            'image' => $this->image,
            'image_url' => $this->image ? coreservice_asset($this->image) : null,
            'status' => $this->status,
            'forms_count' => $this->when(
                isset($this->service_forms_count),
                fn () => (int) $this->service_forms_count
            ),
            'service_forms' => $this->when(
                $this->relationLoaded('serviceForms'),
                fn () => ProductServiceFormsSyncService::toResponseArray($this->resource)
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
