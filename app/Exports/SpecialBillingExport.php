<?php

namespace App\Exports;

use App\Models\SpecialBilling;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SpecialBillingExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return SpecialBilling::all([
            'employee_id',
            'name',
            'amortization',
            'start_date',
            'end_date',
            'gross',
            'office',
        ]);
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Name',
            'Amortization',
            'Start Date',
            'End Date',
            'Gross',
            'Office',
        ];
    }
}
