<?php

namespace App\Modules\CustomerAuth\Requests;

class CustomerWalletResolveRecipientRequest extends CustomerAuthFormRequest
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
