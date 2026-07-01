<?php

namespace App\Modules\CustomerAuth\Requests;

class ConfirmEmailVerificationRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'code' => ['required', 'integer', 'digits:6', 'between:100000,999999'],
        ];
    }
}
