<?php

namespace App\Exports;

use App\Models\Member;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MemberDetailsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $branchId;
    public function __construct($branchId = null)
    {
        $this->branchId = $branchId;
    }

    public function collection()
    {
        $query = Member::with(['branch', 'loanForecasts', 'savings', 'shares']);
        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }
        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Branch', 'CID', 'Emp ID', 'First Name', 'Last Name', 'Address', 'Birth Date', 'Date Registered', 'Gender',
            'Customer Type', 'Customer Classification', 'Occupation', 'Industry', 'Area Officer', 'Area', 'Member Tagging'

        ];
    }

    public function map($member): array
    {
        $loanAccounts = $member->loanForecasts->map(function($loan) {
            return $loan->loan_acct_no . ' (Due: ' . $loan->total_due . ')';
        })->implode('; ');
        $savings = $member->savings->map(function($saving) {
            return $saving->product_code . ' (Bal: ' . $saving->current_balance . ')';
        })->implode('; ');
        $shares = $member->shares->map(function($share) {
            return $share->product_code . ' (Bal: ' . $share->current_balance . ')';
        })->implode('; ');
        return [
            $member->branch->name ?? '',
            $member->cid,
            $member->emp_id,
            $member->fname,
            $member->lname,
            $member->address,
            $member->birth_date,
            $member->date_registered,
            $member->gender,
            $member->customer_type,
            $member->customer_classification,
            $member->occupation,
            $member->industry,
            $member->area_officer,
            $member->area,
            $member->member_tagging,
          
        ];
    }
}
