<?php

namespace App\Modules\CustomerAuth\Requests;

use App\Support\CustomerActivityActions;
use Illuminate\Validation\Rule;

class CustomerActivityIndexRequest extends CustomerAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['sometimes', 'string', Rule::in(CustomerActivityActions::ALL)],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.in' => 'Invalid action filter. Allowed values: '.CustomerActivityActions::allowedListForMessage(),
        ];
    }
}
