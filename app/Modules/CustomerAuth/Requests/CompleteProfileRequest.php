<?php

namespace App\Modules\CustomerAuth\Requests;

use App\Modules\CustomerAuth\Requests\CustomerAuthFormRequest;
use Illuminate\Validation\Rule;

class CompleteProfileRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'birthDate' => ['required', 'date'],
            'gender' => ['required', 'string', Rule::in(['male', 'female', 'other'])],
            'cityId' => ['required', 'uuid'],
            'country_code' => ['nullable', 'string'],
        ];
    }
}
