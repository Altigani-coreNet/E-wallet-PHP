<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CountryRequest extends FormRequest
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
        $countryId = $this->route('id');
        
        return [
            'name' => 'required|array',
            'name.en' => 'required|string|max:255',
            'name.ar' => 'required|string|max:255',
            'short_name' => [
                'required',
                'string',
                'max:10',
                Rule::unique('countries')->ignore($countryId),
            ],
            'code' => 'nullable|string|max:10',
            'status' => 'required|boolean',
            'currency_id' => 'required|uuid|exists:currencies,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The country name is required.',
            'name.array' => 'The country name must be an array.',
            'name.en.required' => 'The country name in English is required.',
            'name.ar.required' => 'The country name in Arabic is required.',
            'short_name.required' => 'The short name is required.',
            'short_name.unique' => 'This short name is already taken.',
            'status.required' => 'The status is required.',
            'currency_id.required' => 'The currency is required.',
            'currency_id.exists' => 'The selected currency is invalid.',
        ];
    }
}


