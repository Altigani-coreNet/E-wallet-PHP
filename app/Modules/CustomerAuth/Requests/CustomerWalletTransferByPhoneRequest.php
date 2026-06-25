<?php

namespace App\Modules\CustomerAuth\Requests;

class CustomerWalletTransferByPhoneRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_phone' => ['required', 'string', 'max:32'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
        ];
    }
}
