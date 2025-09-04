<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\LoanProduct;
use App\Models\SavingProduct;
use App\Models\ShareProduct;
use App\Models\LoanRemittance;
use App\Models\Remittance;
use App\Models\Savings;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RemittanceReportPerBranchMemberExport implements FromArray, WithStyles, WithColumnWidths, WithTitle
{
    protected $loanProducts;
    protected $savingProducts;
    protected $shareProducts;
    protected $billingPeriod;

    public function __construct($billingPeriod = null)
    {
        $this->loanProducts = LoanProduct::all();
        $this->savingProducts = SavingProduct::all();
        $this->shareProducts = ShareProduct::all();
        $this->billingPeriod = $billingPeriod ?? now()->format('Y-m');
    }

    public function array(): array
    {
        $rows = [];
        $branches = Branch::all();
        foreach ($branches as $branch) {
            $rows[] = ['Remittance Report for ' . $branch->name];
            $remitDate = \App\Models\RemittanceBatch::where('billing_period', $this->billingPeriod)
                ->orderByDesc('imported_at')->value('imported_at');
            $remitDateStr = $remitDate ? \Carbon\Carbon::parse($remitDate)->format('F d, Y') : '';
            $rows[] = ['Remittance Date', $remitDateStr];
            $rows[] = ['For Billing Period', $this->billingPeriod];
            $rows[] = [''];

            // --- Determine products with remittances ---
            // Use RemittanceReport for consistent data with Consolidated report
            $branchMembers = $branch->members()->pluck('cid')->toArray();
            $remittanceReports = \App\Models\RemittanceReport::where('period', $this->billingPeriod)
                ->whereIn('cid', $branchMembers)
                ->get();

            // Loans - check if branch has any loan remittances
            $hasLoans = $remittanceReports->where('remitted_loans', '>', 0)->count() > 0;
            $loanRemits = LoanRemittance::where('billing_period', $this->billingPeriod)
                ->whereHas('member', function($q) use ($branch) {
                    $q->where('branch_id', $branch->id);
                })
                ->where('remitted_amount', '>', 0)
                ->with('loanForecast')
                ->get();
            $loanProductCodes = $loanRemits->map(function($remit) {
                $forecast = $remit->loanForecast;
                if ($forecast && $forecast->loan_acct_no) {
                    $segments = explode('-', $forecast->loan_acct_no);
                    return $segments[2] ?? null;
                }
                return null;
            })->filter()->unique()->values();
            $loanProducts = LoanProduct::whereIn('product_code', $loanProductCodes)->get();

            // Shares - check if branch has any share remittances
            $hasShare = $remittanceReports->where('remitted_shares', '>', 0)->count() > 0;

            // Savings - check if branch has any savings remittances
            $hasSavings = $remittanceReports->where('remitted_savings', '>', 0)->count() > 0;
            $savingsRemits = Savings::whereHas('member', function($q) use ($branch) {
                    $q->where('branch_id', $branch->id);
                })
                ->where('remittance_amount', '>', 0)
                ->get();
            $savingsProductCodes = $savingsRemits->pluck('product_code')->filter()->unique()->values();
            $savingsProducts = SavingProduct::whereIn('product_code', $savingsProductCodes)->get();

            // --- Build header ---
            $header = ['Member Name'];
            foreach ($loanProducts as $product) {
                $header[] = $product->product;
            }
            if ($hasShare) {
                $header[] = 'Shares';
            }
            foreach ($savingsProducts as $product) {
                $header[] = $product->product_name;
            }
            $rows[] = $header;

            // --- Member rows ---
            $members = $branch->members;
            foreach ($members as $member) {
                $row = [$member->fname . ' ' . $member->lname];
                $hasRemit = false;
                // Loans
                foreach ($loanProducts as $product) {
                    $remit = LoanRemittance::where('billing_period', $this->billingPeriod)
                        ->where('member_id', $member->id)
                        ->where('remitted_amount', '>', 0)
                        ->with('loanForecast')
                        ->get()
                        ->filter(function($remit) use ($product) {
                            $forecast = $remit->loanForecast;
                            if ($forecast && $forecast->loan_acct_no) {
                                $segments = explode('-', $forecast->loan_acct_no);
                                return ($segments[2] ?? null) == $product->product_code;
                            }
                            return false;
                        });
                    $amount = $remit->sum('remitted_amount');
                    if ($amount > 0) $hasRemit = true;
                    $row[] = $amount > 0 ? $amount : '';
                }
                // Shares
                if ($hasShare) {
                    $shareAmount = $remittanceReports->where('cid', $member->cid)->first()->remitted_shares ?? 0;
                    if ($shareAmount > 0) $hasRemit = true;
                    $row[] = $shareAmount > 0 ? $shareAmount : '';
                }
                // Savings
                foreach ($savingsProducts as $product) {
                    $amount = Savings::where('member_id', $member->id)
                        ->where('product_code', $product->product_code)
                        ->where('remittance_amount', '>', 0)
                        ->sum('remittance_amount');
                    if ($amount > 0) $hasRemit = true;
                    $row[] = $amount > 0 ? $amount : '';
                }
                if ($hasRemit) {
                    $rows[] = $row;
                }
            }

            // --- Totals row ---
            $totals = ['TOTAL'];
            // Loans
            foreach ($loanProducts as $product) {
                $amount = $loanRemits->filter(function($remit) use ($product) {
                    $forecast = $remit->loanForecast;
                    if ($forecast && $forecast->loan_acct_no) {
                        $segments = explode('-', $forecast->loan_acct_no);
                        return ($segments[2] ?? null) == $product->product_code;
                    }
                    return false;
                })->sum('remitted_amount');
                $totals[] = $amount > 0 ? $amount : '';
            }
            // Shares
            if ($hasShare) {
                $totals[] = $remittanceReports->sum('remitted_shares');
            }
            // Savings
            foreach ($savingsProducts as $product) {
                $amount = $savingsRemits->where('product_code', $product->product_code)->sum('remittance_amount');
                $totals[] = $amount > 0 ? $amount : '';
            }
            $rows[] = $totals;
            $rows[] = [''];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Bold for header rows (dynamic header and totals for each branch)
        $styles = [];
        $row = 5;
        $branches = Branch::count();
        for ($i = 0; $i < $branches; $i++) {
            $styles[$row] = ['font' => ['bold' => true]];
            $row++; // header
            $row += Branch::with('members')->get()[$i]->members->count(); // skip member rows
            $styles[$row] = ['font' => ['bold' => true]];
            $row += 2; // total row + blank row
            $row += 4; // static header rows
        }
        // Bold for A1, A2, A3
        $styles[1] = ['font' => ['bold' => true, 'size' => 14]];
        $styles[2] = ['font' => ['bold' => true]];
        $styles[3] = ['font' => ['bold' => true]];
        return $styles;
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 25];
        $col = 'B';
        $count = $this->loanProducts->count() + $this->shareProducts->count() + $this->savingProducts->count();
        for ($i = 0; $i < $count; $i++) {
            $widths[$col] = 18;
            $col++;
        }
        return $widths;
    }

    public function title(): string
    {
        return 'Remittance Report Per Branch Member';
    }
}
