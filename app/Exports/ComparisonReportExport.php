<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ComparisonReportExport implements FromArray, WithHeadings
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'CID',
            'Member Name',
            'Amortization',
            'Total Billed',
            'Loan Payment',
            'Loan Remaining',
            'Remitted Savings',
            'Remitted Shares',

        ];
    }
}
