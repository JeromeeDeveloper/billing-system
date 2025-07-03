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

        // Add dynamic savings product sheets only if there are members with member_tagging = 'New'
        $savingProducts = SavingProduct::whereHas('savings', function ($query) {
            $query->where('account_status', 'deduction')
                ->where('deduction_amount', '>', 0);
        })->get();

        foreach ($savingProducts as $product) {
            if (stripos($product->product_name, 'mortuary') !== false) {
                continue;
            }
            $sheet = new BranchDynamicSavingsSheet($this->billingPeriod, $this->branchId, $product->product_name);
            if ($sheet->collection()->count() > 0) {
                $sheets[$product->product_name] = $sheet;
            }
        }

        // Add dynamic share product sheets only if there are members with member_tagging = 'New'
        $shareProducts = ShareProduct::whereHas('shares', function ($query) {
            $query->where('account_status', 'deduction');
        })->get();

        foreach ($shareProducts as $product) {
            $sheet = new BranchDynamicSharesSheet($this->billingPeriod, $this->branchId, $product->product_name);
            if ($sheet->collection()->count() > 0) {
                $sheets[$product->product_name] = $sheet;
            }
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
                                $q->whereRaw("STR_TO_DATE(start_hold, '%Y-%m') > ?", [now()->format('Y-m-01')])
                                    ->orWhereRaw("STR_TO_DATE(expiry_date, '%Y-%m') <= ?", [now()->format('Y-m-01')]);
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
                // Exclude loans set to non-deduction only if today is between start_hold and expiry_date (inclusive)
                if ($loanForecast->account_status === 'non-deduction') {
                    $today = now()->toDateString();
                    $startHold = $loanForecast->start_hold ? $loanForecast->start_hold : null;
                    $expiryDate = $loanForecast->expiry_date ? $loanForecast->expiry_date : null;
                    if (
                        ($startHold && $expiryDate && $today >= $startHold && $today <= $expiryDate) ||
                        ($startHold && !$expiryDate && $today >= $startHold) ||
                        (!$startHold && $expiryDate && $today <= $expiryDate)
                    ) {
                        continue;
                    }
                } else if ($loanForecast->account_status !== 'deduction') {
                    continue;
                }
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
                'cid'        => $member->cid ?? 'N/A',
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
            'CID',
            'Employee #',
            'Amortization',
            'Name',
            'start_date',
            'end_date',
            'gross',
            'office',
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
            ->where('member_tagging', 'New')
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
            $hasValid = false;
            foreach ($member->savings as $saving) {
                if (
                    $saving->account_status === 'deduction' &&
                    $saving->deduction_amount > 0 &&
                    $saving->start_hold && $saving->expiry_date
                ) {
                    $today = now()->toDateString();
                    $startHold = \Carbon\Carbon::parse($saving->start_hold . '-01')->toDateString();
                    $expiryDate = \Carbon\Carbon::parse($saving->expiry_date . '-01')->toDateString();
                    if ($today >= $startHold && $today <= $expiryDate) {
                        $hasValid = true;
                        break;
                    }
                }
            }
            if (!$hasValid) return null;
            $amortization = 0;
            $hasNonSpecialLoans = false;
            foreach ($member->loanForecasts as $loanForecast) {
                if ($loanForecast->account_status !== 'deduction') continue;
                $productCode = explode('-', $loanForecast->loan_acct_no)[2] ?? null;
                if ($productCode) {
                    $hasSpecialProduct = $member->loanProductMembers()
                        ->whereHas('loanProduct', function($query) use ($productCode) {
                            $query->where('product_code', $productCode)
                                  ->where('billing_type', 'special');
                        })
                        ->exists();
                    $hasRegisteredProduct = $member->loanProductMembers()
                        ->whereHas('loanProduct', function($query) use ($productCode) {
                            $query->where('product_code', $productCode);
                        })
                        ->exists();
                    if ($hasRegisteredProduct && !$hasSpecialProduct) {
                        $amortization += $loanForecast->total_due ?? 0;
                        $hasNonSpecialLoans = true;
                    }
                } else {
                    continue;
                }
            }
            return [
                'cid'        => $member->cid ?? 'N/A',
                'emp_id'        => $member->emp_id ?? 'N/A',
                'amortization'  => $amortization,
                'name'          => "{$member->fname} {$member->lname}",
            ];
        })->filter();
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
            ->where('member_tagging', 'New')
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
            $hasValid = false;
            foreach ($member->shares as $share) {
                if (
                    $share->account_status === 'deduction' &&
                    $share->deduction_amount > 0 &&
                    $share->start_hold && $share->expiry_date
                ) {
                    $today = now()->toDateString();
                    $startHold = \Carbon\Carbon::parse($share->start_hold . '-01')->toDateString();
                    $expiryDate = \Carbon\Carbon::parse($share->expiry_date . '-01')->toDateString();
                    if ($today >= $startHold && $today <= $expiryDate) {
                        $hasValid = true;
                        break;
                    }
                }
            }
            if (!$hasValid) return null;
            $amortization = 0;
            $hasNonSpecialLoans = false;
            foreach ($member->loanForecasts as $loanForecast) {
                if ($loanForecast->account_status !== 'deduction') continue;
                $productCode = explode('-', $loanForecast->loan_acct_no)[2] ?? null;
                if ($productCode) {
                    $hasSpecialProduct = $member->loanProductMembers()
                        ->whereHas('loanProduct', function($query) use ($productCode) {
                            $query->where('product_code', $productCode)
                                  ->where('billing_type', 'special');
                        })
                        ->exists();
                    $hasRegisteredProduct = $member->loanProductMembers()
                        ->whereHas('loanProduct', function($query) use ($productCode) {
                            $query->where('product_code', $productCode);
                        })
                        ->exists();
                    if ($hasRegisteredProduct && !$hasSpecialProduct) {
                        $amortization += $loanForecast->total_due ?? 0;
                        $hasNonSpecialLoans = true;
                    }
                } else {
                    continue;
                }
            }
            return [
                'emp_id'        => $member->emp_id ?? 'N/A',
                'amortization'  => $amortization,
                'name'          => "{$member->fname} {$member->lname}",
            ];
        })->filter();
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
