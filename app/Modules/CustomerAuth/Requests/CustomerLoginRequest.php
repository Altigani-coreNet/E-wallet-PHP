<?php

namespace App\Modules\CustomerAuth\Requests;

class CustomerLoginRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{6,14}$/', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'phone must be a valid E.164 number',
        ];
    }
}
