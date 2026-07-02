<?php

namespace App\Modules\CustomerAuth\Requests;

use Illuminate\Validation\Rules\Password;

class CustomerChangePasswordRequestRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'password_confirmation' => ['required', 'string', 'same:password'],
        ];
    }

    public function messages(): array
    {
        return [
            'password' => 'password must be at least 8 characters and include uppercase, lowercase, number, and special character',
        ];
    }
}
