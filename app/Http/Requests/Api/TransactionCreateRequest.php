<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class TransactionCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'batch_no' => 'nullable|string|max:255',
            'trace_no' => 'nullable|string|max:255',
            'rrn' => 'nullable|string|max:255',
            'auth_code' => 'nullable|string|max:255',
            'mid' => 'nullable|string|max:255',
            'tid' => 'nullable|string|max:255',
            'transaction_id' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'transaction_datetime' => 'nullable|date',
            'invoice_no' => 'nullable|string|max:255',
            'card_number' => 'nullable|string|max:255',
            'expiry' => 'nullable|string|max:255',
            'method' => 'nullable|string|max:255',
            'ref_no' => 'nullable|string|max:255',
            'atc' => 'nullable|string|max:255',
            'tvr' => 'nullable|string|max:255',
            'app_name' => 'nullable|string|max:255',
            'tsi' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'sdk' => 'nullable|string|max:255',
            'terminal_id' => 'nullable|exists:terminals,id',
            'merchant_id' => 'nullable|exists:merchants,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'amount.numeric' => 'Amount must be a valid number',
            'amount.min' => 'Amount must be at least 0',
            'currency.max' => 'Currency code must not exceed 3 characters',
            'terminal_id.exists' => 'The selected terminal does not exist',
            'merchant_id.exists' => 'The selected merchant does not exist',
        ];
    }
}

