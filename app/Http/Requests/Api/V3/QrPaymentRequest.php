<?php

namespace App\Http\Requests\Api\V3;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class QrPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // Transaction Details
            'transactionDetails' => 'required|array',
            'transactionDetails.amount' => 'required|numeric|min:0.01',
            'transactionDetails.currency' => 'required|string',
            'transactionDetails.timestamp' => 'required|date',
            'transactionDetails.transactionType' => 'required|string|in:SALE,REFUND,VOID',
            
            // Payment Method (same structure as card payment)
            'paymentMethod' => 'required|array',
            'paymentMethod.entryMode' => 'required|string',
            'paymentMethod.panToken' => 'required|string',
            'paymentMethod.cardholderName' => 'nullable|string|max:255',
            'paymentMethod.expiryMonth' => 'nullable|string',
            'paymentMethod.expiryYear' => 'nullable|string',
            
            // Merchant Details
            'merchantDetails' => 'required|array',
            'merchantDetails.merchantId' => 'required|string',
            'merchantDetails.terminalId' => 'required|string',
            'merchantDetails.branchId' => 'nullable|string',
            
            // Additional Data (optional)
            'additionalData' => 'nullable|array',
            'additionalData.description' => 'nullable|string|max:500',
            'additionalData.orderNumber' => 'nullable|string|max:100',
            'additionalData.notes' => 'nullable|string|max:1000',
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
            'transactionDetails.required' => 'Transaction details are required',
            'transactionDetails.amount.required' => 'Transaction amount is required',
            'transactionDetails.amount.numeric' => 'Transaction amount must be a number',
            'transactionDetails.amount.min' => 'Transaction amount must be at least 0.01',
            'transactionDetails.currency.required' => 'Currency is required',
            'transactionDetails.timestamp.required' => 'Transaction timestamp is required',
            'transactionDetails.transactionType.required' => 'Transaction type is required',
            'transactionDetails.transactionType.in' => 'Transaction type must be SALE, REFUND, or VOID',
            
            'paymentMethod.required' => 'Payment method data is required',
            'paymentMethod.entryMode.required' => 'Entry mode is required',
            'paymentMethod.panToken.required' => 'PAN token is required',
            'paymentMethod.cardholderName.max' => 'Cardholder name must not exceed 255 characters',
            
            'merchantDetails.required' => 'Merchant details are required',
            'merchantDetails.merchantId.required' => 'Merchant ID is required',
            'merchantDetails.terminalId.required' => 'Terminal ID is required',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'Error_Code' => 'VALIDATION_ERROR'
            ], 422)
        );
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'transactionDetails.amount' => 'amount',
            'transactionDetails.currency' => 'currency',
            'transactionDetails.timestamp' => 'timestamp',
            'transactionDetails.transactionType' => 'transaction type',
            'paymentMethod.entryMode' => 'entry mode',
            'paymentMethod.panToken' => 'PAN token',
            'paymentMethod.cardholderName' => 'cardholder name',
            'paymentMethod.expiryMonth' => 'expiry month',
            'paymentMethod.expiryYear' => 'expiry year',
            'merchantDetails.merchantId' => 'merchant ID',
            'merchantDetails.terminalId' => 'terminal ID',
            'merchantDetails.branchId' => 'branch ID',
        ];
    }
}

