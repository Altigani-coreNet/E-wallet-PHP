<?php

namespace App\Modules\CustomerAuth\Requests;

class CustomerWalletBillPaymentRequest extends CustomerAuthFormRequest
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
            'otp_token' => ['required', 'string', 'max:255'],
            'otp' => ['required', 'integer', 'digits:6', 'between:100000,999999'],
        ];
    }
}
