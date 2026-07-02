<?php

namespace App\Modules\CustomerAuth\Requests;

class BiometricChallengeRequest extends CustomerAuthFormRequest
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
