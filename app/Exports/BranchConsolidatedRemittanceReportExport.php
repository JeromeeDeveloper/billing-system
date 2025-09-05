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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;

class BranchConsolidatedRemittanceReportExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
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

    public function array(): array
    {
        $rows = [];

        // Process Loans & Savings Preview - Unmatched only (branch filtered)
        foreach ($this->loansSavingsPreviewPaginated as $row) {
            $status = is_array($row) ? ($row['status'] ?? '') : ($row->status ?? '');
            $message = is_array($row) ? ($row['message'] ?? '') : ($row->message ?? '');
            $isNoBranch = str_contains(strtolower($message), 'no branch');

            if ($status === 'error' && !$isNoBranch) {
                $cid = is_array($row) ? ($row['cid'] ?? 'N/A') : ($row->cid ?? 'N/A');
                $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                $loans = (float)(is_array($row) ? ($row['loans'] ?? 0) : ($row->loans ?? 0));
                $savings = (float)(is_array($row) ? ($row['savings'] ?? 0) : ($row->savings ?? 0));
                $totalAmount = $loans + $savings;

                $key = $cid;

                if (!isset($rows[$key])) {
                    $rows[$key] = [
                        'cid' => $cid,
                        'name' => $name,
                        'loans' => 0,
                        'savings' => 0,
                        'shares' => 0,
                        'total' => 0
                    ];
                }

                $rows[$key]['loans'] += $loans;
                $rows[$key]['savings'] += $savings;
                $rows[$key]['total'] += $totalAmount;
            }
        }

        // Process Shares Preview - Unmatched only (branch filtered)
        foreach ($this->sharesPreviewPaginated as $row) {
            $status = is_array($row) ? ($row['status'] ?? '') : ($row->status ?? '');
            $message = is_array($row) ? ($row['message'] ?? '') : ($row->message ?? '');
            $isNoBranch = str_contains(strtolower($message), 'no branch');

            if ($status === 'error' && !$isNoBranch) {
                $cid = is_array($row) ? ($row['cid'] ?? 'N/A') : ($row->cid ?? 'N/A');
                $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                $shareAmount = (float)(is_array($row) ? ($row['share_amount'] ?? 0) : ($row->share_amount ?? 0));

                $key = $cid;

                if (!isset($rows[$key])) {
                    $rows[$key] = [
                        'cid' => $cid,
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
                $row['cid'],
                $row['name'],
                $row['loans'] + $row['savings'] + $row['shares'] // Total amount remitted
            ];
        }

        return $finalRows;
    }

    public function headings(): array
    {
        return [
            ['Unmatched Members Report'],
            ['Generated on', now()->format('F d, Y H:i:s')],
            [''],
            ['CID', 'Member Name', 'Amount Remitted']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            'A1:C' . $lastRow => ['borders' => ['allBorders' => ['borderStyle' => 'thin']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // CID
            'B' => 30, // Member Name
            'C' => 20  // Amount Remitted
        ];
    }

    public function title(): string
    {
        return 'Unmatched Members';
    }
}

