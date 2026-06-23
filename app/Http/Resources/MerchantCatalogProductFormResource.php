<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantCatalogProductFormResource extends JsonResource
{
    private function primaryActionType(): string
    {
        if ((bool) ($this->is_last_form ?? false)) {
            return 'tap_to_pay';
        }

        if ((bool) ($this->is_first_form ?? false)) {
            return 'procced';
        }

        return 'button';
    }

    private function primaryActionLabel(string $primaryActionType): string
    {
        return match ($primaryActionType) {
            'tap_to_pay' => __('translation.tap_to_pay'),
            'procced' => __('translation.proceed'),
            default => 'Continue',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $primaryActionType = $this->primaryActionType();

        // dd($this->from_name);
        return [
            'id' => $this->id,
            'form_name' => $this->form_name,
            'form_url' => $this->form_url,
            'product_form_fields' => MerchantCatalogProductFormFieldResource::collection($this->whenLoaded('fields')),
            'actions' => [
                [
                    'action_name' => 'cancel',
                    'action_type' => 'button',
                    'action_label' => __('translation.cancel'),
                ],
                [
                    'action_name' => $primaryActionType,
                    'action_type' => $primaryActionType,
                    'action_label' => $this->primaryActionLabel($primaryActionType),
                ],
            ],
        ];
    }
}

