<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\LoanProduct;
use App\Models\SavingProduct;
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

    public function __construct()
    {
        $this->loanProducts = LoanProduct::all();
        $this->savingProducts = SavingProduct::all();
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
            ['']
        ];

        // Add branch name header
        $headers[] = ['Branch Name'];

        // Add loan product headers
        foreach ($this->loanProducts as $product) {
            $headers[4][] = $product->name;
            $headers[4][] = 'Count';
        }

        // Add shares header
        $headers[4][] = 'Shares';
        $headers[4][] = 'Count';

        // Add savings product headers
        foreach ($this->savingProducts as $product) {
            $headers[4][] = $product->name;
            $headers[4][] = 'Count';
        }

        // Add total header
        $headers[4][] = 'Total';

        return $headers;
    }

    public function map($branch): array
    {
        $row = [$branch->name];

        // Count members per loan product
        foreach ($this->loanProducts as $product) {
            $count = $branch->members->flatMap->loanForecasts
                ->where('loan_product_id', $product->id)
                ->count();
            $row[] = $count;
        }

        // Count members with shares
        $shareCount = $branch->members->filter(function($member) {
            return $member->shares->isNotEmpty();
        })->count();
        $row[] = $shareCount;

        // Count members per saving product
        foreach ($this->savingProducts as $product) {
            $count = $branch->members->flatMap->savings
                ->where('saving_product_id', $product->id)
                ->count();
            $row[] = $count;
        }

        // Calculate total
        $total = $branch->members->count();
        $row[] = $total;

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

        // Set width for all other columns
        $column = 'B';
        for ($i = 0; $i < ($this->loanProducts->count() * 2 + 2 + $this->savingProducts->count() * 2 + 1); $i++) {
            $widths[$column] = 15;
            $column++;
        }

        return $widths;
    }

    public function title(): string
    {
        return 'Remittance Report Consolidated';
    }
}
