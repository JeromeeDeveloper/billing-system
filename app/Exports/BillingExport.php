<?php

namespace App\Exports;

use App\Models\Member;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class BillingExport implements WithMultipleSheets
{
    protected $billingPeriod;

    public function __construct()
    {
        $this->billingPeriod = Auth::user()->billing_period;
    }

    public function sheets(): array
    {
        return [
            'Loan Deductions' => new LoanDeductionsSheet($this->billingPeriod),
            'SAVINGS' => new SavingsDeductionsSheet($this->billingPeriod),
            'SHARES' => new SharesDeductionsSheet($this->billingPeriod),
        ];
    }
}

class LoanDeductionsSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $billingPeriod;

    public function __construct($billingPeriod)
    {
        $this->billingPeriod = $billingPeriod;
    }

    public function title(): string
    {
        return 'Billing Summary';
    }

    public function collection()
    {
        $members = Member::where(function ($query) {
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
            ->where('loan_balance', '>', 0) // Only include members with loan balance greater than 0
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

class SavingsDeductionsSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $billingPeriod;

    public function __construct($billingPeriod)
    {
        $this->billingPeriod = $billingPeriod;
    }

    public function title(): string
    {
        return 'SAVINGS';
    }

    public function collection()
    {
        $members = Member::whereHas('savings', function ($query) {
            $query->where('account_status', 'deduction');
        })
        ->with(['savings' => function ($query) {
            $query->where('account_status', 'deduction');
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

class SharesDeductionsSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $billingPeriod;

    public function __construct($billingPeriod)
    {
        $this->billingPeriod = $billingPeriod;
    }

    public function title(): string
    {
        return 'SHARES';
    }

    public function collection()
    {
        $members = Member::whereHas('shares', function ($query) {
            $query->where('account_status', 'deduction');
        })
        ->with(['shares' => function ($query) {
            $query->where('account_status', 'deduction');
        }])
        ->get();

        return $members->map(function ($member) {
            $shares = $member->shares->first();

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
