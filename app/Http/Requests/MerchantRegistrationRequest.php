<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MerchantRegistrationRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'phone' => 'required|string|unique:users,phone|max:20',
            
            // Business Information
            'owner_name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|string|max:255',
            'business_address' => 'required|string|max:500',
            
            // Documents
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'trade_license' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'tax_certification' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'user_id_document' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'temp_merchant_code' => 'required|string|starts_with:TEMP_',
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'The first name field is required.',
            'last_name.required' => 'The last name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'phone.required' => 'The phone field is required.',
            'owner_name.required' => 'The owner name field is required.',
            'business_name.required' => 'The business name field is required.',
            'business_type.required' => 'Please select a business type.',
            'business_address.required' => 'The business address field is required.',
            'company_logo.image' => 'The company logo must be an image file.',
            'company_logo.mimes' => 'The company logo must be a JPEG, PNG, JPG, or GIF file.',
            'company_logo.max' => 'The company logo must not exceed 2MB.',
            'trade_license.file' => 'The trade license must be a file.',
            'trade_license.mimes' => 'The trade license must be a PDF, JPEG, PNG, or JPG file.',
            'trade_license.max' => 'The trade license must not exceed 5MB.',
            'tax_certification.file' => 'The tax certification must be a file.',
            'tax_certification.mimes' => 'The tax certification must be a PDF, JPEG, PNG, or JPG file.',
            'tax_certification.max' => 'The tax certification must not exceed 5MB.',
            'user_id_document.file' => 'The user ID document must be a file.',
            'user_id_document.mimes' => 'The user ID document must be a PDF, JPEG, PNG, or JPG file.',
            'user_id_document.max' => 'The user ID document must not exceed 5MB.',
        ];
    }
}
