<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ServiceFeesTemplateExport implements FromArray, WithHeadings
{
    /**
     * @return array
     */
    public function array(): array
    {
        return [
            [
                'Sample Service Fee 1',
                'Transaction Fee',
                '2.50'
            ],
            [
                'Sample Service Fee 2',
                'Processing Fee',
                '1.00'
            ],
            [
                'Sample Service Fee 3',
                'Monthly Fee',
                '25.00'
            ]
        ];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Name',
            'Type',
            'Fees'
        ];
    }
}
