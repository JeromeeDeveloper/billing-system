<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\LoanProduct;
use App\Models\SavingProduct;
use App\Models\RemittancePreview;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RemittanceReportPerBranchExport implements FromArray, WithStyles, WithColumnWidths, WithTitle
{
    protected $loanProducts;
    protected $savingProducts;
    protected $billingPeriod;

    public function __construct($billingPeriod = null)
    {
        $this->loanProducts = LoanProduct::all();
        $this->savingProducts = SavingProduct::all();
        $this->billingPeriod = $billingPeriod ?? now()->format('Y-m');
    }

    public function array(): array
    {
        $rows = [];
        $branches = Branch::all();
        foreach ($branches as $branch) {
            $rows[] = ['', 'Remittance Report for branch (' . $branch->name . ')'];
            $rows[] = ['', 'Remittance Date', now()->format('F d, Y')];
            $rows[] = ['', 'For Billing Period', $this->billingPeriod];
            $rows[] = [''];
            $rows[] = [''];
            $rows[] = [''];
            $rows[] = ['Branch Name:', $branch->name];
            $rows[] = ['PRODUCT', 'AMOUNT', 'COUNT'];

            // Loans
            $loanTotalDisplayed = false;
            foreach ($this->loanProducts as $product) {
                $remitted = RemittancePreview::where('billing_period', $this->billingPeriod)
                    ->where('status', 'success')
                    ->whereHas('member', function($q) use ($branch) {
                        $q->where('branch_id', $branch->id);
                    })
                    ->get();
                if (!$loanTotalDisplayed) {
                    $totalAmount = $remitted->sum('loans');
                    $count = $remitted->where('loans', '>', 0)->count();
                    $rows[] = [$product->product, $totalAmount > 0 ? $totalAmount : '', $count > 0 ? $count : ''];
                    $loanTotalDisplayed = true;
                } else {
                    $rows[] = [$product->product, '', ''];
                }
            }
            // Savings
            foreach ($this->savingProducts as $product) {
                $remitted = RemittancePreview::where('billing_period', $this->billingPeriod)
                    ->where('status', 'success')
                    ->whereHas('member', function($q) use ($branch) {
                        $q->where('branch_id', $branch->id);
                    })
                    ->get();
                $totalAmount = $remitted->sum(function($item) use ($product) {
                    if (is_array($item->savings) && isset($item->savings['distribution'])) {
                        foreach ($item->savings['distribution'] as $dist) {
                            if (($dist['product_code'] ?? null) == $product->product_code) {
                                return $dist['amount'] ?? 0;
                            }
                        }
                    }
                    return 0;
                });
                $count = $remitted->filter(function($item) use ($product) {
                    if (is_array($item->savings) && isset($item->savings['distribution'])) {
                        foreach ($item->savings['distribution'] as $dist) {
                            if (($dist['product_code'] ?? null) == $product->product_code && ($dist['amount'] ?? 0) > 0) {
                                return true;
                            }
                        }
                    }
                    return false;
                })->count();
                $rows[] = [$product->product_name, $totalAmount, $count];
            }
            $rows[] = [''];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Bold for header rows (A8, B8, C8 for each branch)
        $row = 8;
        $branches = Branch::count();
        $styles = [];
        for ($i = 0; $i < $branches; $i++) {
            $styles[$row] = ['font' => ['bold' => true]];
            $row += 2 + LoanProduct::count() + SavingProduct::count() + 1; // 1 for blank row
        }
        // Bold for B1, B2, B3
        $row = 1;
        $styles[$row] = ['font' => ['bold' => true, 'size' => 14]];
        $styles[$row+1] = ['font' => ['bold' => true]];
        $styles[$row+2] = ['font' => ['bold' => true]];
        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 20,
            'C' => 10,
        ];
    }

    public function title(): string
    {
        return 'Remittance Report Per Branch';
    }
}
