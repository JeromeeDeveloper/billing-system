<?php

namespace App\Exports;

use App\Models\RemittancePreview;
use App\Models\Remittance;
use App\Models\Member;
use App\Models\LoanForecast;
use App\Models\LoanProduct;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;

class ConsolidatedRemittanceReportExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithMultipleSheets
{
    protected $billingPeriod;
    protected $userId;
    protected $regularRemittances;
    protected $specialRemittances;
    protected $loansSavingsPreviewPaginated;
    protected $sharesPreviewPaginated;
    protected $remittanceReports;

    public function __construct($billingPeriod = null, $userId = null)
    {
        $this->billingPeriod = $billingPeriod ?? Auth::user()->billing_period;
        $this->userId = $userId ?? Auth::id();
        $this->loadData();
    }

    protected function loadData()
    {
        // Load regular remittances from RemittanceReport
        $this->regularRemittances = Remittance::with('member')
            ->whereHas('member', function($query) {
                $query->where('billing_period', $this->billingPeriod);
            })
            ->get();

        // Load special remittances from RemittanceReport
        $this->specialRemittances = Remittance::with('member')
            ->whereHas('member', function($query) {
                $query->where('billing_period', $this->billingPeriod);
            })
            ->get();

        // Load preview data from RemittancePreview
        $this->loansSavingsPreviewPaginated = RemittancePreview::where('user_id', $this->userId)
            ->where('remittance_type', 'loans_savings')
            ->where('billing_period', $this->billingPeriod)
            ->get();

        $this->sharesPreviewPaginated = RemittancePreview::where('user_id', $this->userId)
            ->where('remittance_type', 'shares')
            ->where('billing_period', $this->billingPeriod)
            ->get();

        // Also load RemittanceReport data for accumulated billing data
        $this->remittanceReports = \App\Models\RemittanceReport::where('period', $this->billingPeriod)->get();
    }

    public function sheets(): array
    {
        return [
            'Matched' => new MatchedSheet($this->loansSavingsPreviewPaginated, $this->sharesPreviewPaginated, $this->remittanceReports),
            'Unmatched' => new UnmatchedSheet($this->loansSavingsPreviewPaginated, $this->sharesPreviewPaginated),
            'No Branch' => new NoBranchSheet($this->loansSavingsPreviewPaginated, $this->sharesPreviewPaginated),
        ];
    }

    public function array(): array
    {
        return []; // This will be handled by individual sheets
    }

    public function headings(): array
    {
        return []; // This will be handled by individual sheets
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, // Member Name
            'B' => 15, // Type
            'C' => 15, // Remitted Loans
            'D' => 15, // Remitted Savings
            'E' => 15, // Remitted Shares
            'F' => 15, // Total Remitted
            'G' => 15, // Total Billed
            'H' => 20, // Remaining Balance/Status
        ];
    }
}

class MatchedSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $loansSavingsPreviewPaginated;
    protected $sharesPreviewPaginated;
    protected $remittanceReports;

    public function __construct($loansSavingsPreviewPaginated, $sharesPreviewPaginated, $remittanceReports = null)
    {
        $this->loansSavingsPreviewPaginated = $loansSavingsPreviewPaginated;
        $this->sharesPreviewPaginated = $sharesPreviewPaginated;
        $this->remittanceReports = $remittanceReports;
    }

    public function array(): array
    {
        $rows = [];

        // Process RemittanceReport data (accumulated billing data) - these are all matched
        if ($this->remittanceReports) {
            foreach ($this->remittanceReports as $report) {
                if ($report->remitted_loans > 0 || $report->remitted_savings > 0 || $report->remitted_shares > 0) {
                    $rows[] = [
                        $report->member_name ?? 'N/A',
                        'Accumulated Billing',
                        number_format($report->remitted_loans ?? 0, 2),
                        number_format($report->remitted_savings ?? 0, 2),
                        number_format($report->remitted_shares ?? 0, 2),
                        number_format(($report->remitted_loans ?? 0) + ($report->remitted_savings ?? 0) + ($report->remitted_shares ?? 0), 2),
                        '-',
                        'Matched'
                    ];
                }
            }
        }

        // Process Loans & Savings Preview - Matched only
        foreach ($this->loansSavingsPreviewPaginated as $row) {
            $status = is_array($row) ? ($row['status'] ?? '') : ($row->status ?? '');
            if ($status === 'success') {
                $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                $loans = is_array($row) ? ($row['loans'] ?? 0) : ($row->loans ?? 0);
                $savings = is_array($row) ? ($row['savings'] ?? 0) : ($row->savings ?? 0);

                $rows[] = [
                    $name,
                    'Import Preview',
                    number_format($loans, 2),
                    number_format($savings, 2),
                    '0.00',
                    number_format($loans + $savings, 2),
                    '-',
                    'Matched'
                ];
            }
        }

        // Process Shares Preview - Matched only
        foreach ($this->sharesPreviewPaginated as $row) {
            $status = is_array($row) ? ($row['status'] ?? '') : ($row->status ?? '');
            if ($status === 'success') {
                $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                $shareAmount = is_array($row) ? ($row['share_amount'] ?? 0) : ($row->share_amount ?? 0);

                $rows[] = [
                    $name,
                    'Import Preview',
                    '0.00',
                    '0.00',
                    number_format($shareAmount, 2),
                    number_format($shareAmount, 2),
                    '-',
                    'Matched'
                ];
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['Consolidated Remittance Report - Matched Records'],
            ['Generated on', now()->format('F d, Y H:i:s')],
            [''],
            ['Member', 'Type', 'Remitted Loans', 'Remitted Savings', 'Remitted Shares', 'Total Remitted', 'Total Billed', 'Status']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            'A1:H' . $lastRow => ['borders' => ['allBorders' => ['borderStyle' => 'thin']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, 'B' => 15, 'C' => 15, 'D' => 15, 'E' => 15, 'F' => 15, 'G' => 15, 'H' => 20
        ];
    }

    public function title(): string
    {
        return 'Matched';
    }
}

class UnmatchedSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $loansSavingsPreviewPaginated;
    protected $sharesPreviewPaginated;

    public function __construct($loansSavingsPreviewPaginated, $sharesPreviewPaginated)
    {
        $this->loansSavingsPreviewPaginated = $loansSavingsPreviewPaginated;
        $this->sharesPreviewPaginated = $sharesPreviewPaginated;
    }

    public function array(): array
    {
        $rows = [];

        // Process Loans & Savings Preview - Unmatched only
        foreach ($this->loansSavingsPreviewPaginated as $row) {
            $status = is_array($row) ? ($row['status'] ?? '') : ($row->status ?? '');
            $message = is_array($row) ? ($row['message'] ?? '') : ($row->message ?? '');
            $isNoBranch = str_contains(strtolower($message), 'no branch');

            if ($status === 'error' && !$isNoBranch) {
                $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                $loans = is_array($row) ? ($row['loans'] ?? 0) : ($row->loans ?? 0);
                $savings = is_array($row) ? ($row['savings'] ?? 0) : ($row->savings ?? 0);

                $rows[] = [
                    $name,
                    'Import Preview',
                    number_format($loans, 2),
                    number_format($savings, 2),
                    '0.00',
                    number_format($loans + $savings, 2),
                    '-',
                    'Unmatched'
                ];
            }
        }

        // Process Shares Preview - Unmatched only
        foreach ($this->sharesPreviewPaginated as $row) {
            $status = is_array($row) ? ($row['status'] ?? '') : ($row->status ?? '');
            $message = is_array($row) ? ($row['message'] ?? '') : ($row->message ?? '');
            $isNoBranch = str_contains(strtolower($message), 'no branch');

            if ($status === 'error' && !$isNoBranch) {
                $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                $shareAmount = is_array($row) ? ($row['share_amount'] ?? 0) : ($row->share_amount ?? 0);

                $rows[] = [
                    $name,
                    'Import Preview',
                    '0.00',
                    '0.00',
                    number_format($shareAmount, 2),
                    number_format($shareAmount, 2),
                    '-',
                    'Unmatched'
                ];
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['Consolidated Remittance Report - Unmatched Records'],
            ['Generated on', now()->format('F d, Y H:i:s')],
            [''],
            ['Member', 'Type', 'Remitted Loans', 'Remitted Savings', 'Remitted Shares', 'Total Remitted', 'Total Billed', 'Status']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            'A1:H' . $lastRow => ['borders' => ['allBorders' => ['borderStyle' => 'thin']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, 'B' => 15, 'C' => 15, 'D' => 15, 'E' => 15, 'F' => 15, 'G' => 15, 'H' => 20
        ];
    }

    public function title(): string
    {
        return 'Unmatched';
    }
}

class NoBranchSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $loansSavingsPreviewPaginated;
    protected $sharesPreviewPaginated;

    public function __construct($loansSavingsPreviewPaginated, $sharesPreviewPaginated)
    {
        $this->loansSavingsPreviewPaginated = $loansSavingsPreviewPaginated;
        $this->sharesPreviewPaginated = $sharesPreviewPaginated;
    }

    public function array(): array
    {
        $rows = [];

        // Process Loans & Savings Preview - No Branch only
        foreach ($this->loansSavingsPreviewPaginated as $row) {
            $message = is_array($row) ? ($row['message'] ?? '') : ($row->message ?? '');
            $isNoBranch = str_contains(strtolower($message), 'no branch');

            if ($isNoBranch) {
                $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                $loans = is_array($row) ? ($row['loans'] ?? 0) : ($row->loans ?? 0);
                $savings = is_array($row) ? ($row['savings'] ?? 0) : ($row->savings ?? 0);

                $rows[] = [
                    $name,
                    'Import Preview',
                    number_format($loans, 2),
                    number_format($savings, 2),
                    '0.00',
                    number_format($loans + $savings, 2),
                    '-',
                    'No Branch'
                ];
            }
        }

        // Process Shares Preview - No Branch only
        foreach ($this->sharesPreviewPaginated as $row) {
            $message = is_array($row) ? ($row['message'] ?? '') : ($row->message ?? '');
            $isNoBranch = str_contains(strtolower($message), 'no branch');

            if ($isNoBranch) {
                $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                $shareAmount = is_array($row) ? ($row['share_amount'] ?? 0) : ($row->share_amount ?? 0);

                $rows[] = [
                    $name,
                    'Import Preview',
                    '0.00',
                    '0.00',
                    number_format($shareAmount, 2),
                    number_format($shareAmount, 2),
                    '-',
                    'No Branch'
                ];
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['Consolidated Remittance Report - No Branch Records'],
            ['Generated on', now()->format('F d, Y H:i:s')],
            [''],
            ['Member', 'Type', 'Remitted Loans', 'Remitted Savings', 'Remitted Shares', 'Total Remitted', 'Total Billed', 'Status']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            'A1:H' . $lastRow => ['borders' => ['allBorders' => ['borderStyle' => 'thin']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, 'B' => 15, 'C' => 15, 'D' => 15, 'E' => 15, 'F' => 15, 'G' => 15, 'H' => 20
        ];
    }

    public function title(): string
    {
        return 'No Branch';
    }
}



