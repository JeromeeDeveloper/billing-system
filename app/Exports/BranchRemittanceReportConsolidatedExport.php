<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\LoanProduct;
use App\Models\SavingProduct;
use App\Models\ShareProduct;
use App\Models\RemittanceBatch;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BranchRemittanceReportConsolidatedExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
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
        $remitDate = RemittanceBatch::orderByDesc('imported_at')->value('imported_at');
        $remitDateStr = $remitDate ? \Carbon\Carbon::parse($remitDate)->format('F d, Y') : '';
        $rows[] = ['Remittance Report Consolidated'];
        $rows[] = ['Remittance Date', $remitDateStr];
        $rows[] = ['For Billing Period', $this->billingPeriod];
        $rows[] = [''];
        // Dynamic headers
        $header = ['Branch Name'];
        foreach ($this->loanProducts as $i => $product) {
            $header[] = 'Loan ' . ($i + 1);
            $header[] = 'Count';
        }
        foreach ($this->shareProducts as $i => $product) {
            $header[] = 'Share ' . ($i + 1);
            $header[] = 'Count';
        }
        foreach ($this->savingProducts as $i => $product) {
            $header[] = 'Savings ' . ($i + 1);
            $header[] = 'Count';
        }
        $rows[] = $header;
        // Data row for this branch
        $row = [$branch->name];
        // Loans
        foreach ($this->loanProducts as $product) {
            $membersWithProduct = $branch->members->filter(function($member) use ($product) {
                return $member->loanForecasts->filter(function($loan) use ($product) {
                    $segments = explode('-', $loan->loan_acct_no);
                    $productCode = $segments[2] ?? null;
                    return $productCode && $productCode == $product->product_code;
                })->isNotEmpty();
            });
            $row[] = $product->product;
            $row[] = $membersWithProduct->count();
        }
        // Shares
        foreach ($this->shareProducts as $product) {
            $membersWithProduct = $branch->members->filter(function($member) use ($product) {
                return $member->shares->filter(function($share) use ($product) {
                    return $share->product_code == $product->product_code;
                })->isNotEmpty();
            });
            $row[] = $product->product_name;
            $row[] = $membersWithProduct->count();
        }
        // Savings
        foreach ($this->savingProducts as $product) {
            $membersWithProduct = $branch->members->filter(function($member) use ($product) {
                return $member->savings->filter(function($saving) use ($product) {
                    return $saving->product_code == $product->product_code;
                })->isNotEmpty();
            });
            $row[] = $product->product_name;
            $row[] = $membersWithProduct->count();
        }
        $rows[] = $row;
        return $rows;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
            5 => ['font' => ['bold' => true]],
            'A1:' . $lastColumn . '1' => ['alignment' => ['horizontal' => 'center']],
            'A1:' . $lastColumn . $lastRow => ['borders' => ['allBorders' => ['borderStyle' => 'thin']]],
        ];
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 30];
        $col = 'B';
        $count = $this->loanProducts->count() * 2 + $this->shareProducts->count() * 2 + $this->savingProducts->count() * 2;
        for ($i = 0; $i < $count; $i++) {
            $widths[$col] = 18;
            $col++;
        }
        return $widths;
    }

    public function title(): string
    {
        return 'Remittance Report Consolidated';
    }
}
