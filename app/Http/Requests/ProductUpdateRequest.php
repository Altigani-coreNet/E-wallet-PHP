<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_id' => 'sometimes|required|exists:services,id',
            'service_sub_category_id' => 'nullable|exists:service_sub_categories,id',
            'type_id' => 'nullable|exists:service_types,id',
            'country_id' => 'nullable|exists:countries,id',

            'name' => 'nullable|array',
            'name.en' => 'nullable|string|max:255',
            'name.ar' => 'nullable|string|max:255',

            'description' => 'nullable|array',
            'description.en' => 'nullable|string|max:10000',
            'description.ar' => 'nullable|string|max:10000',

            'service_url' => 'nullable|string|max:2048',
            'notify_url' => 'nullable|string|max:2048',
            'prepay_url' => 'nullable|string|max:2048',
            'image' => 'nullable',

            'status' => 'nullable|boolean',

            'forms' => 'sometimes|nullable|array',
            'forms.*.form_name' => 'nullable|array',
            'forms.*.form_name.en' => 'nullable|string|max:255',
            'forms.*.form_name.ar' => 'nullable|string|max:255',
            'forms.*.title' => 'nullable|string|max:255',
            'forms.*.form_url' => 'nullable|string|max:2048',
            'forms.*.country_id' => 'nullable|uuid',
            'forms.*.fields' => 'required|array|min:1',
            'forms.*.fields.*.label_en' => 'nullable|string|max:255',
            'forms.*.fields.*.label_ar' => 'nullable|string|max:255',
            'forms.*.fields.*.key' => 'required|string|max:255',
            'forms.*.fields.*.type' => 'required|string|max:255',
            'forms.*.fields.*.options' => 'nullable|array',
            'forms.*.fields.*.customization' => 'nullable|array',
            'forms.*.fields.*.customization.min_length' => 'nullable|numeric|min:0',
            'forms.*.fields.*.customization.max_length' => 'nullable|numeric|min:0',
            'forms.*.fields.*.customization.regex' => 'nullable|string|max:1000',
            'forms.*.fields.*.customization.hint' => 'nullable|string|max:255',
            // For number fields, min/max remain numeric; for date fields they are validated separately in withValidator.
            'forms.*.fields.*.customization.min' => 'nullable',
            'forms.*.fields.*.customization.max' => 'nullable',
            'forms.*.fields.*.sort_order' => 'nullable|integer|min:0',
            'forms.*.fields.*.is_required' => 'nullable|boolean',
            'forms.*.fields.*.status' => 'nullable|boolean',
            'forms.*.fields.*.country_id' => 'nullable|uuid',
        ];
    }

    protected function prepareForValidation(): void
    {
        $forms = $this->input('forms');
        if (is_string($forms)) {
            $decoded = json_decode($forms, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge(['forms' => $decoded]);
            }
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'service_id.required' => 'The service is required.',
            'service_id.exists' => 'The selected service does not exist.',
        ];
    }
}
