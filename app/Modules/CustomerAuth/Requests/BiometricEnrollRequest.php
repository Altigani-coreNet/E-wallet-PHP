<?php

namespace App\Modules\CustomerAuth\Requests;

use App\Modules\CustomerAuth\Models\CustomerBiometricDevice;
use Illuminate\Validation\Rule;

class BiometricEnrollRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'uuid', 'max:64'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'platform' => ['required', 'string', Rule::in([
                CustomerBiometricDevice::PLATFORM_IOS,
                CustomerBiometricDevice::PLATFORM_ANDROID,
            ])],
            'public_key' => ['required', 'string', 'min:32'],
        ];
    }
}
