<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\LoanProduct;
use App\Models\SavingProduct;
use App\Models\ShareProduct;
use App\Models\LoanRemittance;
use App\Models\Savings;
use App\Models\Remittance;
use App\Models\RemittanceBatch;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RemittanceReportPerBranchExport implements FromArray, WithStyles, WithColumnWidths, WithTitle
{
    protected $loanProducts;
    protected $savingProducts;
    protected $billingPeriod;

    public function __construct($billingPeriod = null)
    {
        $this->loanProducts = LoanProduct::all();
        $this->savingProducts = SavingProduct::all();
        $this->billingPeriod = $billingPeriod ?? now()->format('Y-m');
    }

    public function array(): array
    {
        $rows = [];
        $branches = Branch::all();
        foreach ($branches as $branch) {
            $rows[] = ['', 'Remittance Report for branch (' . $branch->name . ')'];
            // Get latest remittance import date for this billing period
            $remitDate = RemittanceBatch::where('billing_period', $this->billingPeriod)
                ->orderByDesc('imported_at')->value('imported_at');
            $remitDateStr = $remitDate ? \Carbon\Carbon::parse($remitDate)->format('F d, Y') : '';
            $rows[] = ['', 'Remittance Date', $remitDateStr];
            $rows[] = ['', 'For Billing Period', $this->billingPeriod];
            $rows[] = [''];
            $rows[] = [''];
            $rows[] = [''];
            $rows[] = ['Branch Name:', $branch->name];
            $rows[] = ['PRODUCT', 'AMOUNT', 'COUNT'];

            // --- LOAN PRODUCTS ---
            // Use RemittanceReport for consistent data with Consolidated report
            $branchMembers = $branch->members()->pluck('cid')->toArray();
            $remittanceReports = \App\Models\RemittanceReport::where('period', $this->billingPeriod)
                ->whereIn('cid', $branchMembers)
                ->where('remitted_loans', '>', 0)
                ->get();

            // Group by loan products using LoanRemittance for product breakdown
            $loanRemits = LoanRemittance::where('billing_period', $this->billingPeriod)
                ->whereHas('member', function($q) use ($branch) {
                    $q->where('branch_id', $branch->id);
                })
                ->where('remitted_amount', '>', 0)
                ->with('loanForecast')
                ->get();
            $loanByProduct = $loanRemits->groupBy(function($remit) {
                $forecast = $remit->loanForecast;
                if ($forecast && $forecast->loan_acct_no) {
                    $segments = explode('-', $forecast->loan_acct_no);
                    return $segments[2] ?? null;
                }
                return null;
            });
            foreach ($loanByProduct as $productCode => $remits) {
                if (!$productCode) continue;
                $product = LoanProduct::where('product_code', $productCode)->first();
                if (!$product) continue;
                $totalAmount = $remits->sum('remitted_amount');
                $memberCount = $remits->unique('member_id')->count();
                $rows[] = [$product->product, $totalAmount > 0 ? $totalAmount : '', $memberCount > 0 ? $memberCount : ''];
            }

            // --- SAVINGS PRODUCTS ---
            // Use RemittanceReport for total savings amount (includes excess)
            $totalSavingsFromReports = $remittanceReports->sum('remitted_savings');

            // Get savings breakdown by product from Savings table
            $savingsRemits = Savings::whereHas('member', function($q) use ($branch) {
                    $q->where('branch_id', $branch->id);
                })
                ->where('remittance_amount', '>', 0)
                ->get();
            $savingsByProduct = $savingsRemits->groupBy('product_code');

            // If we have savings from reports, show them grouped by product
            if ($totalSavingsFromReports > 0) {
                foreach ($savingsByProduct as $productCode => $remits) {
                    $product = SavingProduct::where('product_code', $productCode)->first();
                    if (!$product) continue;
                    $totalAmount = $remits->sum('remittance_amount');
                    $memberCount = $remits->unique('member_id')->count();
                    $rows[] = [$product->product_name, $totalAmount > 0 ? $totalAmount : '', $memberCount > 0 ? $memberCount : ''];
                }
            }

            // --- SHARE PRODUCTS ---
            // Use RemittanceReport for shares amount
            $totalSharesFromReports = $remittanceReports->sum('remitted_shares');
            if ($totalSharesFromReports > 0) {
                $shareCount = $remittanceReports->where('remitted_shares', '>', 0)->count();
                $rows[] = ['Shares', $totalSharesFromReports, $shareCount];
            }

            $rows[] = [''];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Bold for header rows (A8, B8, C8 for each branch)
        $row = 8;
        $branches = Branch::count();
        $styles = [];
        for ($i = 0; $i < $branches; $i++) {
            $styles[$row] = ['font' => ['bold' => true]];
            $row += 2 + LoanProduct::count() + SavingProduct::count() + 1; // 1 for blank row
        }
        // Bold for B1, B2, B3
        $row = 1;
        $styles[$row] = ['font' => ['bold' => true, 'size' => 14]];
        $styles[$row+1] = ['font' => ['bold' => true]];
        $styles[$row+2] = ['font' => ['bold' => true]];
        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 20,
            'C' => 10,
        ];
    }

    public function title(): string
    {
        return 'Remittance Report Per Branch';
    }
}
