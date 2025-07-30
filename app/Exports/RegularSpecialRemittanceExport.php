<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\LoanForecast;
use App\Models\LoanProduct;

class RegularSpecialRemittanceExport implements WithMultipleSheets
{
    protected $regularRemittances;
    protected $specialRemittances;
    protected $loansSavingsPreviewPaginated;
    protected $sharesPreviewPaginated;
    protected $billingPeriod;
    protected $isBranch;
    protected $branchId;

    public function __construct($regularRemittances, $specialRemittances, $billingPeriod, $loansSavingsPreviewPaginated = null, $sharesPreviewPaginated = null, $isBranch = false, $branchId = null)
    {
        $this->regularRemittances = $regularRemittances;
        $this->specialRemittances = $specialRemittances;
        $this->loansSavingsPreviewPaginated = $loansSavingsPreviewPaginated;
        $this->sharesPreviewPaginated = $sharesPreviewPaginated;
        $this->billingPeriod = $billingPeriod;
        $this->isBranch = $isBranch;
        $this->branchId = $branchId;
    }

    public function sheets(): array
    {
        return [
            new ConsolidatedRemittanceSheetExport(
                $this->regularRemittances,
                $this->specialRemittances,
                $this->loansSavingsPreviewPaginated,
                $this->sharesPreviewPaginated,
                $this->billingPeriod,
                $this->isBranch,
                $this->branchId
            ),
            new RemittanceSheetExport($this->regularRemittances, $this->billingPeriod, 'Regular Billing'),
            new RemittanceSheetExport($this->specialRemittances, $this->billingPeriod, 'Special Billing'),
        ];
    }
}

class ConsolidatedRemittanceSheetExport implements FromArray, WithHeadings, WithTitle
{
    protected $regularRemittances;
    protected $specialRemittances;
    protected $loansSavingsPreviewPaginated;
    protected $sharesPreviewPaginated;
    protected $billingPeriod;
    protected $isBranch;
    protected $branchId;

    public function __construct($regularRemittances, $specialRemittances, $loansSavingsPreviewPaginated, $sharesPreviewPaginated, $billingPeriod, $isBranch = false, $branchId = null)
    {
        $this->regularRemittances = $regularRemittances;
        $this->specialRemittances = $specialRemittances;
        $this->loansSavingsPreviewPaginated = $loansSavingsPreviewPaginated;
        $this->sharesPreviewPaginated = $sharesPreviewPaginated;
        $this->billingPeriod = $billingPeriod;
        $this->isBranch = $isBranch;
        $this->branchId = $branchId;
    }

    public function title(): string
    {
        return $this->isBranch ? 'Branch Consolidated Remittance' : 'Admin Consolidated Remittance';
    }

    public function headings(): array
    {
        return [
            ['Member Name', 'Type', 'Remitted Loans', 'Remitted Savings', 'Remitted Shares', 'Total Remitted', 'Total Billed', 'Remaining Balance']
        ];
    }

    public function array(): array
    {
        $consolidatedData = $this->consolidateData();
        $rows = [];
        $totalRemittedLoans = 0;
        $totalRemittedSavings = 0;
        $totalRemittedShares = 0;
        $totalRemitted = 0;
        $totalBilled = 0;
        $totalRemaining = 0;

        foreach ($consolidatedData as $memberId => $data) {
            // Skip upload preview only records
            if ($data['billing_type'] === 'preview') {
                continue;
            }

            $totalRemittedLoans += $data['remitted_loans'];
            $totalRemittedSavings += $data['remitted_savings'];
            $totalRemittedShares += $data['remitted_shares'];
            $totalRemitted += $data['total_remitted'];
            $totalBilled += $data['total_billed'];
            $totalRemaining += $data['remaining_balance'];

            $rows[] = [
                $data['member_name'],
                ucfirst($data['billing_type']),
                $data['remitted_loans'],
                $data['remitted_savings'],
                $data['remitted_shares'],
                $data['total_remitted'],
                $data['total_billed'],
                $data['remaining_balance']
            ];
        }

        // Totals row
        $rows[] = [
            'Total',
            '',
            $totalRemittedLoans,
            $totalRemittedSavings,
            $totalRemittedShares,
            $totalRemitted,
            $totalBilled,
            $totalRemaining
        ];

        return $rows;
    }

