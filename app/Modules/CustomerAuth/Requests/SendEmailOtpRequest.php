<?php

namespace App\Modules\CustomerAuth\Requests;

use App\Modules\CustomerAuth\Requests\CustomerAuthFormRequest;

class SendEmailOtpRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
