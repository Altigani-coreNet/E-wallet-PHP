<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantCatalogProductFormFieldResource extends JsonResource
{
    /**
     * Normalize backend field type.
     */
    private function normalizedType(?string $type): string
    {
        return strtolower(trim((string) $type));
    }

    /**
     * Map backend type to generic form widget type.
     */
    private function mapFormType(?string $type): string
    {
        return match ($this->normalizedType($type)) {
            'number', 'numeric', 'amount', 'decimal', 'number field' => 'text_field',
            'dropdown', 'select', 'list' => 'dropdown',
            'textarea', 'multiline' => 'text_area',
            'date' , 'date field' => 'date_picker',
            'text field' => 'text_field',
            'email field' => 'email_field',
            'checkbox' => 'checkbox',
            'radio buttons' , 'radio' => 'radio',
            default => 'text_field',
        };
    }

    /**
     * Map backend type to generic input type.
     */
    private function mapInputType(?string $type): string
    {
        return match ($this->normalizedType($type)) {
            'number', 'numeric', 'amount', 'decimal' , 'number field' => 'number',
            'dropdown', 'select', 'list' => 'selection',
            'textarea', 'multiline' => 'text_multiline',
            'date' , 'date field' => 'date',
            'email' => 'email',
            'phone', 'tel', 'mobile' => 'phone',
            'checkbox' => 'checkbox',
            'radio buttons' , 'radio' => 'radio',
            default => 'text',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function mappedCustomization(?array $customization): array
    {
        $customization = is_array($customization) ? $customization : [];
        $inputType = $this->mapInputType($this->type);
        $min = $customization['min'] ?? null;
        $max = $customization['max'] ?? null;
        $minLength = $customization['min_length'] ?? null;
        $maxLength = $customization['max_length'] ?? null;
        $hint = $customization['hint'] ?? null;

        if ($inputType === 'number') {
            return [
                'min' => $min,
                'max' => $max,
                'min_length' => $minLength,
                'max_length' => $maxLength,
                'regex' => null,
                'hint' => $hint,
            ];
        }

        if (in_array($inputType, ['text', 'email'], true)) {
            $resolvedMinLength = $minLength ?? $min;
            $resolvedMaxLength = $maxLength ?? $max;
            return [
                'min' => null,
                'max' => null,
                'min_length' => $resolvedMinLength,
                'max_length' => $resolvedMaxLength,
                'regex' => $customization['regex'] ?? null,
                'hint' => $hint,
            ];
        }

        return [
            'min' => null,
            'max' => null,
            'min_length' => null,
            'max_length' => null,
            'regex' => null,
            'hint' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // 'id' => $this->id,
            'label' => app()->getLocale() === 'ar'
                ? ($this->label_ar ?: $this->label_en)
                : ($this->label_en ?: $this->label_ar),
            'key' => $this->normalizedType($this->key),
            'type' => $this->type,
            'form_type' => $this->mapFormType($this->type),
            'input_type' => $this->mapInputType($this->type),
            'options_json' => $this->options_json,
            // 'customization_json' => $this->customization_json,
            ...$this->mappedCustomization($this->customization_json),
            'sort_order' => $this->sort_order,
            'is_required' => (bool) $this->is_required,
        ];
    }
}

