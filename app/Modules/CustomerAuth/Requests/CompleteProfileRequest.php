<?php

namespace App\Modules\CustomerAuth\Requests;

use Illuminate\Validation\Rule;

class CompleteProfileRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:255'],
            'nationalId' => ['required', 'string', 'max:50', Rule::unique('customers', 'national_id')],
            'email' => ['required', 'email'],
            'birthDate' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:male,female,other'],
            'cityId' => ['required', 'uuid'],
            'country_code' => ['nullable', 'string'],
            'picture' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
        ];
    }
}
