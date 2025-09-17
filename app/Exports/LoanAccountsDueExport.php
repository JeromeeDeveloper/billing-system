<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\LoanForecast;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LoanAccountsDueExport implements FromCollection, WithHeadings
{
    protected $billingPeriod; // format: Y-m

    public function __construct(?string $billingPeriod = null)
    {
        if ($billingPeriod) {
            $this->billingPeriod = \Carbon\Carbon::parse($billingPeriod)->format('Y-m');
        } else {
            $userBillingPeriod = Auth::user()->billing_period ?? now()->format('Y-m-01');
            $this->billingPeriod = \Carbon\Carbon::parse($userBillingPeriod)->format('Y-m');
        }
    }

    public function collection()
    {
        $billingEnd = \Carbon\Carbon::parse($this->billingPeriod . '-01')->endOfMonth();
        $today = now()->toDateString();

        $members = Member::where(function ($query) use ($today) {
                $query->where('account_status', 'deduction')
                      ->orWhere(function ($query) use ($today) {
                          $query->where('account_status', 'non-deduction')
                                ->where(function ($q) use ($today) {
                                    $q->whereRaw("(start_hold IS NOT NULL AND expiry_date IS NOT NULL AND ? NOT BETWEEN start_hold AND expiry_date)", [$today])
                                      ->orWhereRaw("(start_hold IS NOT NULL AND expiry_date IS NULL AND ? < start_hold)", [$today])
                                      ->orWhereRaw("(start_hold IS NULL AND expiry_date IS NOT NULL AND ? > expiry_date)", [$today]);
                                });
                      });
            })
            ->whereHas('loanForecasts', function ($query) use ($billingEnd) {
                $query->where(function($q) use ($billingEnd) {
                    $q->whereNull('amortization_due_date')
                      ->orWhere('amortization_due_date', '<=', $billingEnd->toDateString());
                });
            })
            ->with(['loanForecasts'])
            ->get();

        $rows = collect();

        foreach ($members as $member) {
            $memberTotalLoanBalance = (float) ($member->loan_balance ?? 0);
            $pushedAnyForMember = false;
            foreach ($member->loanForecasts as $lf) {
                // Due date filter
                $isDue = true;
                if ($lf->amortization_due_date) {
                    $dueDate = \Carbon\Carbon::parse($lf->amortization_due_date);
                    $isDue = $dueDate->lte($billingEnd);
                }
                if (!$isDue) {
                    continue;
                }

                // Account status filter (align with billing export logic)
                if ($lf->account_status === 'non-deduction') {
                    $startHold = $lf->start_hold ?: null;
                    $expiryDate = $lf->expiry_date ?: null;
                    $withinHold = (
                        ($startHold && $expiryDate && $today >= $startHold && $today <= $expiryDate) ||
                        ($startHold && !$expiryDate && $today >= $startHold) ||
                        (!$startHold && $expiryDate && $today <= $expiryDate)
                    );
                    if ($withinHold) {
                        continue;
                    }
                } elseif ($lf->account_status !== 'deduction') {
                    continue;
                }

                // Extract product code
                $productCode = explode('-', (string) $lf->loan_acct_no)[2] ?? null;
                if (!$productCode) {
                    continue;
                }

                // Validate product registration and billing type via member->loanProductMembers
                $hasSpecial = $member->loanProductMembers()
                    ->whereHas('loanProduct', function($q) use ($productCode) {
                        $q->where('product_code', $productCode)->where('billing_type', 'special');
                    })->exists();
                $hasNotBilled = $member->loanProductMembers()
                    ->whereHas('loanProduct', function($q) use ($productCode) {
                        $q->where('product_code', $productCode)->where('billing_type', 'not_billed');
                    })->exists();
                $hasRegistered = $member->loanProductMembers()
                    ->whereHas('loanProduct', function($q) use ($productCode) {
                        $q->where('product_code', $productCode);
                    })->exists();

                if (!$hasRegistered || $hasSpecial || $hasNotBilled) {
                    continue;
                }

                $rows->push([
                    'cid' => $member->cid,
                    'product_code' => $productCode,
                    'loan_account_no' => $lf->loan_acct_no,
                    'total_due' => (float) ($lf->original_total_due ?? $lf->total_due ?? 0),
                    'member_total_loan_balance' => $memberTotalLoanBalance,
                ]);
                $pushedAnyForMember = true;
            }

            // Add a per-member summary row to make totals obvious
            if ($pushedAnyForMember) {
                $rows->push([
                    'cid' => $member->cid,
                    'product_code' => 'TOTAL',
                    'loan_account_no' => '',
                    'total_due' => '',
                    'member_total_loan_balance' => $memberTotalLoanBalance,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['CID', 'Product Code', 'Loan Account #', 'Total Amort Due', 'Amortization'];
    }
}


