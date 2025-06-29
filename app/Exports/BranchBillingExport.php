<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\SavingProduct;
use App\Models\ShareProduct;
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
        $sheets = [
            'Billing Summary' => new BranchLoanDeductionsSheet($this->billingPeriod, $this->branchId),
        ];

        // Add dynamic savings product sheets
        $savingProducts = SavingProduct::whereHas('savings', function ($query) {
            $query->where('account_status', 'deduction')
                ->where('deduction_amount', '>', 0);
        })->get();

        foreach ($savingProducts as $product) {
            // Skip mortuary products
            if (stripos($product->product_name, 'mortuary') !== false) {
                continue;
            }

            $sheets[$product->product_name] = new BranchDynamicSavingsSheet($this->billingPeriod, $this->branchId, $product->product_name);
        }

        // Add dynamic share product sheets
        $shareProducts = ShareProduct::whereHas('shares', function ($query) {
            $query->where('account_status', 'deduction');
        })->get();

        foreach ($shareProducts as $product) {
            $sheets[$product->product_name] = new BranchDynamicSharesSheet($this->billingPeriod, $this->branchId, $product->product_name);
        }

        return $sheets;
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
            }, 'loanProductMembers.loanProduct'])
            ->get();

        return $members->map(function ($member) {
            $forecast = $member->loanForecasts->first();

            // Calculate amortization as sum of total_due for all loans except those marked as 'special'
            $amortization = 0;
            $hasNonSpecialLoans = false;

            // Get all loan forecasts for this member
            foreach ($member->loanForecasts as $loanForecast) {
                // Extract product code from loan_acct_no (e.g., 40102 from 0304-001-40102-000025-9)
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
        ];
    }
}

class BranchDynamicSavingsSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $billingPeriod;
    protected $branchId;
    protected $productName;

    public function __construct($billingPeriod, $branchId, $productName)
    {
        $this->billingPeriod = $billingPeriod;
        $this->branchId = $branchId;
        $this->productName = $productName;
    }

    public function title(): string
    {
        return $this->productName;
    }

    public function collection()
    {
        $members = Member::where('branch_id', $this->branchId)
            ->whereHas('savings', function ($query) {
                $query->where('account_status', 'deduction')
                    ->where('deduction_amount', '>', 0)
                    ->whereHas('savingProduct', function($q) {
                        $q->where('product_name', $this->productName);
                    });
            })
            ->with(['savings' => function ($query) {
                $query->where('account_status', 'deduction')
                    ->where('deduction_amount', '>', 0)
                    ->whereHas('savingProduct', function($q) {
                        $q->where('product_name', $this->productName);
                    });
            }])
            ->get();

        return $members->map(function ($member) {
            // Calculate amortization as sum of total_due for all loans except those marked as 'special'
            $amortization = 0;
            $hasNonSpecialLoans = false;

            // Get all loan forecasts for this member
            foreach ($member->loanForecasts as $loanForecast) {
                // Extract product code from loan_acct_no (e.g., 40102 from 0304-001-40102-000025-9)
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

            return [
                'emp_id'        => $member->emp_id ?? 'N/A',
                'amortization'  => $amortization,
                'name'          => "{$member->fname} {$member->lname}",
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee #',
            'Amortization',
            'Name',
        ];
    }
}

class BranchDynamicSharesSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $billingPeriod;
    protected $branchId;
    protected $productName;

    public function __construct($billingPeriod, $branchId, $productName)
    {
        $this->billingPeriod = $billingPeriod;
        $this->branchId = $branchId;
        $this->productName = $productName;
    }

    public function title(): string
    {
        return $this->productName;
    }

    public function collection()
    {
        $members = Member::where('branch_id', $this->branchId)
            ->whereHas('shares', function ($query) {
                $query->where('account_status', 'deduction')
                    ->where('product_name', $this->productName);
            })
            ->with(['shares' => function ($query) {
                $query->where('account_status', 'deduction')
                    ->where('product_name', $this->productName);
            }])
            ->get();

        return $members->map(function ($member) {
            // Calculate amortization as sum of total_due for all loans except those marked as 'special'
            $amortization = 0;
            $hasNonSpecialLoans = false;

            // Get all loan forecasts for this member
            foreach ($member->loanForecasts as $loanForecast) {
                // Extract product code from loan_acct_no (e.g., 40102 from 0304-001-40102-000025-9)
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

            return [
                'emp_id'        => $member->emp_id ?? 'N/A',
                'amortization'  => $amortization,
                'name'          => "{$member->fname} {$member->lname}",
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee #',
            'Amortization',
            'Name',
        ];
    }
}
