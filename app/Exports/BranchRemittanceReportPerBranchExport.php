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

class BranchRemittanceReportPerBranchExport implements FromArray, WithStyles, WithColumnWidths, WithTitle
{
    protected $loanProducts;
    protected $savingProducts;
    protected $billingPeriod;
    protected $branchId;

    public function __construct($billingPeriod = null, $branchId = null)
    {
        $this->loanProducts = LoanProduct::all();
        $this->savingProducts = SavingProduct::all();
        $this->billingPeriod = $billingPeriod ?? now()->format('Y-m');
        $this->branchId = $branchId;
    }

    public function array(): array
    {
        $rows = [];
        $branch = Branch::find($this->branchId);
        if (!$branch) return [['No data for this branch']];
        $rows[] = ['', 'Remittance Report for branch (' . $branch->name . ')'];
        $rows[] = ['', 'Remittance Date', now()->format('F d, Y')];
        $rows[] = ['', 'For Billing Period', $this->billingPeriod];
        $rows[] = [''];
        $rows[] = [''];
        $rows[] = [''];
        $rows[] = ['Branch Name:', $branch->name];
        $rows[] = ['PRODUCT', 'AMOUNT', 'COUNT'];

        // Loans
        $remitted = RemittancePreview::where('billing_period', $this->billingPeriod)
            ->where('status', 'success')
            ->whereHas('member', function($q) use ($branch) {
                $q->where('branch_id', $branch->id);
            })
            ->get();
        foreach ($this->loanProducts as $product) {
            $totalAmount = $remitted->sum('loans');
            $count = $remitted->where('loans', '>', 0)->count();
            $rows[] = [$product->product, $totalAmount, $count];
        }
        // Savings
        foreach ($this->savingProducts as $product) {
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
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [];
        $styles[8] = ['font' => ['bold' => true]];
        $styles[1] = ['font' => ['bold' => true, 'size' => 14]];
        $styles[2] = ['font' => ['bold' => true]];
        $styles[3] = ['font' => ['bold' => true]];
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
