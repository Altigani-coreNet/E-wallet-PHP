<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CityRequest extends FormRequest
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
            'name' => 'required|array',
            'name.en' => 'required|string|max:255',
            'name.ar' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'status' => 'required|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The city name is required.',
            'name.array' => 'The city name must be an array.',
            'name.en.required' => 'The city name in English is required.',
            'name.ar.required' => 'The city name in Arabic is required.',
            'country_id.required' => 'The country is required.',
            'country_id.exists' => 'The selected country is invalid.',
            'status.required' => 'The status is required.',
        ];
    }
}


