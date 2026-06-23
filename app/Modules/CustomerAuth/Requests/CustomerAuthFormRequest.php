<?php

namespace App\Modules\CustomerAuth\Requests;

use App\Support\SuccessResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class CustomerAuthFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            SuccessResponse::error(
                'Validation failed',
                422,
                $validator->errors()->toArray(),
            )
        );
    }
}
