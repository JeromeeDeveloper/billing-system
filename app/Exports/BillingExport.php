<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\SavingProduct;
use App\Models\ShareProduct;
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
        $sheets = [
            'Billing Summary' => new LoanDeductionsSheet($this->billingPeriod),
        ];

        // Add dynamic savings product sheets only if there are members with member_tagging = 'New'
        $savingProducts = SavingProduct::whereHas('savings', function ($query) {
            $query->where('account_status', 'deduction');
        })->get();

        foreach ($savingProducts as $product) {
            // Skip mortuary products
            if (stripos($product->product_name, 'mortuary') !== false) {
                continue;
            }
            $sheet = new DynamicSavingsSheet($this->billingPeriod, $product->product_name);
            if ($sheet->collection()->count() > 0) {
                $sheets[$product->product_name] = $sheet;
            }
        }

        // Add dynamic share product sheets only if there are members with member_tagging = 'New'
        $shareProducts = ShareProduct::whereHas('shares', function ($query) {
            $query->where('account_status', 'deduction');
        })->get();

        foreach ($shareProducts as $product) {
            $sheet = new DynamicSharesSheet($this->billingPeriod, $product->product_name);
            if ($sheet->collection()->count() > 0) {
                $sheets[$product->product_name] = $sheet;
            }
        }

        return $sheets;
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
                            $q->whereRaw("STR_TO_DATE(start_hold, '%Y-%m') > ?", [now()->format('Y-m-01')])
                                ->orWhereRaw("STR_TO_DATE(expiry_date, '%Y-%m') <= ?", [now()->format('Y-m-01')]);
                        });
                });
        })
            ->whereHas('loanForecasts', function ($query) {
                $query->where('billing_period', $this->billingPeriod);
            })
            ->whereHas('loanProductMembers') // Include members who have at least one registered loan product
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

                    // Check if this product code is registered in loan products
                    $hasRegisteredProduct = $member->loanProductMembers()
                        ->whereHas('loanProduct', function($query) use ($productCode) {
                            $query->where('product_code', $productCode);
                        })
                        ->exists();

                    // Only include loans that have registered products and are not marked as 'special'
                    if ($hasRegisteredProduct && !$hasSpecialProduct) {
                        $amortization += $loanForecast->total_due ?? 0;
                        $hasNonSpecialLoans = true;
                    }
                    // If loan is not registered, skip it (don't add to amortization but member is still included)
                } else {
                    // If no product code found, skip this loan (don't add to amortization)
                    continue;
                }
            }

            // If member has no valid loans to include in amortization, return null to exclude from export
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
                'office'        => $member->area ?? 'N/A',
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
            'Start Date',
            'End Date',
            'Gross',
            'Office',
        ];
    }
}

class DynamicSavingsSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $billingPeriod;
    protected $productName;

    public function __construct($billingPeriod, $productName)
    {
        $this->billingPeriod = $billingPeriod;
        $this->productName = $productName;
    }

    public function title(): string
    {
        return $this->productName;
    }

    public function collection()
    {
        $members = Member::where('member_tagging', 'New')
            ->whereHas('savings', function ($query) {
                $query->where('account_status', 'deduction')
                    ->whereHas('savingProduct', function($q) {
                        $q->where('product_name', $this->productName);
                    });
            })
            ->with(['savings' => function ($query) {
                $query->where('account_status', 'deduction')
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
            'CID',
            'Employee #',
            'Amortization',
            'Name',
        ];
    }
}

class DynamicSharesSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $billingPeriod;
    protected $productName;

    public function __construct($billingPeriod, $productName)
    {
        $this->billingPeriod = $billingPeriod;
        $this->productName = $productName;
    }

    public function title(): string
    {
        return $this->productName;
    }

    public function collection()
    {
        $members = Member::where('member_tagging', 'New')
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
            'CID',
            'Employee #',
            'Amortization',
            'Name',
        ];
    }
}
