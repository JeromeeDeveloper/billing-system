<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\LoanProduct;
use App\Models\SavingProduct;
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
        $branches = Branch::with(['members' => function($query) {
            $query->with(['loanForecasts', 'savings']);
        }])->get();
        foreach ($branches as $branch) {
            // Static headers
            $rows[] = ['', 'Remittance Report for branch (' . $branch->name . ')']; // B1
            $rows[] = ['', 'Remittance Date', now()->format('F d, Y')]; // B2
            $rows[] = ['', 'For Billing Period', $this->billingPeriod]; // B3
            $rows[] = [''];
            $rows[] = [''];
            $rows[] = [''];
            $rows[] = ['Branch Name:', $branch->name]; // A7, B7
            $rows[] = ['PRODUCT', 'AMOUNT', 'COUNT']; // A8, B8, C8

            // Loans
            foreach ($this->loanProducts as $product) {
                $membersWithProduct = $branch->members->filter(function($member) use ($product) {
                    return $member->loanForecasts->filter(function($loan) use ($product) {
                        $segments = explode('-', $loan->loan_acct_no);
                        $productCode = $segments[2] ?? null;
                        return $productCode && $productCode == $product->product_code;
                    })->isNotEmpty();
                });
                $totalAmount = $branch->members->flatMap->loanForecasts
                    ->filter(function($loan) use ($product) {
                        $segments = explode('-', $loan->loan_acct_no);
                        $productCode = $segments[2] ?? null;
                        return $productCode && $productCode == $product->product_code;
                    })->sum('total_due');
                $rows[] = [$product->product, $totalAmount, $membersWithProduct->count()];
            }
            // Savings
            foreach ($this->savingProducts as $product) {
                $membersWithProduct = $branch->members->filter(function($member) use ($product) {
                    return $member->savings->filter(function($saving) use ($product) {
                        return $saving->product_code == $product->product_code;
                    })->isNotEmpty();
                });
                $totalAmount = $branch->members->flatMap->savings
                    ->filter(function($saving) use ($product) {
                        return $saving->product_code == $product->product_code;
                    })->sum('current_balance');
                $rows[] = [$product->product_name, $totalAmount, $membersWithProduct->count()];
            }
            // Blank row before next branch
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
