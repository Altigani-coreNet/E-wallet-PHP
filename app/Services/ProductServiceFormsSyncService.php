<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductServiceForm;
use App\Models\ProductServiceFormField;
use Illuminate\Support\Facades\DB;

class ProductServiceFormsSyncService
{
    /**
     * @param mixed $name
     * @return array{en: string, ar: string}
     */
    private static function normalizeFormNameTranslations($name): array
    {
        if (is_string($name)) {
            $trimmed = trim($name);
            return ['en' => $trimmed, 'ar' => ''];
        }

        if (! is_array($name)) {
            return ['en' => '', 'ar' => ''];
        }

        return [
            'en' => is_scalar($name['en'] ?? null) ? trim((string) $name['en']) : '',
            'ar' => is_scalar($name['ar'] ?? null) ? trim((string) $name['ar']) : '',
        ];
    }

    /**
     * Replace all service forms + fields for a product (same behavior as POST service-forms).
     *
     * @param  array<int, array<string, mixed>>  $forms  Validated/normalized forms payload
     */
    public function sync(Product $product, array $forms): void
    {
        DB::transaction(function () use ($product, $forms) {
            ProductServiceForm::where('product_id', $product->id)->delete();

            foreach ($forms as $formIndex => $formData) {
                $form = ProductServiceForm::create([
                    'product_id' => $product->id,
                    'form_name' => self::normalizeFormNameTranslations($formData['form_name'] ?? null),
                    'form_url' => $formData['form_url'] ?? null,
                    'country_id' => $formData['country_id'] ?? null,
                ]);

                foreach (($formData['fields'] ?? []) as $fieldIndex => $fieldData) {
                    ProductServiceFormField::create([
                        'product_service_form_id' => $form->id,
                        'label_en' => $fieldData['label_en'] ?? null,
                        'label_ar' => $fieldData['label_ar'] ?? null,
                        'key' => $fieldData['key'],
                        'type' => $fieldData['type'],
                        'options_json' => $fieldData['options'] ?? [],
                        'customization_json' => $fieldData['customization'] ?? null,
                        'sort_order' => $fieldData['sort_order'] ?? $fieldIndex,
                        'is_required' => array_key_exists('is_required', $fieldData) ? (bool) $fieldData['is_required'] : true,
                        'status' => array_key_exists('status', $fieldData) ? (bool) $fieldData['status'] : true,
                        'country_id' => $fieldData['country_id'] ?? null,
                    ]);
                }
            }
        });
    }

    /**
     * Normalize builder payload (title, client ids) to API sync shape.
     *
     * @param  array<int, mixed>  $rawForms
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeFromBuilder(array $rawForms): array
    {
        $out = [];
        foreach ($rawForms as $form) {
            if (! is_array($form)) {
                continue;
            }
            $name = $form['form_name'] ?? $form['title'] ?? null;
            $fieldsIn = $form['fields'] ?? [];
            $fieldsOut = [];
            foreach ($fieldsIn as $idx => $field) {
                if (! is_array($field)) {
                    continue;
                }
                $opts = [];
                foreach ($field['options'] ?? [] as $opt) {
                    if (! is_array($opt)) {
                        continue;
                    }
                    $opts[] = [
                        'label_en' => $opt['label_en'] ?? '',
                        'label_ar' => $opt['label_ar'] ?? '',
                        'value' => $opt['value'] ?? '',
                    ];
                }
                $fieldsOut[] = [
                    'label_en' => $field['label_en'] ?? null,
                    'label_ar' => $field['label_ar'] ?? null,
                    'key' => $field['key'] ?? '',
                    'type' => $field['type'] ?? 'Text Field',
                    'options' => $opts,
                    'customization' => isset($field['customization']) && is_array($field['customization']) ? $field['customization'] : null,
                    'sort_order' => $field['sort_order'] ?? $idx,
                    'is_required' => array_key_exists('is_required', $field) ? (bool) $field['is_required'] : true,
                    'status' => array_key_exists('status', $field) ? (bool) $field['status'] : true,
                    'country_id' => $field['country_id'] ?? null,
                ];
            }
            $out[] = [
                'form_name' => self::normalizeFormNameTranslations($name),
                'form_url' => $form['form_url'] ?? null,
                'country_id' => $form['country_id'] ?? null,
                'fields' => $fieldsOut,
            ];
        }

        return $out;
    }

    /**
     * Same shape as GET .../service-forms (for unified product API response).
     */
    public static function toResponseArray(Product $product): array
    {
        $collection = $product->relationLoaded('serviceForms')
            ? $product->serviceForms->sortBy('id')->values()
            : ProductServiceForm::query()
                ->where('product_id', $product->id)
                ->with(['fields'])
                ->orderBy('id')
                ->get();

        return $collection
            ->map(function (ProductServiceForm $form) {
                return [
                    'id' => $form->id,
                    'form_name' => $form->form_name,
                    'form_name_en' => $form->getTranslation('form_name', 'en', false) ?? '',
                    'form_name_ar' => $form->getTranslation('form_name', 'ar', false) ?? '',
                    'form_url' => $form->form_url,
                    'country_id' => $form->country_id,
                    'fields' => ($form->fields ?? collect())->map(function (ProductServiceFormField $field) {
                        return [
                            'id' => $field->id,
                            'label_en' => $field->label_en,
                            'label_ar' => $field->label_ar,
                            'key' => $field->key,
                            'type' => $field->type,
                            'options' => $field->options_json ?? [],
                            'customization' => $field->customization_json ?? null,
                            'sort_order' => $field->sort_order,
                            'is_required' => (bool) $field->is_required,
                            'status' => (bool) $field->status,
                            'country_id' => $field->country_id,
                        ];
                    })->values(),
                ];
            })
            ->values()
            ->all();
    }
}
