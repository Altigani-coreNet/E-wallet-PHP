<?php

namespace App\Modules\CustomerAuth\Requests;

class UpdateProfileRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:255'],
            'birthDate' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:male,female,other'],
            'cityId' => ['required', 'uuid'],
            'country_code' => ['nullable', 'string'],
            'picture' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
        ];
    }
}
