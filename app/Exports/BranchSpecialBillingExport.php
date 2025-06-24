<?php

namespace App\Exports;

use App\Models\SpecialBilling;
use App\Models\Member;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BranchSpecialBillingExport implements FromCollection, WithHeadings
{
    protected $branch_id;

    public function __construct($branch_id)
    {
        $this->branch_id = $branch_id;
    }

    public function collection()
    {
        // Assuming SpecialBilling has a member_id or office/branch reference
        // If office is a branch code, you may need to join with the branches table or filter by office
        return SpecialBilling::whereHas('member', function($q) {
            $q->where('branch_id', $this->branch_id);
        })->get([
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
