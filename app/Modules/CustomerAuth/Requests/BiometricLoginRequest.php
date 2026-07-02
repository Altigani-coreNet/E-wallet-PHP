<?php

namespace App\Modules\CustomerAuth\Requests;

class BiometricLoginRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'uuid', 'max:64'],
            'challenge_token' => ['required', 'string'],
            'signature' => ['required', 'string', 'min:8'],
        ];
    }
}
