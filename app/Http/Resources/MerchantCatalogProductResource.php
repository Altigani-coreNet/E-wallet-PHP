<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantCatalogProductResource extends JsonResource
{
    private function formsWithPositionFlags()
    {
        if (! $this->relationLoaded('serviceForms')) {
            return collect();
        }

        $forms = $this->serviceForms->values();
        $lastIndex = $forms->count() - 1;

        return $forms->map(function ($form, $index) use ($lastIndex) {
            $form->is_first_form = ($index === 0);
            $form->is_last_form = ($index === $lastIndex);
            return $form;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'service_url' => $this->service_url,
            'notify_url' => $this->notify_url,
            'prepay_url' => $this->prepay_url,
            'image_url' => $this->image ? coreservice_asset($this->image) : null,
            'product_forms' => MerchantCatalogProductFormResource::collection($this->formsWithPositionFlags()),
        ];
    }
}

