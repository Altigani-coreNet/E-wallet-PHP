<?php

namespace App\Modules\CustomerAuth\Requests;

use App\Modules\CustomerAuth\Requests\CustomerAuthFormRequest;
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
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+[1-9]\d{6,14}$/'],
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
            'password_confirmation.same' => 'password_confirmation must match password',
        ];
    }
}
