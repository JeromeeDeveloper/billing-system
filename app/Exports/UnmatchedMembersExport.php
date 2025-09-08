<?php

namespace App\Exports;

use App\Models\RemittancePreview;
use App\Models\RemittanceReport;
use App\Models\Member;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Illuminate\Support\Facades\Auth;

class UnmatchedMembersExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $billingPeriod;
    protected $userId;
    protected $loansSavingsPreviewPaginated;
    protected $sharesPreviewPaginated;

    public function __construct($billingPeriod = null, $userId = null)
    {
        $this->billingPeriod = $billingPeriod ?? Auth::user()->billing_period;
        $this->userId = $userId ?? Auth::id();
        $this->loadData();
    }

    protected function loadData()
    {
        // Load preview data from RemittancePreview
        $this->loansSavingsPreviewPaginated = RemittancePreview::where('remittance_type', 'loans_savings')
            ->where('billing_period', $this->billingPeriod)
            ->get();

        $this->sharesPreviewPaginated = RemittancePreview::where('remittance_type', 'shares')
            ->where('billing_period', $this->billingPeriod)
            ->get();
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
                $cid = is_array($row) ? ($row['cid'] ?? null) : ($row->cid ?? null);
                $name = is_array($row) ? ($row['name'] ?? null) : ($row->name ?? null);

                // If CID is missing attempt to resolve via member_id, then emp_id, then name
                if (!$cid) {
                    $member = null;

                    // 1) member_id
                    $memberId = is_array($row) ? ($row['member_id'] ?? null) : ($row->member_id ?? null);
                    if ($memberId) {
                        $member = Member::find($memberId);
                    }

                    // 2) emp_id fallback
                    if (!$member) {
                        $empId = is_array($row) ? ($row['emp_id'] ?? null) : ($row->emp_id ?? null);
                        if ($empId) {
                            $member = Member::where('emp_id', $empId)->first();
                        }
                    }

                    // 3) name fallback (basic split: last token = lname, rest = fname)
                    if (!$member && $name) {
                        $parts = preg_split('/\s+/', trim($name));
                        if ($parts && count($parts) >= 2) {
                            $last = array_pop($parts);
                            $first = implode(' ', $parts);
                            $member = Member::where('fname', $first)->where('lname', $last)->first();
                            if (!$member) {
                                // Try reversed (common data entry variance)
                                $member = Member::where('fname', $last)->where('lname', $first)->first();
                            }
                        }
                    }

                    if ($member) {
                        $cid = $member->cid ?? null;
                        if (!$name) {
                            $name = trim(($member->fname ?? '') . ' ' . ($member->lname ?? '')) ?: null;
                        }
                    }
                }

                $cid = $cid ?: 'N/A';
                $name = $name ?: 'N/A';
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

        // Process Shares Preview - Unmatched only
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
            ['Billing Period', $this->billingPeriod],
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
            3 => ['font' => ['bold' => true]],
            5 => ['font' => ['bold' => true]],
            'A1:C' . $lastRow => ['borders' => ['allBorders' => ['borderStyle' => 'thin']]],
            'A5:C5' => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E6E6FA']
                ]
            ]
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
