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
    protected $billingPeriod;

    public function __construct($regularRemittances, $specialRemittances, $billingPeriod)
    {
        $this->regularRemittances = $regularRemittances;
        $this->specialRemittances = $specialRemittances;
        $this->billingPeriod = $billingPeriod;
    }

    public function sheets(): array
    {
        return [
            new RemittanceSheetExport($this->regularRemittances, $this->billingPeriod, 'Regular Billing'),
            new RemittanceSheetExport($this->specialRemittances, $this->billingPeriod, 'Special Billing'),
        ];
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
