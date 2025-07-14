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

class BranchBillingExport implements WithMultipleSheets
{
    protected $billingPeriod;
    protected $branchId;

    public function __construct($billingPeriod = null, $branchId = null)
    {
        if ($billingPeriod) {
            // Extract year-month from billing period (e.g., "2025-07-01" becomes "2025-07")
            $this->billingPeriod = \Carbon\Carbon::parse($billingPeriod)->format('Y-m');
        } else {
            // Fallback to current user's billing period
            $userBillingPeriod = \Illuminate\Support\Facades\Auth::user()->billing_period ?? now()->format('Y-m-01');
            $this->billingPeriod = \Carbon\Carbon::parse($userBillingPeriod)->format('Y-m');
        }

        $this->branchId = $branchId;

        // Debug: Log the billing period being used
        \Illuminate\Support\Facades\Log::info('BranchBillingExport - Billing Period: ' . $this->billingPeriod . ', Branch ID: ' . $this->branchId);
    }

    public function sheets(): array
    {
        $sheets = [
            'Billing Summary' => new BranchLoanDeductionsSheet($this->billingPeriod, $this->branchId),
        ];

        // Add dynamic savings product sheets only if there are members with member_tagging = 'New'
        $savingProducts = SavingProduct::whereHas('savings', function ($query) {
            $query->where('account_status', 'deduction');
        })->get();

        foreach ($savingProducts as $product) {
            // Skip mortuary products
            if ($product->product_type === 'mortuary') {
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
        \Illuminate\Support\Facades\Log::info('BranchBillingExport - Starting collection with billing period: ' . $this->billingPeriod . ', Branch ID: ' . $this->branchId);

        // Step 1: Check total members in branch
        $totalMembers = Member::where('branch_id', $this->branchId)->count();
        \Illuminate\Support\Facades\Log::info('BranchBillingExport - Total members in branch: ' . $totalMembers);

        // Step 2: Check members with loan balance > 0
        $membersWithLoanBalance = Member::where('branch_id', $this->branchId)->where('loan_balance', '>', 0)->count();
        \Illuminate\Support\Facades\Log::info('BranchBillingExport - Members with loan_balance > 0: ' . $membersWithLoanBalance);

        // Step 3: Check members with loan product members
        $membersWithLoanProducts = Member::where('branch_id', $this->branchId)->whereHas('loanProductMembers')->count();
        \Illuminate\Support\Facades\Log::info('BranchBillingExport - Members with loan product members: ' . $membersWithLoanProducts);

        // Step 4: Check members with loan forecasts in billing period
        $membersWithLoanForecasts = Member::where('branch_id', $this->branchId)->whereHas('loanForecasts', function ($query) {
            $query->where('billing_period', $this->billingPeriod);
        })->count();
        \Illuminate\Support\Facades\Log::info('BranchBillingExport - Members with loan forecasts in billing period: ' . $membersWithLoanForecasts);

        // Step 5: Check members with loan forecasts that have amortization_due_date in billing period
        $membersWithDueDate = Member::where('branch_id', $this->branchId)->whereHas('loanForecasts', function ($query) {
            $query->where('billing_period', $this->billingPeriod)
                  ->where(function($q) {
                      $q->whereNull('amortization_due_date')
                        ->orWhereRaw("DATE_FORMAT(amortization_due_date, '%Y-%m') = ?", [$this->billingPeriod]);
                  });
        })->count();
        \Illuminate\Support\Facades\Log::info('BranchBillingExport - Members with amortization_due_date in billing period: ' . $membersWithDueDate);

        // Debug: Check what amortization_due_date values exist
        $sampleLoanForecasts = \App\Models\LoanForecast::select('id', 'member_id', 'loan_acct_no', 'billing_period', 'amortization_due_date')
            ->where('billing_period', $this->billingPeriod)
            ->limit(10)
            ->get();

        \Illuminate\Support\Facades\Log::info('BranchBillingExport - Sample loan forecasts in billing period: ' . $sampleLoanForecasts->toJson());

        // Debug: Check all unique amortization_due_date values
        $uniqueDueDates = \App\Models\LoanForecast::whereNotNull('amortization_due_date')
            ->selectRaw('DISTINCT amortization_due_date, DATE_FORMAT(amortization_due_date, "%Y-%m") as month_year')
            ->limit(20)
            ->get();

        \Illuminate\Support\Facades\Log::info('BranchBillingExport - Sample unique amortization_due_date values: ' . $uniqueDueDates->toJson());

        // Debug: Check what billing_period values exist in loan_forecast
        $uniqueBillingPeriods = \App\Models\LoanForecast::selectRaw('DISTINCT billing_period')
            ->whereNotNull('billing_period')
            ->limit(20)
            ->get();

        \Illuminate\Support\Facades\Log::info('BranchBillingExport - Unique billing_period values in loan_forecast: ' . $uniqueBillingPeriods->toJson());

        // Debug: Check loan forecasts with amortization_due_date in July 2025
        $julyLoanForecasts = \App\Models\LoanForecast::select('id', 'member_id', 'loan_acct_no', 'billing_period', 'amortization_due_date')
            ->whereRaw("DATE_FORMAT(amortization_due_date, '%Y-%m') = ?", [$this->billingPeriod])
            ->limit(10)
            ->get();

        \Illuminate\Support\Facades\Log::info('BranchBillingExport - Loan forecasts with amortization_due_date in July 2025: ' . $julyLoanForecasts->toJson());

        // Step 6: Check account status filter
        $membersWithValidStatus = Member::where('branch_id', $this->branchId)->where(function ($query) {
            $query->where('account_status', 'deduction')
                ->orWhere(function ($query) {
                    $query->where('account_status', 'non-deduction')
                        ->where(function ($q) {
                            $q->whereRaw("STR_TO_DATE(start_hold, '%Y-%m') > ?", [now()->format('Y-m-01')])
                                ->orWhereRaw("STR_TO_DATE(expiry_date, '%Y-%m') <= ?", [now()->format('Y-m-01')]);
                        });
                });
        })->count();
        \Illuminate\Support\Facades\Log::info('BranchBillingExport - Members with valid account status: ' . $membersWithValidStatus);

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
                $query->where(function($q) {
                    $q->whereNull('amortization_due_date')
                      ->orWhereRaw("amortization_due_date <= ?", [\Carbon\Carbon::parse($this->billingPeriod . '-01')->endOfMonth()->toDateString()]);
                });
            })
            ->whereHas('loanProductMembers') // Include members who have at least one registered loan product
            ->where('loan_balance', '>', 0) // Only include members with loan balance greater than 0
            ->with(['loanForecasts', 'loanProductMembers.loanProduct'])
            ->get();

        \Illuminate\Support\Facades\Log::info('BranchBillingExport - Found ' . $members->count() . ' members after all filters');

        return $members->map(function ($member) {
            \Illuminate\Support\Facades\Log::info('Processing member: ' . $member->id . ' - ' . $member->fname . ' ' . $member->lname);

            // Debug: Show member's loan product memberships
            $loanProductMemberships = $member->loanProductMembers->map(function($lpm) {
                return [
                    'product_id' => $lpm->loan_product_id,
                    'product_code' => $lpm->loanProduct->product_code ?? 'N/A',
                    'billing_type' => $lpm->loanProduct->billing_type ?? 'N/A'
                ];
            });
            \Illuminate\Support\Facades\Log::info('Member loan product memberships: ' . $loanProductMemberships->toJson());

            // Debug: Show member's loan forecasts
            $loanForecastsDebug = $member->loanForecasts->map(function($lf) {
                $productCode = explode('-', $lf->loan_acct_no)[2] ?? null;
                return [
                    'loan_acct_no' => $lf->loan_acct_no,
                    'extracted_product_code' => $productCode,
                    'amortization_due_date' => $lf->amortization_due_date,
                    'total_due' => $lf->total_due,
                    'account_status' => $lf->account_status
                ];
            });
            \Illuminate\Support\Facades\Log::info('Member loan forecasts: ' . $loanForecastsDebug->toJson());

            $forecast = $member->loanForecasts->first();

            // Calculate amortization as sum of total_due for all loans except those marked as 'special'
            $amortization = 0;
            $hasNonSpecialLoans = false;

            // Get all loan forecasts for this member
            foreach ($member->loanForecasts as $loanForecast) {
                \Illuminate\Support\Facades\Log::info('Processing loan forecast: ' . $loanForecast->loan_acct_no . ' for member: ' . $member->id);

                // Check if this loan forecast is due on or before the end of the billing period
                $isDue = true;
                if ($loanForecast->amortization_due_date) {
                    $dueDate = \Carbon\Carbon::parse($loanForecast->amortization_due_date);
                    $billingEnd = \Carbon\Carbon::parse($this->billingPeriod . '-01')->endOfMonth();
                    $isDue = $dueDate->lte($billingEnd);
                }
                if (!$isDue) {
                    \Illuminate\Support\Facades\Log::info('Skipping loan forecast - not due on or before billing period');
                    continue;
                }

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
                        \Illuminate\Support\Facades\Log::info('Skipping loan forecast - non-deduction within hold period');
                        continue;
                    }
                } else if ($loanForecast->account_status !== 'deduction') {
                    \Illuminate\Support\Facades\Log::info('Skipping loan forecast - account status not deduction: ' . $loanForecast->account_status);
                    continue;
                }
                // Extract product code from loan_acct_no (e.g., 40102 from 0304-001-40102-000023-3)
                $productCode = explode('-', $loanForecast->loan_acct_no)[2] ?? null;

                \Illuminate\Support\Facades\Log::info('Product code extracted: ' . $productCode . ' from loan: ' . $loanForecast->loan_acct_no);

                if ($productCode) {
                    // Check if this member has a loan product with this product code that is marked as 'special'
                    $hasSpecialProduct = $member->loanProductMembers()
                        ->whereHas('loanProduct', function($query) use ($productCode) {
                            $query->where('product_code', $productCode)
                                  ->where('billing_type', 'special');
                        })
                        ->exists();

                    // Check if this member has a loan product with this product code that is marked as 'not_billed'
                    $hasNotBilledProduct = $member->loanProductMembers()
                        ->whereHas('loanProduct', function($query) use ($productCode) {
                            $query->where('product_code', $productCode)
                                  ->where('billing_type', 'not_billed');
                        })
                        ->exists();

                    // Check if this product code is registered in loan products
                    $hasRegisteredProduct = $member->loanProductMembers()
                        ->whereHas('loanProduct', function($query) use ($productCode) {
                            $query->where('product_code', $productCode);
                        })
                        ->exists();

                    \Illuminate\Support\Facades\Log::info('Product checks - Has Special: ' . ($hasSpecialProduct ? 'YES' : 'NO') . ', Has Not Billed: ' . ($hasNotBilledProduct ? 'YES' : 'NO') . ', Has Registered: ' . ($hasRegisteredProduct ? 'YES' : 'NO'));

                    // Only include loans that have registered products and are not marked as 'special' or 'not_billed'
                    if ($hasRegisteredProduct && !$hasSpecialProduct && !$hasNotBilledProduct) {
                        $amortization += $loanForecast->original_total_due ?? $loanForecast->total_due ?? 0;
                        $hasNonSpecialLoans = true;
                        \Illuminate\Support\Facades\Log::info('Added to amortization: ' . ($loanForecast->original_total_due ?? $loanForecast->total_due ?? 0));
                    } else {
                        \Illuminate\Support\Facades\Log::info('Skipping loan - not registered or is special');
                    }
                    // If loan is not registered, skip it (don't add to amortization but member is still included)
                } else {
                    // If no product code found, skip this loan (don't add to amortization)
                    \Illuminate\Support\Facades\Log::info('Skipping loan - no product code found');
                    continue;
                }
            }

            // If member has no valid loans to include in amortization, return null to exclude from export
            if (!$hasNonSpecialLoans) {
                \Illuminate\Support\Facades\Log::info('Member excluded - no valid non-special loans. Amortization: ' . $amortization . ', Has Non-Special: ' . ($hasNonSpecialLoans ? 'YES' : 'NO'));
                return null;
            }

            \Illuminate\Support\Facades\Log::info('Member included - Final amortization: ' . $amortization);

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
                    $hasNotBilledProduct = $member->loanProductMembers()
                        ->whereHas('loanProduct', function($query) use ($productCode) {
                            $query->where('product_code', $productCode)
                                  ->where('billing_type', 'not_billed');
                        })
                        ->exists();
                    $hasRegisteredProduct = $member->loanProductMembers()
                        ->whereHas('loanProduct', function($query) use ($productCode) {
                            $query->where('product_code', $productCode);
                        })
                        ->exists();
                    if ($hasRegisteredProduct && !$hasSpecialProduct && !$hasNotBilledProduct) {
                        $amortization += $loanForecast->original_total_due ?? $loanForecast->total_due ?? 0;
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
                    $hasNotBilledProduct = $member->loanProductMembers()
                        ->whereHas('loanProduct', function($query) use ($productCode) {
                            $query->where('product_code', $productCode)
                                  ->where('billing_type', 'not_billed');
                        })
                        ->exists();
                    $hasRegisteredProduct = $member->loanProductMembers()
                        ->whereHas('loanProduct', function($query) use ($productCode) {
                            $query->where('product_code', $productCode);
                        })
                        ->exists();
                    if ($hasRegisteredProduct && !$hasSpecialProduct && !$hasNotBilledProduct) {
                        $amortization += $loanForecast->original_total_due ?? $loanForecast->total_due ?? 0;
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
