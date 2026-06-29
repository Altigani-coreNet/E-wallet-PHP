<?php

namespace App\Modules\CustomerAuth\Requests;

use App\Models\WalletTransaction;
use Illuminate\Validation\Rule;

class CustomerWalletTransactionsRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'type' => ['nullable', 'string', Rule::in(WalletTransaction::TYPES)],
            'direction' => ['nullable', 'string', Rule::in(WalletTransaction::DIRECTIONS)],
        ];
    }
}
