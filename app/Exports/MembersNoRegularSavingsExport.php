<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\Savings;
use App\Models\SavingProduct;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;

class MembersNoRegularSavingsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $branchId;

    public function __construct($branchId = null)
    {
        $this->branchId = $branchId;
    }

    public function collection()
    {
        $query = Member::with(['branch', 'savings.savingProduct']);

        // Filter by branch if specified
        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        $members = $query->get();

        // Filter members who have no regular savings products
        $membersWithNoRegularSavings = $members->filter(function ($member) {
            // Get all savings accounts for this member
            $savings = $member->savings;

            // Check if member has any regular savings products
            $hasRegularSavings = $savings->some(function ($saving) {
                return $saving->savingProduct && $saving->savingProduct->product_type === 'regular';
            });

            // Return true if member has NO regular savings
            return !$hasRegularSavings;
        });

        return $membersWithNoRegularSavings;
    }

    public function headings(): array
    {
        return [
            'Branch',
            'CID',
            'Employee ID',
            'First Name',
            'Last Name',

            'Member Tagging',




            'Savings Product Types'
        ];
    }

    public function map($member): array
    {
        // Get savings account information
        $savingsAccounts = $member->savings;
        $totalSavingsAccounts = $savingsAccounts->count();

        $savingsAccountNumbers = $savingsAccounts->pluck('account_number')->implode(', ');
        $savingsProductTypes = $savingsAccounts->map(function ($saving) {
            return $saving->savingProduct ? $saving->savingProduct->product_type : 'Unknown';
        })->implode(', ');

        return [
            $member->branch ? $member->branch->name : 'N/A',
            $member->cid ?? '',
            $member->emp_id ?? '',
            $member->fname ?? '',
            $member->lname ?? '',

            $member->member_tagging ?? '',

        

            $savingsProductTypes
        ];
    }
}
