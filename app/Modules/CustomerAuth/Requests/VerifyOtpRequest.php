<?php

namespace App\Modules\CustomerAuth\Requests;

use App\Modules\CustomerAuth\Requests\CustomerAuthFormRequest;

class VerifyOtpRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'code' => ['required', 'integer', 'min:100000', 'max:999999'],
        ];
    }
}
