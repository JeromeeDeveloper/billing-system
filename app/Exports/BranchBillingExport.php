<?php

namespace App\Exports;

use App\Models\Member;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Facades\Log;

class BranchBillingExport implements WithMultipleSheets
{
    protected $billingPeriod;
    protected $branchId;

    public function __construct($billingPeriod, $branchId)
    {
        $this->billingPeriod = $billingPeriod;
        $this->branchId = $branchId;
    }

    public function sheets(): array
    {
        return [
            'Billing Summary' => new BranchLoanDeductionsSheet($this->billingPeriod, $this->branchId),
            'SAVINGS' => new BranchRegularSavingsSheet($this->billingPeriod, $this->branchId),
            'RETIREMENT' => new BranchRetirementSavingsSheet($this->billingPeriod, $this->branchId),
            'SHARES' => new BranchSharesDeductionsSheet($this->billingPeriod, $this->branchId),
        ];
    }
}

// Each sheet class below should filter by branch_id
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class BranchLoanDeductionsSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $billingPeriod;
    protected $branchId;

    public function __construct($billingPeriod, $branchId)
    {
        $this->billingPeriod = $billingPeriod;
        $this->branchId = $branchId;
    }

    public function title(): string
    {
        return 'Billing Summary';
    }

    public function collection()
    {
        $members = Member::where('branch_id', $this->branchId)
            ->where(function ($query) {
                $query->where('account_status', 'deduction')
                    ->orWhere(function ($query) {
                        $query->where('account_status', 'non-deduction')
                            ->where(function ($q) {
                                $q->whereDate('start_hold', '>', now()->toDateString())
                                    ->orWhereDate('expiry_date', '<=', now()->toDateString());
                            });
                    });
            })
            ->whereHas('loanForecasts', function ($query) {
                $query->where('billing_period', $this->billingPeriod);
            })
            ->where('loan_balance', '>', 0)
            ->with(['loanForecasts' => function ($query) {
                $query->where('billing_period', $this->billingPeriod);
            }])
            ->get();

        return $members->map(function ($member) {
            $forecast = $member->loanForecasts->first();
            return [
                'emp_id'        => $member->emp_id ?? 'N/A',
                'amortization'  => $member->loan_balance ?? 0,
                'name'          => "{$member->fname} {$member->lname}",
                'start_date'    => $member->start_date ? $member->start_date->format('Y-m-d') : 'N/A',
                'end_date'      => $member->end_date ? $member->end_date->format('Y-m-d') : 'N/A',
                'gross'         => $member->principal ?? 0,
                'office'        => $member->area_officer ?? 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee #',
            'Amortization',
            'Name',
            'Start Date',
            'End Date',
            'Gross',
            'Office',
        ];
    }
}

class BranchRegularSavingsSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $billingPeriod;
    protected $branchId;

    public function __construct($billingPeriod, $branchId)
    {
        $this->billingPeriod = $billingPeriod;
        $this->branchId = $branchId;
    }

    public function title(): string
    {
        return 'SAVINGS';
    }

    public function collection()
    {
        $members = Member::where('branch_id', $this->branchId)
            ->whereHas('savings', function ($query) {
                $query->where('account_status', 'deduction')
                    ->whereHas('savingProduct', function($q) {
                        $q->where('product_name', 'Regular Savings');
                    });
            })
            ->with(['savings' => function ($query) {
                $query->where('account_status', 'deduction')
                    ->whereHas('savingProduct', function($q) {
                        $q->where('product_name', 'Regular Savings');
                    });
            }])
            ->get();

        return $members->map(function ($member) {
            $savings = $member->savings->first();
            return [
                'emp_id'        => $member->emp_id ?? 'N/A',
                'amortization'  => $member->loan_balance ?? 0,
                'name'          => "{$member->fname} {$member->lname}",
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee #',
            'amortization',
            'Name',
        ];
    }
}

class BranchRetirementSavingsSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $billingPeriod;
    protected $branchId;

    public function __construct($billingPeriod, $branchId)
    {
        $this->billingPeriod = $billingPeriod;
        $this->branchId = $branchId;
    }

    public function title(): string
    {
        return 'RETIREMENT';
    }

    public function collection()
    {
        $members = Member::where('branch_id', $this->branchId)
            ->whereHas('savings', function ($query) {
                $query->where('account_status', 'deduction')
                    ->whereHas('savingProduct', function($q) {
                        $q->where('product_name', 'Savings 2');
                    });
            })
            ->with(['savings' => function ($query) {
                $query->where('account_status', 'deduction')
                    ->whereHas('savingProduct', function($q) {
                        $q->where('product_name', 'Savings 2');
                    });
            }])
            ->get();

        return $members->map(function ($member) {
            $savings = $member->savings->first();
            return [
                'emp_id'        => $member->emp_id ?? 'N/A',
                'amortization'  => $member->loan_balance ?? 0,
                'name'          => "{$member->fname} {$member->lname}",
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee #',
            'amortization',
            'Name',
        ];
    }
}

class BranchSharesDeductionsSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $billingPeriod;
    protected $branchId;

    public function __construct($billingPeriod, $branchId)
    {
        $this->billingPeriod = $billingPeriod;
        $this->branchId = $branchId;
    }

    public function title(): string
    {
        return 'SHARES';
    }

    public function collection()
    {
        $members = Member::where('branch_id', $this->branchId)
            ->whereHas('shares', function ($query) {
                $query->where('account_status', 'deduction');
            })
            ->with(['shares' => function ($query) {
                $query->where('account_status', 'deduction');
            }])
            ->get();

        return $members->map(function ($member) {
            $share = $member->shares->first();
            return [
                'emp_id'        => $member->emp_id ?? 'N/A',
                'amortization'  => $member->loan_balance ?? 0,
                'name'          => "{$member->fname} {$member->lname}",
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee #',
            'amortization',
            'Name',
        ];
    }
}
