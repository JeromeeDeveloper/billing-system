<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\LoanProduct;
use App\Models\SavingProduct;
use App\Models\ShareProduct;
use App\Models\LoanRemittance;
use App\Models\Savings;
use App\Models\Remittance;
use App\Models\RemittanceBatch;
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
        // Get latest remittance import date for this billing period
        $remitDate = RemittanceBatch::where('billing_period', $this->billingPeriod)
            ->orderByDesc('imported_at')->value('imported_at');
        $remitDateStr = $remitDate ? \Carbon\Carbon::parse($remitDate)->format('F d, Y') : '';
        $rows[] = ['', 'Remittance Date', $remitDateStr];
        $rows[] = ['', 'For Billing Period', $this->billingPeriod];
        $rows[] = [''];
        $rows[] = [''];
        $rows[] = [''];
        $rows[] = ['Branch Name:', $branch->name];
        $rows[] = ['PRODUCT', 'AMOUNT', 'COUNT'];

        // --- LOAN PRODUCTS ---
        $loanRemits = LoanRemittance::where('billing_period', $this->billingPeriod)
            ->whereHas('member', function($q) use ($branch) {
                $q->where('branch_id', $branch->id);
            })
            ->where('remitted_amount', '>', 0)
            ->with('loanForecast')
            ->get();
        $loanByProduct = $loanRemits->groupBy(function($remit) {
            $forecast = $remit->loanForecast;
            if ($forecast && $forecast->loan_acct_no) {
                $segments = explode('-', $forecast->loan_acct_no);
                return $segments[2] ?? null;
            }
            return null;
        });
        foreach ($loanByProduct as $productCode => $remits) {
            if (!$productCode) continue;
            $product = LoanProduct::where('product_code', $productCode)->first();
            if (!$product) continue;
            $totalAmount = $remits->sum('remitted_amount');
            $memberCount = $remits->unique('member_id')->count();
            $rows[] = [$product->product, $totalAmount > 0 ? $totalAmount : '', $memberCount > 0 ? $memberCount : ''];
        }

        // --- SAVINGS PRODUCTS ---
        $savingsRemits = Savings::whereHas('member', function($q) use ($branch) {
                $q->where('branch_id', $branch->id);
            })
            ->where('remittance_amount', '>', 0)
            ->get();
        $savingsByProduct = $savingsRemits->groupBy('product_code');
        foreach ($savingsByProduct as $productCode => $remits) {
            $product = SavingProduct::where('product_code', $productCode)->first();
            if (!$product) continue;
            $totalAmount = $remits->sum('remittance_amount');
            $memberCount = $remits->unique('member_id')->count();
            $rows[] = [$product->product_name, $totalAmount > 0 ? $totalAmount : '', $memberCount > 0 ? $memberCount : ''];
        }

        // --- SHARE PRODUCTS ---
        $shareRemits = Remittance::where('branch_id', $branch->id)
            ->where('share_dep', '>', 0)
            ->get();
        $totalShare = $shareRemits->sum('share_dep');
        $shareCount = $shareRemits->unique('member_id')->count();
        if ($totalShare > 0) {
            $rows[] = ['Shares', $totalShare, $shareCount];
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
