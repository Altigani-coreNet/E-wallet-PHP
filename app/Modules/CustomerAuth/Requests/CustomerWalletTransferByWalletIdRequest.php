<?php

namespace App\Modules\CustomerAuth\Requests;

class CustomerWalletTransferByWalletIdRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_wallet_id' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
