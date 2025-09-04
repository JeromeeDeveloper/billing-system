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

class BranchConsolidatedRemittanceReportExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithMultipleSheets
{
    protected $billingPeriod;
    protected $branchId;
    protected $regularRemittances;
    protected $specialRemittances;
    protected $loansSavingsPreviewPaginated;
    protected $sharesPreviewPaginated;
    protected $remittanceReports;

    public function __construct($billingPeriod = null, $branchId = null)
    {
        $this->billingPeriod = $billingPeriod ?? Auth::user()->billing_period;
        $this->branchId = $branchId ?? Auth::user()->branch_id;
        $this->loadData();
    }

    protected function loadData()
    {
        // Load regular remittances from RemittanceReport (branch filtered)
        $this->regularRemittances = Remittance::with('member')
            ->whereHas('member', function($query) {
                $query->where('billing_period', $this->billingPeriod)
                      ->where('branch_id', $this->branchId);
            })
            ->get();

        // Load special remittances from RemittanceReport (branch filtered)
        $this->specialRemittances = Remittance::with('member')
            ->whereHas('member', function($query) {
                $query->where('billing_period', $this->billingPeriod)
                      ->where('branch_id', $this->branchId);
            })
            ->get();

        // Load preview data from RemittancePreview (branch filtered)
        $this->loansSavingsPreviewPaginated = RemittancePreview::whereHas('member', function($query) {
                $query->where('branch_id', $this->branchId);
            })
            ->where('remittance_type', 'loans_savings')
            ->where('billing_period', $this->billingPeriod)
            ->get();

        $this->sharesPreviewPaginated = RemittancePreview::whereHas('member', function($query) {
                $query->where('branch_id', $this->branchId);
            })
            ->where('remittance_type', 'shares')
            ->where('billing_period', $this->billingPeriod)
            ->get();

        // Also load RemittanceReport data for accumulated billing data (branch filtered)
        $this->remittanceReports = \App\Models\RemittanceReport::where('period', $this->billingPeriod)
            ->whereHas('member', function($query) {
                $query->where('branch_id', $this->branchId);
            })
            ->get();
    }

    public function sheets(): array
    {
        return [
            'Matched' => new BranchMatchedSheet($this->loansSavingsPreviewPaginated, $this->sharesPreviewPaginated, $this->remittanceReports, $this->branchId),
            'Unmatched' => new BranchUnmatchedSheet($this->loansSavingsPreviewPaginated, $this->sharesPreviewPaginated, $this->branchId),
            'No Branch' => new BranchNoBranchSheet($this->loansSavingsPreviewPaginated, $this->sharesPreviewPaginated, $this->branchId),
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
            'A' => 25, 'B' => 15, 'C' => 15, 'D' => 15, 'E' => 15, 'F' => 15, 'G' => 15, 'H' => 20
        ];
    }
}

class BranchMatchedSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $loansSavingsPreviewPaginated;
    protected $sharesPreviewPaginated;
    protected $remittanceReports;
    protected $branchId;

    public function __construct($loansSavingsPreviewPaginated, $sharesPreviewPaginated, $remittanceReports = null, $branchId = null)
    {
        $this->loansSavingsPreviewPaginated = $loansSavingsPreviewPaginated;
        $this->sharesPreviewPaginated = $sharesPreviewPaginated;
        $this->remittanceReports = $remittanceReports;
        $this->branchId = $branchId;
    }

    public function array(): array
    {
        $rows = [];

        // Process RemittanceReport data (accumulated billing data) - these are all matched (branch filtered)
        if ($this->remittanceReports) {
            foreach ($this->remittanceReports as $report) {
                                if ($report->remitted_loans > 0 || $report->remitted_savings > 0 || $report->remitted_shares > 0) {
                    $remittedLoans = (float)($report->remitted_loans ?? 0);
                    $remittedSavings = (float)($report->remitted_savings ?? 0);
                    $remittedShares = (float)($report->remitted_shares ?? 0);

                    $memberName = $report->member_name ?? 'N/A';
                    $key = $memberName;

                    if (!isset($rows[$key])) {
                        $rows[$key] = [
                            'name' => $memberName,
                            'loans' => 0,
                            'savings' => 0,
                            'shares' => 0,
                            'total' => 0
                        ];
                    }

                    $rows[$key]['loans'] += $remittedLoans;
                    $rows[$key]['savings'] += $remittedSavings;
                    $rows[$key]['shares'] += $remittedShares;
                    $rows[$key]['total'] += $remittedLoans + $remittedSavings + $remittedShares;
                }
            }
        }

        // Process Loans & Savings Preview - Matched only (branch filtered)
        foreach ($this->loansSavingsPreviewPaginated as $row) {
            $status = is_array($row) ? ($row['status'] ?? '') : ($row->status ?? '');
            if ($status === 'success') {
                $memberId = is_array($row) ? ($row['member_id'] ?? null) : ($row->member_id ?? null);
                $member = Member::find($memberId);
                if (!$member || $member->branch_id != $this->branchId) {
                    continue;
                }

                $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                $loans = (float)(is_array($row) ? ($row['loans'] ?? 0) : ($row->loans ?? 0));
                $savings = (float)(is_array($row) ? ($row['savings'] ?? 0) : ($row->savings ?? 0));

                $key = $name;

                if (!isset($rows[$key])) {
                    $rows[$key] = [
                        'name' => $name,
                        'loans' => 0,
                        'savings' => 0,
                        'shares' => 0,
                        'total' => 0
                    ];
                }

                $rows[$key]['loans'] += $loans;
                $rows[$key]['savings'] += $savings;
                $rows[$key]['total'] += $loans + $savings;
            }
        }

        // Process Shares Preview - Matched only (branch filtered)
        foreach ($this->sharesPreviewPaginated as $row) {
            $status = is_array($row) ? ($row['status'] ?? '') : ($row->status ?? '');
            if ($status === 'success') {
                $memberId = is_array($row) ? ($row['member_id'] ?? null) : ($row->member_id ?? null);
                $member = Member::find($memberId);
                if (!$member || $member->branch_id != $this->branchId) {
                    continue;
                }

                $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                $shareAmount = (float)(is_array($row) ? ($row['share_amount'] ?? 0) : ($row->share_amount ?? 0));

                $key = $name;

                if (!isset($rows[$key])) {
                    $rows[$key] = [
                        'name' => $name,
                        'loans' => 0,
                        'savings' => 0,
                        'shares' => 0,
                        'total' => 0
                    ];
                }

                $rows[$key]['shares'] += $shareAmount;
                $rows[$key]['total'] += $shareAmount;
            }
        }

        // Convert consolidated data back to array format
        $finalRows = [];
        foreach ($rows as $row) {
            $finalRows[] = [
                $row['name'],
                'Matched'
            ];
        }

        return $finalRows;
    }

    public function headings(): array
    {
        return [
            ['Consolidated Remittance Report - Matched Records'],
            ['Generated on', now()->format('F d, Y H:i:s')],
            [''],
            ['Member', 'Status']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            'A1:B' . $lastRow => ['borders' => ['allBorders' => ['borderStyle' => 'thin']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, 'B' => 20
        ];
    }

    public function title(): string
    {
        return 'Matched';
    }
}

class BranchUnmatchedSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $loansSavingsPreviewPaginated;
    protected $sharesPreviewPaginated;
    protected $branchId;

    public function __construct($loansSavingsPreviewPaginated, $sharesPreviewPaginated, $branchId = null)
    {
        $this->loansSavingsPreviewPaginated = $loansSavingsPreviewPaginated;
        $this->sharesPreviewPaginated = $sharesPreviewPaginated;
        $this->branchId = $branchId;
    }

    public function array(): array
    {
        $rows = [];

        // Process Loans & Savings Preview - Unmatched only (branch filtered)
        foreach ($this->loansSavingsPreviewPaginated as $row) {
            $status = is_array($row) ? ($row['status'] ?? '') : ($row->status ?? '');
            $message = is_array($row) ? ($row['message'] ?? '') : ($row->message ?? '');
            $isNoBranch = str_contains(strtolower($message), 'no branch');

            if ($status === 'error' && !$isNoBranch) {
                $memberId = is_array($row) ? ($row['member_id'] ?? null) : ($row->member_id ?? null);
                $member = Member::find($memberId);
                if (!$member || $member->branch_id != $this->branchId) {
                    continue;
                }

                $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                $loans = (float)(is_array($row) ? ($row['loans'] ?? 0) : ($row->loans ?? 0));
                $savings = (float)(is_array($row) ? ($row['savings'] ?? 0) : ($row->savings ?? 0));

                $key = $name;

                if (!isset($rows[$key])) {
                    $rows[$key] = [
                        'name' => $name,
                        'loans' => 0,
                        'savings' => 0,
                        'shares' => 0,
                        'total' => 0
                    ];
                }

                $rows[$key]['loans'] += $loans;
                $rows[$key]['savings'] += $savings;
                $rows[$key]['total'] += $loans + $savings;
            }
        }

        // Process Shares Preview - Unmatched only (branch filtered)
        foreach ($this->sharesPreviewPaginated as $row) {
            $status = is_array($row) ? ($row['status'] ?? '') : ($row->status ?? '');
            $message = is_array($row) ? ($row['message'] ?? '') : ($row->message ?? '');
            $isNoBranch = str_contains(strtolower($message), 'no branch');

            if ($status === 'error' && !$isNoBranch) {
                $memberId = is_array($row) ? ($row['member_id'] ?? null) : ($row->member_id ?? null);
                $member = Member::find($memberId);
                if (!$member || $member->branch_id != $this->branchId) {
                    continue;
                }

                $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                $shareAmount = (float)(is_array($row) ? ($row['share_amount'] ?? 0) : ($row->share_amount ?? 0));

                $key = $name;

                if (!isset($rows[$key])) {
                    $rows[$key] = [
                        'name' => $name,
                        'loans' => 0,
                        'savings' => 0,
                        'shares' => 0,
                        'total' => 0
                    ];
                }

                $rows[$key]['shares'] += $shareAmount;
                $rows[$key]['total'] += $shareAmount;
            }
        }

        // Convert consolidated data back to array format
        $finalRows = [];
        foreach ($rows as $row) {
            $finalRows[] = [
                $row['name'],
                'Unmatched'
            ];
        }

        return $finalRows;
    }

    public function headings(): array
    {
        return [
            ['Consolidated Remittance Report - Unmatched Records'],
            ['Generated on', now()->format('F d, Y H:i:s')],
            [''],
            ['Member', 'Status']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            'A1:B' . $lastRow => ['borders' => ['allBorders' => ['borderStyle' => 'thin']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, 'B' => 20
        ];
    }

    public function title(): string
    {
        return 'Unmatched';
    }
}

class BranchNoBranchSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $loansSavingsPreviewPaginated;
    protected $sharesPreviewPaginated;
    protected $branchId;

    public function __construct($loansSavingsPreviewPaginated, $sharesPreviewPaginated, $branchId = null)
    {
        $this->loansSavingsPreviewPaginated = $loansSavingsPreviewPaginated;
        $this->sharesPreviewPaginated = $sharesPreviewPaginated;
        $this->branchId = $branchId;
    }

    public function array(): array
    {
        $rows = [];

        // Process Loans & Savings Preview - No Branch only (branch filtered)
        foreach ($this->loansSavingsPreviewPaginated as $row) {
            $message = is_array($row) ? ($row['message'] ?? '') : ($row->message ?? '');
            $isNoBranch = str_contains(strtolower($message), 'no branch');

            if ($isNoBranch) {
                $memberId = is_array($row) ? ($row['member_id'] ?? null) : ($row->member_id ?? null);
                $member = Member::find($memberId);
                if (!$member || $member->branch_id != $this->branchId) {
                    continue;
                }

                $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                $loans = (float)(is_array($row) ? ($row['loans'] ?? 0) : ($row->loans ?? 0));
                $savings = (float)(is_array($row) ? ($row['savings'] ?? 0) : ($row->savings ?? 0));

                $key = $name;

                if (!isset($rows[$key])) {
                    $rows[$key] = [
                        'name' => $name,
                        'loans' => 0,
                        'savings' => 0,
                        'shares' => 0,
                        'total' => 0
                    ];
                }

                $rows[$key]['loans'] += $loans;
                $rows[$key]['savings'] += $savings;
                $rows[$key]['total'] += $loans + $savings;
            }
        }

        // Process Shares Preview - No Branch only (branch filtered)
        foreach ($this->sharesPreviewPaginated as $row) {
            $message = is_array($row) ? ($row['message'] ?? '') : ($row->message ?? '');
            $isNoBranch = str_contains(strtolower($message), 'no branch');

            if ($isNoBranch) {
                $memberId = is_array($row) ? ($row['member_id'] ?? null) : ($row->member_id ?? null);
                $member = Member::find($memberId);
                if (!$member || $member->branch_id != $this->branchId) {
                    continue;
                }

                $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                $shareAmount = (float)(is_array($row) ? ($row['share_amount'] ?? 0) : ($row->share_amount ?? 0));

                $key = $name;

                if (!isset($rows[$key])) {
                    $rows[$key] = [
                        'name' => $name,
                        'loans' => 0,
                        'savings' => 0,
                        'shares' => 0,
                        'total' => 0
                    ];
                }

                $rows[$key]['shares'] += $shareAmount;
                $rows[$key]['total'] += $shareAmount;
            }
        }

        // Convert consolidated data back to array format
        $finalRows = [];
        foreach ($rows as $row) {
            $finalRows[] = [
                $row['name'],
                'No Branch'
            ];
        }

        return $finalRows;
    }

    public function headings(): array
    {
        return [
            ['Consolidated Remittance Report - No Branch Records'],
            ['Generated on', now()->format('F d, Y H:i:s')],
            [''],
            ['Member', 'Status']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            'A1:B' . $lastRow => ['borders' => ['allBorders' => ['borderStyle' => 'thin']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, 'B' => 20
        ];
    }

    public function title(): string
    {
        return 'No Branch';
    }
}