    private function consolidateData()
    {
        $consolidatedData = [];

        // Process Regular Billing data
        if ($this->regularRemittances && $this->regularRemittances->count() > 0) {
            foreach ($this->regularRemittances as $remit) {
                // Handle both admin (object) and branch (model) data structures
                $memberId = is_object($remit) ? $remit->member_id : $remit['member_id'];
                $memberName = is_object($remit) ? ($remit->member->full_name ?? 'N/A') : ($remit['member']['full_name'] ?? 'N/A');
                $remittedAmount = is_object($remit) ? ($remit->remitted_amount ?? 0) : ($remit['remitted_amount'] ?? 0);
                $remittedSavings = is_object($remit) ? ($remit->remitted_savings ?? 0) : ($remit['remitted_savings'] ?? 0);
                $remittedShares = is_object($remit) ? ($remit->remitted_shares ?? 0) : ($remit['remitted_shares'] ?? 0);

                // Convert from CID to member_id for admin data
                $actualMemberId = $memberId;
                if (is_object($remit) && isset($remit->billing_type)) { // This condition identifies admin data
                    $member = \App\Models\Member::where('cid', $memberId)->first();
                    $actualMemberId = $member ? $member->id : null;
                }

                // For branch, use the LoanRemittance's loanForecast relationship
                if ($this->isBranch && is_object($remit) && $remit->loanForecast) {
                    $forecast = $remit->loanForecast;
                    $productCode = null;
                    if ($forecast->loan_acct_no) {
                        $segments = explode('-', $forecast->loan_acct_no);
                        $productCode = $segments[2] ?? null;
                    }
                    $product = $productCode ? LoanProduct::where('product_code', $productCode)->first() : null;
                    $billedTotal = 0;
                    if ($product && $product->billing_type === 'regular') {
                        $billedTotal = $forecast->total_due;
                    }
                } else {
                    // For admin, query all LoanForecast records
                    $loanForecasts = LoanForecast::where('member_id', $actualMemberId)
                        ->where('billing_period', $this->billingPeriod)
                        ->get();

                    $billedTotal = 0;
                    foreach ($loanForecasts as $forecast) {
                        $productCode = null;
                        if ($forecast->loan_acct_no) {
                            $segments = explode('-', $forecast->loan_acct_no);
                            $productCode = $segments[2] ?? null;
                        }
                        $product = $productCode ? LoanProduct::where('product_code', $productCode)->first() : null;
                        if ($product && $product->billing_type === 'regular') {
                            $billedTotal += $forecast->total_due;
                        }
                    }
                }

                $consolidatedData[$memberId] = [
                    'member_id' => $memberId,
                    'member_name' => $memberName,
                    'billing_type' => 'regular',
                    'remitted_loans' => $remittedAmount,
                    'remitted_savings' => $remittedSavings,
                    'remitted_shares' => $remittedShares,
                    'total_remitted' => $remittedAmount + $remittedSavings + $remittedShares,
                    'total_billed' => $billedTotal,
                    'remaining_balance' => $billedTotal - $remittedAmount
                ];
            }
        }

        // Process Special Billing data
        if ($this->specialRemittances && $this->specialRemittances->count() > 0) {
            foreach ($this->specialRemittances as $remit) {
                // Handle both admin (object) and branch (model) data structures
                $memberId = is_object($remit) ? $remit->member_id : $remit['member_id'];
                $memberName = is_object($remit) ? ($remit->member->full_name ?? 'N/A') : ($remit['member']['full_name'] ?? 'N/A');
                $remittedAmount = is_object($remit) ? ($remit->remitted_amount ?? 0) : ($remit['remitted_amount'] ?? 0);
                $remittedSavings = is_object($remit) ? ($remit->remitted_savings ?? 0) : ($remit['remitted_savings'] ?? 0);
                $remittedShares = is_object($remit) ? ($remit->remitted_shares ?? 0) : ($remit['remitted_shares'] ?? 0);

                // Convert from CID to member_id for admin data
                $actualMemberId = $memberId;
                if (is_object($remit) && isset($remit->billing_type)) { // This condition identifies admin data
                    $member = \App\Models\Member::where('cid', $memberId)->first();
                    $actualMemberId = $member ? $member->id : null;
                }

                // For branch, use the LoanRemittance's loanForecast relationship
                if ($this->isBranch && is_object($remit) && $remit->loanForecast) {
                    $forecast = $remit->loanForecast;
                    $productCode = null;
                    if ($forecast->loan_acct_no) {
                        $segments = explode('-', $forecast->loan_acct_no);
                        $productCode = $segments[2] ?? null;
                    }
                    $product = $productCode ? LoanProduct::where('product_code', $productCode)->first() : null;
                    $billedTotal = 0;
                    if ($product && $product->billing_type === 'special') {
                        $billedTotal = $forecast->total_due;
                    }
                } else {
                    // For admin, query all LoanForecast records
                    $loanForecasts = LoanForecast::where('member_id', $actualMemberId)
                        ->where('billing_period', $this->billingPeriod)
                        ->get();

                    $billedTotal = 0;
                    foreach ($loanForecasts as $forecast) {
                        $productCode = null;
                        if ($forecast->loan_acct_no) {
                            $segments = explode('-', $forecast->loan_acct_no);
                            $productCode = $segments[2] ?? null;
                        }
                        $product = $productCode ? LoanProduct::where('product_code', $productCode)->first() : null;
                        if ($product && $product->billing_type === 'special') {
                            $billedTotal += $forecast->total_due;
                        }
                    }
                }

                $consolidatedData[$memberId] = [
                    'member_id' => $memberId,
                    'member_name' => $memberName,
                    'billing_type' => 'special',
                    'remitted_loans' => $remittedAmount,
                    'remitted_savings' => $remittedSavings,
                    'remitted_shares' => $remittedShares,
                    'total_remitted' => $remittedAmount + $remittedSavings + $remittedShares,
                    'total_billed' => $billedTotal,
                    'remaining_balance' => $billedTotal - $remittedAmount
                ];
            }
        }

        // Process Loans & Savings Preview data and merge with existing records
        if ($this->loansSavingsPreviewPaginated && $this->loansSavingsPreviewPaginated->count() > 0) {
            foreach ($this->loansSavingsPreviewPaginated as $row) {
                // Handle both array and object data structures
                $memberId = is_array($row) ? ($row['member_id'] ?? null) : ($row->member_id ?? null);
                if (!$memberId) continue;

                $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                $loans = is_array($row) ? (is_numeric($row['loans']) ? $row['loans'] : 0) : (is_numeric($row->loans) ? $row->loans : 0);
                $savings = is_array($row) ? (is_numeric($row['savings']) ? $row['savings'] : 0) : (is_numeric($row->savings) ? $row->savings : 0);

                if (isset($consolidatedData[$memberId])) {
                    // Merge with existing billing data
                    $consolidatedData[$memberId]['remitted_loans'] += $loans;
                    $consolidatedData[$memberId]['remitted_savings'] += $savings;
                    $consolidatedData[$memberId]['total_remitted'] += $loans + $savings;
                    $consolidatedData[$memberId]['remaining_balance'] -= ($loans + $savings);
                } else {
                    // Create new record for preview only
                    $consolidatedData[$memberId] = [
                        'member_id' => $memberId,
                        'member_name' => $name,
                        'billing_type' => 'preview',
                        'remitted_loans' => $loans,
                        'remitted_savings' => $savings,
                        'remitted_shares' => 0,
                        'total_remitted' => $loans + $savings,
                        'total_billed' => 0,
                        'remaining_balance' => 0
                    ];
                }
            }
        }

        // Process Shares Preview data and merge with existing records
        if ($this->sharesPreviewPaginated && $this->sharesPreviewPaginated->count() > 0) {
            foreach ($this->sharesPreviewPaginated as $row) {
                // Handle both array and object data structures
                $memberId = is_array($row) ? ($row['member_id'] ?? null) : ($row->member_id ?? null);
                if (!$memberId) continue;

                $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                $shareAmount = is_array($row) ? (is_numeric($row['share_amount']) ? $row['share_amount'] : 0) : (is_numeric($row->share_amount) ? $row->share_amount : 0);

                if (isset($consolidatedData[$memberId])) {
                    // Merge with existing data
                    $consolidatedData[$memberId]['remitted_shares'] += $shareAmount;
                    $consolidatedData[$memberId]['total_remitted'] += $shareAmount;
                    $consolidatedData[$memberId]['remaining_balance'] -= $shareAmount;
                } else {
                    // Create new record for shares preview only
                    $consolidatedData[$memberId] = [
                        'member_id' => $memberId,
                        'member_name' => $name,
                        'billing_type' => 'preview',
                        'remitted_loans' => 0,
                        'remitted_savings' => 0,
                        'remitted_shares' => $shareAmount,
                        'total_remitted' => $shareAmount,
                        'total_billed' => 0,
                        'remaining_balance' => 0
                    ];
                }
            }
        }

        return $consolidatedData;
    }
}

