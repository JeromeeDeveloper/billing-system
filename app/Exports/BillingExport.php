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
            'Billing Summary' => new LoanDeductionsSheet($this->billingPeriod),
            'SAVINGS' => new RegularSavingsSheet($this->billingPeriod),
            'RETIREMENT' => new RetirementSavingsSheet($this->billingPeriod),
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
            }, 'loanProductMembers.loanProduct'])
            ->get();

        return $members->map(function ($member) {
            $forecast = $member->loanForecasts->first();

            // Calculate amortization as sum of total_due for all loans except those marked as 'special'
            $amortization = 0;
            $hasNonSpecialLoans = false;

            // Get all loan forecasts for this member
            foreach ($member->loanForecasts as $loanForecast) {
                // Extract product code from loan_acct_no (e.g., 40102 from 0304-001-40102-000023-3)
                $productCode = explode('-', $loanForecast->loan_acct_no)[2] ?? null;

                if ($productCode) {
                    // Check if this member has a loan product with this product code that is marked as 'special'
                    $hasSpecialProduct = $member->loanProductMembers()
                        ->whereHas('loanProduct', function($query) use ($productCode) {
                            $query->where('product_code', $productCode)
                                  ->where('billing_type', 'special');
                        })
                        ->exists();

                    // Include all loans except those marked as 'special'
                    if (!$hasSpecialProduct) {
                        $amortization += $loanForecast->total_due ?? 0;
                        $hasNonSpecialLoans = true;
                    }
                } else {
                    // If no product code found, include the loan (default behavior)
                    $amortization += $loanForecast->total_due ?? 0;
                    $hasNonSpecialLoans = true;
                }
            }

            // If member only has special loans (or no loans), return null to exclude from export
            if (!$hasNonSpecialLoans) {
                return null;
            }

            return [
                'emp_id'        => $member->emp_id ?? 'N/A',
                'amortization'  => $amortization,
                'name'          => "{$member->fname} {$member->lname}",
                'start_date'    => $member->start_date ? $member->start_date->format('Y-m-d') : 'N/A',
                'end_date'      => $member->end_date ? $member->end_date->format('Y-m-d') : 'N/A',
                'gross'         => $member->regular_principal ?? 0,
                'office'        => $member->area_officer ?? 'N/A',
            ];
        })->filter(); // Remove null entries
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

class RegularSavingsSheet implements FromCollection, WithHeadings, WithTitle
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

class RetirementSavingsSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $billingPeriod;

    public function __construct($billingPeriod)
    {
        $this->billingPeriod = $billingPeriod;
    }

    public function title(): string
    {
        return 'RETIREMENT';
    }

    public function collection()
    {
        $members = Member::whereHas('savings', function ($query) {
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
