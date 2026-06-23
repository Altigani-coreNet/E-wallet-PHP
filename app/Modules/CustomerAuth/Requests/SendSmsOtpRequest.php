<?php

namespace App\Modules\CustomerAuth\Requests;

use App\Modules\CustomerAuth\Requests\CustomerAuthFormRequest;

class SendSmsOtpRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
        ];
    }
}
