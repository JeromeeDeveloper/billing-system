<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\LoanProduct;
use App\Models\SavingProduct;
use App\Models\ShareProduct;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BranchRemittanceReportPerBranchMemberExport implements FromArray, WithStyles, WithColumnWidths, WithTitle
{
    protected $loanProducts;
    protected $savingProducts;
    protected $shareProducts;
    protected $billingPeriod;
    protected $branchId;

    public function __construct($billingPeriod = null, $branchId = null)
    {
        $this->loanProducts = LoanProduct::all();
        $this->savingProducts = SavingProduct::all();
        $this->shareProducts = ShareProduct::all();
        $this->billingPeriod = $billingPeriod ?? now()->format('Y-m');
        $this->branchId = $branchId;
    }

    public function array(): array
    {
        $rows = [];
        $branch = Branch::with(['members' => function($query) {
            $query->with(['loanForecasts', 'shares', 'savings']);
        }])->find($this->branchId);
        if (!$branch) return [['No data for this branch']];
        // Static headers
        $rows[] = ['Remittance Report for ' . $branch->name]; // A1
        $rows[] = ['Remittance Date', now()->format('F d, Y')]; // A2
        $rows[] = ['For Billing Period', $this->billingPeriod]; // A3
        $rows[] = [''];

        // Dynamic headers
        $header = ['Member Name'];
        foreach ($this->loanProducts as $i => $product) {
            $header[] = 'Loan ' . ($i + 1);
        }
        foreach ($this->shareProducts as $i => $product) {
            $header[] = 'Share ' . ($i + 1);
        }
        foreach ($this->savingProducts as $i => $product) {
            $header[] = 'Savings ' . ($i + 1);
        }
        $rows[] = $header;

        // Member rows
        foreach ($branch->members as $member) {
            $row = [$member->fname . ' ' . $member->lname];
            // Loans
            foreach ($this->loanProducts as $product) {
                $amount = $member->loanForecasts->filter(function($loan) use ($product) {
                    $segments = explode('-', $loan->loan_acct_no);
                    $productCode = $segments[2] ?? null;
                    return $productCode && $productCode == $product->product_code;
                })->sum('total_due');
                $row[] = $amount;
            }
            // Shares
            foreach ($this->shareProducts as $product) {
                $amount = $member->shares->filter(function($share) use ($product) {
                    return $share->product_code == $product->product_code;
                })->sum('current_balance');
                $row[] = $amount;
            }
            // Savings
            foreach ($this->savingProducts as $product) {
                $amount = $member->savings->filter(function($saving) use ($product) {
                    return $saving->product_code == $product->product_code;
                })->sum('current_balance');
                $row[] = $amount;
            }
            $rows[] = $row;
        }

        // Totals row
        $totals = ['TOTAL'];
        // Loans
        foreach ($this->loanProducts as $product) {
            $total = $branch->members->flatMap->loanForecasts
                ->filter(function($loan) use ($product) {
                    $segments = explode('-', $loan->loan_acct_no);
                    $productCode = $segments[2] ?? null;
                    return $productCode && $productCode == $product->product_code;
                })->sum('total_due');
            $totals[] = $total;
        }
        // Shares
        foreach ($this->shareProducts as $product) {
            $total = $branch->members->flatMap->shares
                ->filter(function($share) use ($product) {
                    return $share->product_code == $product->product_code;
                })->sum('current_balance');
            $totals[] = $total;
        }
        // Savings
        foreach ($this->savingProducts as $product) {
            $total = $branch->members->flatMap->savings
                ->filter(function($saving) use ($product) {
                    return $saving->product_code == $product->product_code;
                })->sum('current_balance');
            $totals[] = $total;
        }
        $rows[] = $totals;
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [];
        $styles[5] = ['font' => ['bold' => true]];
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
