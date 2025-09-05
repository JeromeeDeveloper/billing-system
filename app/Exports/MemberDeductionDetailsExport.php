<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\SavingProduct;
use App\Models\ShareProduct;
use App\Models\LoanProduct;
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

class MemberDeductionDetailsExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $billingPeriod;

    public function __construct($billingPeriod = null)
    {
        $this->billingPeriod = $billingPeriod ?? Auth::user()->billing_period;
    }

    public function array(): array
    {
        $rows = [];

        // Get all PGB members
        $members = Member::where('member_tagging', 'PGB')
            ->with(['savings', 'shares', 'loanForecasts'])
            ->get();

        // Get all product codes with their names
        $savingProducts = SavingProduct::all()->keyBy('product_code');
        $shareProducts = ShareProduct::all()->keyBy('product_code');
        $loanProducts = LoanProduct::all()->keyBy('product_code');

        // Collect all unique product codes that have deduction amounts
        $allProductCodes = collect();

        foreach ($members as $member) {
            // Check savings with deduction amounts
            foreach ($member->savings as $saving) {
                if ($saving->deduction_amount > 0) {
                    $allProductCodes->push($saving->product_code);
                }
            }

            // Check shares with deduction amounts
            foreach ($member->shares as $share) {
                if ($share->deduction_amount > 0) {
                    $allProductCodes->push($share->product_code);
                }
            }

            // Check loan forecasts with deduction amounts (if they have product_code)
            foreach ($member->loanForecasts as $loan) {
                if (isset($loan->product_code) && $loan->product_code) {
                    $allProductCodes->push($loan->product_code);
                }
            }
        }

        $uniqueProductCodes = $allProductCodes->unique()->sort()->values();

        // Process each member
        foreach ($members as $member) {
            $row = [$member->cid];

            $hasDeductions = false;

            // Add deduction amounts for each product code
            foreach ($uniqueProductCodes as $productCode) {
                $deductionAmount = 0;

                // Check savings
                $saving = $member->savings->where('product_code', $productCode)->first();
                if ($saving && $saving->deduction_amount > 0) {
                    $deductionAmount = $saving->deduction_amount;
                    $hasDeductions = true;
                }

                // Check shares
                $share = $member->shares->where('product_code', $productCode)->first();
                if ($share && $share->deduction_amount > 0) {
                    $deductionAmount = $share->deduction_amount;
                    $hasDeductions = true;
                }

                // Check loans (if they have product_code)
                $loan = $member->loanForecasts->where('product_code', $productCode)->first();
                if ($loan && isset($loan->product_code) && $loan->product_code) {
                    // For loans, we might use total_due or another field as deduction amount
                    // Adjust this based on your business logic
                    if (isset($loan->total_due) && $loan->total_due > 0) {
                        $deductionAmount = $loan->total_due;
                        $hasDeductions = true;
                    }
                }

                $row[] = $deductionAmount > 0 ? $deductionAmount : '';
            }

            // Only add row if member has any deductions
            if ($hasDeductions) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        $headings = ['CoreID'];

        // Get all unique product codes that have deduction amounts
        $members = Member::where('member_tagging', 'PGB')
            ->with(['savings', 'shares', 'loanForecasts'])
            ->get();

        $allProductCodes = collect();

        foreach ($members as $member) {
            // Check savings with deduction amounts
            foreach ($member->savings as $saving) {
                if ($saving->deduction_amount > 0) {
                    $allProductCodes->push($saving->product_code);
                }
            }

            // Check shares with deduction amounts
            foreach ($member->shares as $share) {
                if ($share->deduction_amount > 0) {
                    $allProductCodes->push($share->product_code);
                }
            }

            // Check loan forecasts with product codes
            foreach ($member->loanForecasts as $loan) {
                if (isset($loan->product_code) && $loan->product_code) {
                    $allProductCodes->push($loan->product_code);
                }
            }
        }

        $uniqueProductCodes = $allProductCodes->unique()->sort()->values();

        // Get product names for headers
        $savingProducts = SavingProduct::all()->keyBy('product_code');
        $shareProducts = ShareProduct::all()->keyBy('product_code');
        $loanProducts = LoanProduct::all()->keyBy('product_code');

        foreach ($uniqueProductCodes as $productCode) {
            $productName = '';

            // Try to find product name
            if ($savingProducts->has($productCode)) {
                $productName = $savingProducts[$productCode]->product_name ?? '';
            } elseif ($shareProducts->has($productCode)) {
                $productName = $shareProducts[$productCode]->product_name ?? '';
            } elseif ($loanProducts->has($productCode)) {
                $productName = $loanProducts[$productCode]->product ?? '';
            }

            $header = $productName ? "{$productCode} - {$productName}" : $productCode;
            $headings[] = $header;
        }

        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = $sheet->getHighestColumn();

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E6E6FA']
                ],
                'alignment' => ['horizontal' => 'center']
            ],
            'A1:' . $lastCol . '1' => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ],
            'A1:' . $lastCol . $lastRow => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]
        ];
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 15]; // CoreID column

        // Get all unique product codes to set column widths
        $members = Member::where('member_tagging', 'PGB')
            ->with(['savings', 'shares', 'loanForecasts'])
            ->get();

        $allProductCodes = collect();

        foreach ($members as $member) {
            foreach ($member->savings as $saving) {
                if ($saving->deduction_amount > 0) {
                    $allProductCodes->push($saving->product_code);
                }
            }
            foreach ($member->shares as $share) {
                if ($share->deduction_amount > 0) {
                    $allProductCodes->push($share->product_code);
                }
            }
            foreach ($member->loanForecasts as $loan) {
                if (isset($loan->product_code) && $loan->product_code) {
                    $allProductCodes->push($loan->product_code);
                }
            }
        }

        $uniqueProductCodes = $allProductCodes->unique()->sort()->values();

        // Set column widths for product columns
        $col = 'B';
        foreach ($uniqueProductCodes as $index => $productCode) {
            $widths[$col] = 20;
            $col++;
        }

        return $widths;
    }

    public function title(): string
    {
        return 'Member Deduction Details';
    }
}
