<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceStoreRequest extends FormRequest
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
            'category_id' => 'required|exists:service_categories,id',
            'sub_category_id' => 'nullable|exists:service_sub_categories,id',
            'country_id' => 'required',
            'partner_id' => 'required|exists:partners,id',
            'service_type' => 'required|in:digital,ivr,sms',
            'service_name' => 'required',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif,svg|max:2048',
            'description' => 'nullable|array',
            'description.en' => 'nullable|string',
            'description.ar' => 'nullable|string',
            'short_code' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,pending,staging,testing',
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'The category is required.',
            'category_id.exists' => 'The selected category does not exist.',
            'sub_category_id.exists' => 'The selected sub-category does not exist.',
            'country_id.required' => 'The country is required.',
            'country_id.exists' => 'The selected country does not exist.',
            'service_type.required' => 'The service type is required.',
            'service_type.in' => 'The service type must be digital, ivr, or sms.',
            'service_name.required' => 'The service name is required.',
        ];
    }
}
