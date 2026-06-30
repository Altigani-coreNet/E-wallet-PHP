<?php

namespace App\Modules\CustomerAuth\Requests;

class CustomerDeleteAccountRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string'],
        ];
    }
}
