<?php

namespace App\Modules\Accounting\Requests;

class ImportChartOfAccountRequest extends AccountingFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ];
    }
}
