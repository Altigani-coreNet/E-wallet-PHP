<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'business_type' => ['required', 'string', 'max:50'],
            'business_address' => ['required', 'string', 'max:500'],
            'country' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'trade_license_number' => ['required', 'string', 'max:255'],
            'tax_certified_number' => ['required', 'string', 'max:255'],
            'trade_license_start_date' => ['required', 'date'],
            'trade_license_expired_date' => ['required', 'date', 'after:trade_license_start_date'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'long' => ['nullable', 'numeric', 'between:-180,180'],
            'temp_merchant_code' => ['nullable', 'string'],
            
            // File uploads
            'company_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'], // 2MB max
            'trade_license' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // 5MB max
            'tax_certification' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // 5MB max
            'user_id_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // 5MB max
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
            'business_name.required' => 'Business name is required',
            'owner_name.required' => 'Owner name is required',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email is already registered',
            'phone.required' => 'Phone number is required',
            'business_type.required' => 'Business type is required',
            'business_address.required' => 'Business address is required',
            'country.required' => 'Country is required',
            'city.required' => 'City is required',
            'trade_license_number.required' => 'Trade license number is required',
            'tax_certified_number.required' => 'Tax certified number is required',
            'trade_license_start_date.required' => 'Trade license start date is required',
            'trade_license_start_date.date' => 'Trade license start date must be a valid date',
            'trade_license_expired_date.required' => 'Trade license expired date is required',
            'trade_license_expired_date.date' => 'Trade license expired date must be a valid date',
            'trade_license_expired_date.after' => 'Trade license expired date must be after the start date',
            'lat.between' => 'Latitude must be between -90 and 90',
            'long.between' => 'Longitude must be between -180 and 180',
            
            // File upload messages
            'company_logo.mimes' => 'Company logo must be in JPG, JPEG, or PNG format',
            'company_logo.max' => 'Company logo must not exceed 2MB in size',
            'trade_license.mimes' => 'Trade license must be in PDF, JPG, JPEG, or PNG format',
            'trade_license.max' => 'Trade license must not exceed 5MB in size',
            'tax_certification.mimes' => 'Tax certification must be in PDF, JPG, JPEG, or PNG format',
            'tax_certification.max' => 'Tax certification must not exceed 5MB in size',
            'user_id_document.mimes' => 'User ID document must be in PDF, JPG, JPEG, or PNG format',
            'user_id_document.max' => 'User ID document must not exceed 5MB in size',
        ];
    }
}
