<?php

namespace App\Modules\CustomerAuth\Requests;

class CustomerWalletQueryRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
        ];
    }
}
