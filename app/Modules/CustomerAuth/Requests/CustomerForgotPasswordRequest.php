<?php

namespace App\Modules\CustomerAuth\Requests;

class CustomerForgotPasswordRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{6,14}$/', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'phone must be a valid E.164 number',
        ];
    }
}
