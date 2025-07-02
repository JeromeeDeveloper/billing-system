<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\LoanProduct;
use App\Models\SavingProduct;
use App\Models\ShareProduct;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RemittanceReportConsolidatedExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected $loanProducts;
    protected $savingProducts;
    protected $shareProducts;
    protected $dynamicHeaders;

    public function __construct()
    {
        $this->loanProducts = LoanProduct::all();
        $this->savingProducts = SavingProduct::all();
        $this->shareProducts = ShareProduct::all();
        $this->buildDynamicHeaders();
    }

    protected function buildDynamicHeaders()
    {
        $headers = ['Branch Name'];
        foreach ($this->loanProducts as $i => $product) {
            $headers[] = 'Loan ' . ($i + 1);
            $headers[] = 'Count';
        }
        foreach ($this->shareProducts as $i => $product) {
            $headers[] = 'Share ' . ($i + 1);
            $headers[] = 'Count';
        }
        foreach ($this->savingProducts as $i => $product) {
            $headers[] = 'Savings ' . ($i + 1);
            $headers[] = 'Count';
        }
        $this->dynamicHeaders = $headers;
    }

    public function collection()
    {
        return Branch::with(['members' => function($query) {
            $query->with(['loanForecasts', 'shares', 'savings']);
        }])->get();
    }

    public function headings(): array
    {
        $headers = [
            ['Remittance Report Consolidated'],
            ['Remittance Date', now()->format('F d, Y')],
            ['For Billing Period', now()->format('F Y')],
            [''],
            $this->dynamicHeaders
        ];
        return $headers;
    }

    public function map($branch): array
    {
        $row = [$branch->name];
        // Loans: product name and count
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
        // Shares: product name and count
        foreach ($this->shareProducts as $product) {
            $membersWithProduct = $branch->members->filter(function($member) use ($product) {
                return $member->shares->filter(function($share) use ($product) {
                    return $share->product_code == $product->product_code;
                })->isNotEmpty();
            });
            $row[] = $product->product_name;
            $row[] = $membersWithProduct->count();
        }
        // Savings: product name and count
        foreach ($this->savingProducts as $product) {
            $membersWithProduct = $branch->members->filter(function($member) use ($product) {
                return $member->savings->filter(function($saving) use ($product) {
                    return $saving->product_code == $product->product_code;
                })->isNotEmpty();
            });
            $row[] = $product->product_name;
            $row[] = $membersWithProduct->count();
        }
        return $row;
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
        $widths = ['A' => 30]; // Branch Name column
        $column = 'B';
        for ($i = 1; $i < count($this->dynamicHeaders); $i++) {
            $widths[$column] = 18;
            $column++;
        }
        return $widths;
    }

    public function title(): string
    {
        return 'Remittance Report Consolidated';
    }
}
