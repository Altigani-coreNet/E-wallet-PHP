<?php

namespace App\Modules\Accounting\Requests;

use App\Modules\Accounting\Services\ChartOfAccountService;
use Illuminate\Validation\Rule;

class StoreChartOfAccountRequest extends AccountingFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('chart_of_accounts', 'code')->where(
                    fn ($query) => $query->where('created_by', ChartOfAccountService::SYSTEM_OWNER)
                ),
            ],
            'sub_type' => ['required', 'integer', 'exists:chart_of_account_sub_types,id'],
            'description' => ['nullable', 'string'],
            'is_enabled' => ['nullable', 'boolean'],
        ];
    }
}
