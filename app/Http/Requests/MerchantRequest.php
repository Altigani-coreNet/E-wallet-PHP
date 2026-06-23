<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\BusinessType;

class MerchantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // You can add admin authorization here if needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $merchantId = $this->route('merchant')?->id;
        // dd($this->all());
        return [
            'name' => $merchantId ? 'nullable' : 'required|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'business_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('merchants')->ignore($merchantId),
            ],
            // 'user_email' => [
            //     'required',
            //     'email',
            //     'max:255',
            //     Rule::unique('users')->ignore($merchantId),
            // ],
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'address' => 'nullable|string',
            'business_type' => 'nullable|in:' . implode(',', array_keys(BusinessType::toArray())),
            'country_id' => 'required|exists:countries,id',
            'city_id' => 'required|exists:cities,id',
            'trade_license_number' => 'required|string|max:255',
            'tax_certified_number' => 'required|string|max:255',
            'trade_license_start_date' => 'required|date',
            'trade_license_expired_date' => 'required|date|after:trade_license_start_date',
            'is_active' => 'boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            // 'business_name' => 'required|string|max:255',
            'merchant_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('merchants')->ignore($merchantId),
            ],
            'user_id' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The merchant name is required.',
            'email.required' => 'The email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already in use.',
            'business_type.in' => 'Please select a valid business type.',
            'country_id.required' => 'The country is required.',
            'country_id.exists' => 'Please select a valid country.',
            'city_id.required' => 'The city is required.',
            'city_id.exists' => 'Please select a valid city.',
            'trade_license_number.required' => 'The trade license number is required.',
            'tax_certified_number.required' => 'The tax certified number is required.',
            'trade_license_start_date.required' => 'The trade license start date is required.',
            'trade_license_start_date.date' => 'The trade license start date must be a valid date.',
            'trade_license_expired_date.required' => 'The trade license expired date is required.',
            'trade_license_expired_date.date' => 'The trade license expired date must be a valid date.',
            'trade_license_expired_date.after' => 'The trade license expired date must be after the start date.',
            'logo.image' => 'The logo must be an image file.',
            'logo.mimes' => 'The logo must be a file of type: jpeg, png, jpg, gif.',
            'logo.max' => 'The logo may not be greater than 2MB.',
        ];
    }
}
