<?php

namespace App\Exports;

use App\Models\RemittanceReport;
use App\Models\RemittanceBatch;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Collection;

class PerRemittanceReportExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
    protected $billingPeriod;
    protected $isBranch;
    protected $branchId;

    public function __construct($billingPeriod, $isBranch = false, $branchId = null)
    {
        $this->billingPeriod = $billingPeriod;
        $this->isBranch = $isBranch;
        $this->branchId = $branchId;
    }

    public function title(): string
    {
        return $this->isBranch ? 'Branch Per-Remittance Report' : 'Admin Per-Remittance Report';
    }

    public function headings(): array
    {
        // We don't need headings here since we'll add them in the array() method
        return [];
    }

    public function array(): array
    {
        // Get all remittance tags for this billing period
        $remittanceTags = RemittanceBatch::where('billing_period', $this->billingPeriod)
            ->orderBy('remittance_tag')
            ->pluck('remittance_tag')
            ->toArray();

        $maxTags = count($remittanceTags);

        // Get all members with remittance data
        $query = RemittanceReport::where('period', $this->billingPeriod);

        if ($this->isBranch && $this->branchId) {
            $query->whereHas('member', function($q) {
                $q->where('branch_id', $this->branchId);
            });
        }

        $reports = $query->get();

        // Group by CID
        $groupedData = $reports->groupBy('cid');

        $rows = [];

        // Add first section header: Summary
        $summaryHeaders = ['CID', 'Member Name', 'Type', 'Remitted Loans', 'Remitted Savings', 'Remitted Shares', 'Total Remitted'];
        $rows[] = $summaryHeaders;

                // Add summary data for all members (only if they have non-zero values)
        foreach ($groupedData as $cid => $memberReports) {
            $memberName = $memberReports->first()->member_name ?? '';

            $totalLoans = $memberReports->where('remittance_type', 'loans_savings')->sum('remitted_loans');
            $totalSavings = $memberReports->where('remittance_type', 'loans_savings')->sum('remitted_savings');
            $totalShares = $memberReports->where('remittance_type', 'shares')->sum('remitted_shares');
            $totalRemitted = $totalLoans + $totalSavings + $totalShares;

            // Only include member if they have non-zero values
            if ($totalRemitted > 0) {
                $summaryRow = [
                    $cid,
                    $memberName,
                    'Total',
                    $totalLoans,
                    $totalSavings,
                    $totalShares,
                    $totalRemitted
                ];
                $rows[] = $summaryRow;
            }
        }

        // Add empty row for spacing
        $rows[] = array_fill(0, 7, '');

        // Add second section header: Loans breakdown
        $loansHeaders = ['CID', 'Member Name', 'Type', 'Billed Amount'];
        for ($i = 1; $i <= $maxTags; $i++) {
            $loansHeaders[] = "Remittance Loans {$i}";
        }
        $loansHeaders[] = 'Running Balance';
        $rows[] = $loansHeaders;

                        // Add loans data for all members (only if they have non-zero loan values)
        foreach ($groupedData as $cid => $memberReports) {
            $memberName = $memberReports->first()->member_name ?? '';

            $totalLoans = $memberReports->where('remittance_type', 'loans_savings')->sum('remitted_loans');

            // Only include member if they have non-zero loan values
            if ($totalLoans > 0) {
                $loansRow = [
                    $cid,
                    $memberName,
                    'Loans',
                    $memberReports->where('remittance_type', 'loans_savings')->sum('billed_amount')
                ];

                $totalLoansPaid = 0;
                foreach ($remittanceTags as $tag) {
                    $loanAmount = $memberReports->where('remittance_tag', $tag)->where('remittance_type', 'loans_savings')->first()->remitted_loans ?? 0;
                    $loansRow[] = $loanAmount;
                    $totalLoansPaid += $loanAmount;
                }

                $billedAmount = $memberReports->where('remittance_type', 'loans_savings')->sum('billed_amount');
                $runningBalance = $billedAmount - $totalLoansPaid;
                $loansRow[] = $runningBalance;
                $rows[] = $loansRow;
            }
        }

        // Add empty row for spacing
        $rows[] = array_fill(0, 4 + $maxTags, '');

        // Add third section header: Savings breakdown
        $savingsHeaders = ['CID', 'Member Name', 'Type'];
        for ($i = 1; $i <= $maxTags; $i++) {
            $savingsHeaders[] = "Remittance Savings {$i}";
        }
        $savingsHeaders[] = 'Total Remittance on Savings';
        $rows[] = $savingsHeaders;

                        // Add savings data for all members (only if they have non-zero savings values)
        foreach ($groupedData as $cid => $memberReports) {
            $memberName = $memberReports->first()->member_name ?? '';

            $totalSavings = $memberReports->where('remittance_type', 'loans_savings')->sum('remitted_savings');

            // Only include member if they have non-zero savings values
            if ($totalSavings > 0) {
                $savingsRow = [
                    $cid,
                    $memberName,
                    'Savings'
                ];

                $totalSavingsPaid = 0;
                foreach ($remittanceTags as $tag) {
                    $savingsAmount = $memberReports->where('remittance_tag', $tag)->where('remittance_type', 'loans_savings')->first()->remitted_savings ?? 0;
                    $savingsRow[] = $savingsAmount;
                    $totalSavingsPaid += $savingsAmount;
                }
                $savingsRow[] = $totalSavingsPaid;
                $rows[] = $savingsRow;
            }
        }

        // Add empty row for spacing
        $rows[] = array_fill(0, 3 + $maxTags, '');

        // Add fourth section header: Shares breakdown
        $sharesHeaders = ['CID', 'Member Name', 'Type'];
        for ($i = 1; $i <= $maxTags; $i++) {
            $sharesHeaders[] = "Remittance Share {$i}";
        }
        $sharesHeaders[] = 'Total Remittance on Share';
        $rows[] = $sharesHeaders;

                        // Add shares data for all members (only if they have non-zero shares values)
        foreach ($groupedData as $cid => $memberReports) {
            $memberName = $memberReports->first()->member_name ?? '';

            $totalShares = $memberReports->where('remittance_type', 'shares')->sum('remitted_shares');

            // Only include member if they have non-zero shares values
            if ($totalShares > 0) {
                $sharesRow = [
                    $cid,
                    $memberName,
                    'Shares'
                ];

                $totalSharesPaid = 0;
                foreach ($remittanceTags as $tag) {
                    $sharesAmount = $memberReports->where('remittance_tag', $tag)->where('remittance_type', 'shares')->first()->remitted_shares ?? 0;
                    $sharesRow[] = $sharesAmount;
                    $totalSharesPaid += $sharesAmount;
                }
                $sharesRow[] = $totalSharesPaid;
                $rows[] = $sharesRow;
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Style the headers
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E2EFDA']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Number formatting for amount columns
        $highestRow = $sheet->getHighestRow();
        for ($row = 1; $row <= $highestRow; $row++) {
            $sheet->getStyle("D{$row}:G{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        }

        return $sheet;
    }
}
