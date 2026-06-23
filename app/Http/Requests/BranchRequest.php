<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BranchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if(auth()->guard('web')->check() || !auth()->guard('admin')->check()){
            $this->merge(['merchant_id' => auth()->user()->merchant_id]);
        }

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
            'address' => 'nullable|string|max:1000',
            'merchant_id' => 'required|exists:merchants,id',
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
            'name.required' => 'Branch name is required.',
            'name.max' => 'Branch name cannot exceed 255 characters.',
            'address.max' => 'Address cannot exceed 1000 characters.',
            'merchant_id.required' => 'Merchant is required.',
            'merchant_id.exists' => 'Selected merchant does not exist.',
        ];
    }
} 