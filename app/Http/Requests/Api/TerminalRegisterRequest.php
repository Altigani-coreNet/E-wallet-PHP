<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class TerminalRegisterRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'terminal_id' => 'nullable|string|max:50|unique:terminals,terminal_id',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:100',
            'serial_no' => 'nullable|string|max:100',
            'sdk_id' => 'nullable|string|max:100',
            'sdk_version' => 'nullable|string|max:50',
            'android_os' => 'nullable|string|max:50',
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
            'name.required' => 'Terminal name is required',
            'name.max' => 'Terminal name must not exceed 255 characters',
            'terminal_id.unique' => 'This terminal ID is already registered',
            'terminal_id.max' => 'Terminal ID must not exceed 50 characters',
            'brand.max' => 'Brand must not exceed 100 characters',
            'model.max' => 'Model must not exceed 100 characters',
            'manufacturer.max' => 'Manufacturer must not exceed 100 characters',
            'serial_no.max' => 'Serial number must not exceed 100 characters',
            'sdk_id.max' => 'SDK ID must not exceed 100 characters',
            'sdk_version.max' => 'SDK version must not exceed 50 characters',
            'android_os.max' => 'Android OS version must not exceed 50 characters',
            'is_active.boolean' => 'Is active must be a boolean value',
        ];
    }
}

