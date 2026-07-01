<?php

namespace App\Modules\Accounting\Requests;

class ReportFilterRequest extends AccountingFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_datetime' => ['nullable', 'date'],
            'end_datetime' => ['nullable', 'date', 'after_or_equal:start_datetime'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'as_of_date' => ['nullable', 'date'],
            'account_id' => ['nullable', 'integer', 'exists:chart_of_accounts,id'],
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
        ];
    }
}