class RemittanceSheetExport implements FromArray, WithHeadings, WithTitle
{
    protected $remittances;
    protected $billingPeriod;
    protected $title;

    public function __construct($remittances, $billingPeriod, $title)
    {
        $this->remittances = $remittances;
        $this->billingPeriod = $billingPeriod;
        $this->title = $title;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        return [
            ['Member', 'Remitted Loans', 'Remitted Savings', 'Remitted Shares', 'Total Remitted', 'Total Billed', 'Remaining Loans']
        ];
    }

    public function array(): array
    {
        $rows = [];
        $totalLoans = 0;
        $totalSavings = 0;
        $totalShares = 0;
        $totalRemitted = 0;
        $totalBilled = 0;
        $totalRemaining = 0;
        $billingType = strtolower(str_contains($this->title, 'special') ? 'special' : 'regular');
        $billedTotals = [];
        foreach ($this->remittances as $remit) {
            $member = $remit->member;
            $remittedLoans = $remit->remitted_amount ?? 0;
            $remittedSavings = $remit->remitted_savings ?? 0;
            $remittedShares = $remit->remitted_shares ?? 0;
            $totalRemit = $remittedLoans + $remittedSavings + $remittedShares;
            // Compute billed total for this member and billing type
            $billedTotal = LoanForecast::where('member_id', $remit->member_id)
                ->where('billing_period', $this->billingPeriod)
                ->get()
                ->filter(function($forecast) use ($billingType) {
                    $productCode = null;
                    if ($forecast->loan_acct_no) {
                        $segments = explode('-', $forecast->loan_acct_no);
                        $productCode = $segments[2] ?? null;
                    }
                    $product = $productCode ? LoanProduct::where('product_code', $productCode)->first() : null;
                    return $product && $product->billing_type === $billingType;
                })
                ->sum('total_due');
            $remainingLoans = $billedTotal - $remittedLoans;
            $totalLoans += $remittedLoans;
            $totalSavings += $remittedSavings;
            $totalShares += $remittedShares;
            $totalRemitted += $totalRemit;
            $totalBilled += $billedTotal;
            $totalRemaining += $remainingLoans;
            $billedTotals[] = $billedTotal;
            $rows[] = [
                $member->full_name ?? ($member->fname . ' ' . $member->lname) ?? 'N/A',
                $remittedLoans,
                $remittedSavings,
                $remittedShares,
                $totalRemit,
                $billedTotal,
                $remainingLoans
            ];
        }
        // Totals row
        $rows[] = [
            'Total',
            $totalLoans,
            $totalSavings,
            $totalShares,
            $totalRemitted,
            array_sum($billedTotals),
            $totalRemaining
        ];
        return $rows;
    }
}
