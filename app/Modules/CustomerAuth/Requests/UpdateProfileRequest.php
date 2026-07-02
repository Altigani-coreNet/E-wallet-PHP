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
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'birthDate' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:male,female,other'],
            'cityId' => ['required', 'uuid'],
            'country_code' => ['nullable', 'string'],
            'picture' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
            'phone' => ['sometimes', 'string', 'max:32'],
            'passport' => ['nullable', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'picture.max' => 'The profile picture must not be larger than 2 MB.',
            'passport.max' => 'The passport document must not be larger than 2 MB.',
        ];
    }
}
