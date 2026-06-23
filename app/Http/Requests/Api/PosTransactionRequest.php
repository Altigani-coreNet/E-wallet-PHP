<?php

namespace App\Http\Requests\Api;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class PosTransactionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // dd($this->service_id);
        // $this->merge(['service_id' => '019da4e8-7823-73e6-b533-15a1fb8c9e57']);
        if ($this->filled('service_id')) {
            $service = Service::query()->find($this->service_id);
            if (! $service) {       
                throw ValidationException::withMessages([
                    'service_id' => ['Service not found.'],
                ]);
            }
            $this->merge([
                'service_id' => $service->id,
                'service_category_id' => $service->category_id,
                'partner_id' => $service->partner_id,
            ]);
        }else{
            $tapToPayService = Service::query()
            ->active()
            ->where('id', '019dcdfb-d84a-715f-9aee-9c181f861e92')
            ->first();

        if (! $tapToPayService) {
            throw ValidationException::withMessages([
                'service_id' => ['Tap to Pay service is not configured. Please seed FastPos service first.'],
            ]);
        }

        $this->merge([
            'service_id' => $tapToPayService->id,
            'service_category_id' => $tapToPayService->category_id,
            'partner_id' => $tapToPayService->partner_id,
        ]);
        }

        
    }

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
            'transactionDetails' => 'required|array',
            'transactionDetails.amount' => 'required|numeric|min:0',
            'transactionDetails.currency' => 'required',
            'transactionDetails.timestamp' => 'required|date',
            'transactionDetails.transactionType' => 'required|string',
            
            'paymentMethod' => 'required|array',
            'paymentMethod.entryMode' => 'required|string',
            'paymentMethod.panToken' => 'required|string',
            'paymentMethod.cardholderName' => 'required|string',
            'paymentMethod.expiryMonth' => 'required|string',
            'paymentMethod.expiryYear' => 'required|string',
            'paymentMethod.paymentChannel' => 'nullable|string',
            
            'securityData' => 'required|array',
            'securityData.emvData' => 'nullable|string',
            'securityData.pinBlock' => 'nullable|string',
            'securityData.ksn' => 'nullable|string',

            'merchantDetails' => 'required|array',
            'merchantDetails.merchantId' => 'nullable|string',
            'merchantDetails.terminalId' => 'nullable|string',

            'partner_id' => 'nullable|uuid',
            'service_category_id' => 'nullable|uuid',
            'service_id' => 'nullable|uuid',
            'product_id' => 'nullable|uuid',
            'service_payload' => 'nullable|array',
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
            'transactionDetails.amount.min' => 'Transaction amount must be at least 0',
            'transactionDetails.currency.required' => 'Currency is required',
            'transactionDetails.currency.max' => 'Currency code must not exceed 3 characters',
            'transactionDetails.timestamp.required' => 'Transaction timestamp is required',
            'transactionDetails.timestamp.date' => 'Transaction timestamp must be a valid date',
            'transactionDetails.transactionType.required' => 'Transaction type is required',
            
            'paymentMethod.required' => 'Payment method details are required',
            'paymentMethod.entryMode.required' => 'Card entry mode is required',
            'paymentMethod.panToken.required' => 'Card number is required',
            'paymentMethod.cardholderName.required' => 'Cardholder name is required',
            'paymentMethod.expiryMonth.required' => 'Card expiry month is required',
            'paymentMethod.expiryYear.required' => 'Card expiry year is required',
            
            'securityData.required' => 'Security data is required',
        ];
    }
}

