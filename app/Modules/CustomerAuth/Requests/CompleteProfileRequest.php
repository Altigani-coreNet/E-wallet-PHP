<?php

namespace App\Modules\CustomerAuth\Requests;

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
            'email' => ['required', 'email'],
            'birthDate' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:male,female,other'],
            'cityId' => ['required', 'uuid'],
            'country_code' => ['nullable', 'string'],
        ];
    }
}
