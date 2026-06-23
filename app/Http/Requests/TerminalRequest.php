<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Terminal;
use Illuminate\Validation\Rule;

class TerminalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('serial_number') && !$this->filled('serial_no')) {
            $this->merge([
                'serial_no' => $this->input('serial_number'),
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
        $terminalRoute = $this->route('terminal') ?? $this->route('id');
        $terminalId = $terminalRoute instanceof Terminal ? $terminalRoute->getKey() : $terminalRoute;
        $nameRule = $this->isMethod('post') ? 'required|string|max:255' : 'sometimes|string|max:255';

        $terminalIdRule = [
            'nullable',
            'string',
            'max:50',
        ];

        $uniqueRule = Rule::unique('terminals', 'terminal_id');

        if ($terminalId) {
            $uniqueRule->ignore($terminalId);
        }

        $terminalIdRule[] = $uniqueRule;

        return [
            'name' => $nameRule,
            'terminal_id' => $terminalIdRule,
            'merchant_id' => 'nullable|uuid', // UUID, no FK validation (references SoftPos)
            'branch_id' => 'nullable|uuid', // UUID, no FK validation (references SoftPos)
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:100',
            'serial_no' => 'nullable|string|max:100',
            'sdk_id' => 'nullable|string|max:100',
            'sdk_version' => 'nullable|string|max:50',
            'android_os' => 'nullable|string|max:50',
            'add_type' => 'nullable|string|in:auto,static',
            'terminal_status' => 'nullable|string|in:online,offline,testing,maintenance',
            'device_id' => 'nullable|string|max:100',
            'is_active' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $allowedValues = [
                        true,
                        false,
                        1,
                        0,
                        '1',
                        '0',
                        'true',
                        'false',
                        'active',
                        'inactive',
                    ];

                    if (!in_array($value, $allowedValues, true)) {
                        $fail('The is active field must be one of: true, false, active, inactive.');
                    }
                },
            ],
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
            'name.required' => 'Terminal name is required',
            'name.max' => 'Terminal name must not exceed 255 characters',
            'terminal_id.unique' => 'This terminal ID is already registered',
            'terminal_id.max' => 'Terminal ID must not exceed 50 characters',
            'brand.max' => 'Brand must not exceed 100 characters',
            'model.max' => 'Model must not exceed 100 characters',
            'manufacturer.max' => 'Manufacturer must not exceed 100 characters',
            'serial_no.max' => 'Serial number must not exceed 100 characters',
            'sdk_id.max' => 'SDK ID must not exceed 100 characters',
            'sdk_version.max' => 'SDK version must not exceed 50 characters',
            'android_os.max' => 'Android OS version must not exceed 50 characters',
            'add_type.in' => 'Add type must be either auto or static',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // If terminal_id is provided, check if it's unique
            if ($this->filled('terminal_id')) {
                $terminalId = $this->input('terminal_id');
                $terminalRoute = $this->route('terminal') ?? $this->route('id');
                $excludeId = $terminalRoute instanceof Terminal ? $terminalRoute->getKey() : $terminalRoute;
                
                if (Terminal::where('terminal_id', $terminalId)
                    ->when($excludeId, fn($query) => $query->where('id', '!=', $excludeId))
                    ->exists()) {
                    $validator->errors()->add('terminal_id', 'This terminal ID is already registered');
                }
            }
        });
    }
}

