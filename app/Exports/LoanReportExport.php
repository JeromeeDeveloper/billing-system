<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\LoanForecast;
use App\Models\RemittancePreview;
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
use Illuminate\Support\Facades\Log;

class LoanReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
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
        return 'Loan Report';
    }

    public function headings(): array
    {
        return [
            'Member Information',
            'Branch',
            'Employee ID',
            'Member Name',
            'Loan Account Details',
            'Loan Account Number',
            'Product Code',
            'Billing Information',
            'Total Due (Billed)',
            'Principal Due',
            'Interest Due',
            'Penalty Due',
            'Remittance Information',
            'Total Remitted',
            'Remaining Balance',
            'Payment Status',
            'Account Status',
            'Open Date',
            'Maturity Date',
            'Approval Number',
            'Billing Period'
        ];
    }

    public function collection()
    {
        $query = Member::with(['branch', 'loanForecasts', 'loanProductMembers.loanProduct'])
            ->where('billing_period', $this->billingPeriod)
            ->whereHas('loanForecasts', function($q) {
                $q->where('billing_period', $this->billingPeriod);
            });

        // Filter by branch if specified
        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        $members = $query->get();
        $reportData = new Collection();

        foreach ($members as $member) {
            // Get remittance data for this member
            $remittanceData = RemittancePreview::where('member_id', $member->id)
                ->where('billing_period', $this->billingPeriod)
                ->first();

            $totalRemitted = $remittanceData ? $remittanceData->loans : 0;

            // Process each loan forecast for this member
            foreach ($member->loanForecasts as $forecast) {
                if ($forecast->billing_period !== $this->billingPeriod) {
                    continue;
                }

                // Extract product code from loan account number
                $segments = explode('-', $forecast->loan_acct_no);
                $productCode = $segments[2] ?? 'N/A';

                // Get product name
                $productName = 'N/A';
                $loanProductMember = $member->loanProductMembers->first(function($lpm) use ($productCode) {
                    return $lpm->loanProduct && $lpm->loanProduct->product_code === $productCode;
                });
                if ($loanProductMember) {
                    $productName = $loanProductMember->loanProduct->product_name;
                }

                // Calculate remaining balance
                $remainingBalance = max(0, $forecast->total_due - $forecast->total_due_after_remittance);

                // Determine payment status
                $paymentStatus = 'Unpaid';
                if ($forecast->total_due_after_remittance > 0) {
                    if ($forecast->total_due_after_remittance >= $forecast->total_due) {
                        $paymentStatus = 'Fully Paid';
                    } else {
                        $paymentStatus = 'Partially Paid';
                    }
                }

                $reportData->push([
                    'member_info' => "{$member->fname} {$member->lname}",
                    'branch' => $member->branch ? $member->branch->name : 'N/A',
                    'emp_id' => $member->emp_id ?? 'N/A',
                    'member_name' => "{$member->fname} {$member->lname}",
                    'loan_details' => $productName,
                    'loan_acct_no' => $forecast->loan_acct_no,
                    'product_code' => $productCode,
                    'billing_info' => 'Current Period',
                    'total_due' => $forecast->total_due,
                    'principal_due' => $forecast->principal_due,
                    'interest_due' => $forecast->interest_due,
                    'penalty_due' => $forecast->penalty_due,
                    'remittance_info' => 'Remittance Data',
                    'total_remitted' => $forecast->total_due_after_remittance,
                    'remaining_balance' => $remainingBalance,
                    'payment_status' => $paymentStatus,
                    'account_status' => $forecast->account_status,
                    'open_date' => $forecast->open_date ?: 'N/A',
                    'maturity_date' => $forecast->maturity_date ? $forecast->maturity_date->format('Y-m-d') : 'N/A',
                    'approval_no' => $forecast->approval_no ?? 'N/A',
                    'billing_period' => $this->billingPeriod
                ]);
            }
        }

        return $reportData;
    }

    public function map($row): array
    {
        return [
            $row['member_info'],
            $row['branch'],
            $row['emp_id'],
            $row['member_name'],
            $row['loan_details'],
            $row['loan_acct_no'],
            $row['product_code'],
            $row['billing_info'],
            number_format($row['total_due'], 2),
            number_format($row['principal_due'], 2),
            number_format($row['interest_due'], 2),
            number_format($row['penalty_due'], 2),
            $row['remittance_info'],
            number_format($row['total_remitted'], 2),
            number_format($row['remaining_balance'], 2),
            $row['payment_status'],
            ucfirst($row['account_status']),
            $row['open_date'],
            $row['maturity_date'],
            $row['approval_no'],
            $row['billing_period']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:V1')->applyFromArray([
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
        foreach (range('A', 'V') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Style data rows
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle('A2:V' . $highestRow)->applyFromArray([
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
            $sheet->getStyle('I2:L' . $highestRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('N2:O' . $highestRow)->getNumberFormat()->setFormatCode('#,##0.00');

            // Color code payment status
            for ($row = 2; $row <= $highestRow; $row++) {
                $paymentStatus = $sheet->getCell('P' . $row)->getValue();
                switch ($paymentStatus) {
                    case 'Fully Paid':
                        $sheet->getStyle('P' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $sheet->getStyle('P' . $row)->getFill()->getStartColor()->setRGB('90EE90');
                        break;
                    case 'Partially Paid':
                        $sheet->getStyle('P' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $sheet->getStyle('P' . $row)->getFill()->getStartColor()->setRGB('FFD700');
                        break;
                    case 'Unpaid':
                        $sheet->getStyle('P' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $sheet->getStyle('P' . $row)->getFill()->getStartColor()->setRGB('FFB6C1');
                        break;
                }
            }
        }

        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Member Information
            'B' => 15, // Branch
            'C' => 12, // Employee ID
            'D' => 25, // Member Name
            'E' => 25, // Loan Account Details
            'F' => 25, // Loan Account Number
            'G' => 12, // Product Code
            'H' => 15, // Billing Information
            'I' => 15, // Total Due (Billed)
            'J' => 15, // Principal Due
            'K' => 15, // Interest Due
            'L' => 15, // Penalty Due
            'M' => 15, // Remittance Information
            'N' => 15, // Total Remitted
            'O' => 15, // Remaining Balance
            'P' => 15, // Payment Status
            'Q' => 15, // Account Status
            'R' => 12, // Open Date
            'S' => 12, // Maturity Date
            'T' => 15, // Approval Number
            'U' => 15, // Billing Period
        ];
    }
}
