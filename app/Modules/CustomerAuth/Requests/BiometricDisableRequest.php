<?php

namespace App\Modules\CustomerAuth\Requests;

class BiometricDisableRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'uuid', 'max:64'],
        ];
    }
}
