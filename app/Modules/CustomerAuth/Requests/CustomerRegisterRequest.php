<?php

namespace App\Modules\CustomerAuth\Requests;

use Illuminate\Validation\Rules\Password;

class CustomerRegisterRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{6,14}$/', 'max:20'],
            'password' => [
                'required',
                'string',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'password_confirmation' => ['required', 'string', 'same:password'],
            'otp_token' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'phone must be a valid E.164 number',
            'password' => 'password must be at least 8 characters and include uppercase, lowercase, number, and special character',
        ];
    }
}
