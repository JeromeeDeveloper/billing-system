<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\Remittance;
use App\Models\LoanForecast;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Auth;

class DetailedRemittanceReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected $billingPeriod;
    protected $branchId;

    public function __construct($billingPeriod = null, $branchId = null)
    {
        $this->billingPeriod = $billingPeriod ?? Auth::user()->billing_period;
        $this->branchId = $branchId;
    }

    public function title(): string
    {
        return 'Detailed Remittance Report';
    }

    public function headings(): array
    {
        return [
            'CID',
            'Member Name',
            'Total Billed',
            'Remitted Savings',
            'Remitted Shares',
            'Remaining Balance',
            'Total Remitted'
        ];
    }

    public function collection()
    {
        $billingPeriod = $this->billingPeriod;
        $members = \App\Models\Member::with(['loanForecasts' => function($q) use ($billingPeriod) {
            $q->where('billing_period', $billingPeriod);
        }, 'remittances' => function($q) use ($billingPeriod) {
            $q->where('created_at', '>=', $billingPeriod . '-01')
              ->where('created_at', '<=', $billingPeriod . '-31');
        }])->get();

        $reportData = collect();
        foreach ($members as $member) {
            $totalBilled = $member->loanForecasts->sum('total_due');
            $remittedSavings = $member->remittances->sum('savings_dep');
            $remittedShares = $member->remittances->sum('share_dep');
            $remittedLoans = $member->remittances->sum('loan_payment');
            $totalRemitted = $remittedLoans + $remittedSavings + $remittedShares;
            $remainingBalance = max(0, $totalBilled - $remittedLoans);

            // Only include members with any remitted amount
            if ($remittedLoans > 0 || $remittedSavings > 0 || $remittedShares > 0) {
                $reportData->push([
                    $member->cid,
                    trim(($member->lname ?? '') . ', ' . ($member->fname ?? '')),
                    $totalBilled,
                    $remittedSavings,
                    $remittedShares,
                    $remainingBalance,
                    $totalRemitted
                ]);
            }
        }
        return $reportData;
    }

    public function map($row): array
    {
        return [
            $row[0], // CID
            $row[1], // Member Name
            number_format($row[2], 2), // Total Billed
            number_format($row[3], 2), // Remitted Savings
            number_format($row[4], 2), // Remitted Shares
            number_format($row[5], 2), // Remaining Balance
            number_format($row[6], 2), // Total Remitted
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Auto-size columns
        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Style data rows
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle('A2:G' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Style numeric columns
            $sheet->getStyle('C2:G' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
        }
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // CID
            'B' => 25, // Member Name
            'C' => 18, // Total Billed
            'D' => 18, // Remitted Savings
            'E' => 18, // Remitted Shares
            'F' => 18, // Remaining Balance
            'G' => 18, // Total Remitted
        ];
    }
}
