<?php

namespace App\Exports;

use App\Models\SpecialBilling;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SpecialBillingExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return SpecialBilling::with('member')->get()->map(function ($specialBilling) {
            return [
                'employee_id'   => $specialBilling->employee_id,
                'name'          => $specialBilling->name,
                'amortization'  => $specialBilling->amortization,
                'start_date'    => $specialBilling->start_date,
                'end_date'      => $specialBilling->end_date,
                'gross'         => $specialBilling->gross ?? 0,
                'office'        => $specialBilling->office,
            ];
        });
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
