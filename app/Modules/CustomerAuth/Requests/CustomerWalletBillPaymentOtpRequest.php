<?php

namespace App\Modules\CustomerAuth\Requests;

class CustomerWalletBillPaymentOtpRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'string', 'uuid'],
            'product_id' => ['required', 'string', 'uuid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'service_payload' => ['nullable', 'array'],
            'description' => ['nullable', 'string', 'max:255'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
        ];
    }
}
