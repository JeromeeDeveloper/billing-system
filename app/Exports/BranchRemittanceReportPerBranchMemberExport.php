<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\LoanProduct;
use App\Models\SavingProduct;
use App\Models\ShareProduct;
use App\Models\LoanRemittance;
use App\Models\Remittance;
use App\Models\Savings;
use App\Models\RemittanceBatch;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BranchRemittanceReportPerBranchMemberExport implements FromArray, WithStyles, WithColumnWidths, WithTitle
{
    protected $loanProducts;
    protected $savingProducts;
    protected $shareProducts;
    protected $billingPeriod;
    protected $branchId;

    public function __construct($billingPeriod = null, $branchId = null)
    {
        $this->loanProducts = LoanProduct::all();
        $this->savingProducts = SavingProduct::all();
        $this->shareProducts = ShareProduct::all();
        $this->billingPeriod = $billingPeriod ?? now()->format('Y-m');
        $this->branchId = $branchId;
    }

    public function array(): array
    {
        $rows = [];
        $branch = Branch::find($this->branchId);
        if (!$branch) return [['No data for this branch']];
        $rows[] = ['Remittance Report for ' . $branch->name];
        $remitDate = RemittanceBatch::where('billing_period', $this->billingPeriod)
            ->orderByDesc('imported_at')->value('imported_at');
        $remitDateStr = $remitDate ? \Carbon\Carbon::parse($remitDate)->format('F d, Y') : '';
        $rows[] = ['Remittance Date', $remitDateStr];
        $rows[] = ['For Billing Period', $this->billingPeriod];
        $rows[] = [''];

        // --- Determine products with remittances ---
        // Loans
        $loanRemits = LoanRemittance::where('billing_period', $this->billingPeriod)
            ->whereHas('member', function($q) use ($branch) {
                $q->where('branch_id', $branch->id);
            })
            ->where('remitted_amount', '>', 0)
            ->with('loanForecast')
            ->get();
        $loanProductCodes = $loanRemits->map(function($remit) {
            $forecast = $remit->loanForecast;
            if ($forecast && $forecast->loan_acct_no) {
                $segments = explode('-', $forecast->loan_acct_no);
                return $segments[2] ?? null;
            }
            return null;
        })->filter()->unique()->values();
        $loanProducts = LoanProduct::whereIn('product_code', $loanProductCodes)->get();

        // Shares
        $shareRemits = Remittance::where('branch_id', $branch->id)
            ->where('share_dep', '>', 0)
            ->get();
        $hasShare = $shareRemits->count() > 0;

        // Savings
        $savingsRemits = Savings::whereHas('member', function($q) use ($branch) {
                $q->where('branch_id', $branch->id);
            })
            ->where('remittance_amount', '>', 0)
            ->get();
        $savingsProductCodes = $savingsRemits->pluck('product_code')->filter()->unique()->values();
        $savingsProducts = SavingProduct::whereIn('product_code', $savingsProductCodes)->get();

        // --- Build header ---
        $header = ['Member Name'];
        foreach ($loanProducts as $product) {
            $header[] = $product->product;
        }
        if ($hasShare) {
            $header[] = 'Shares';
        }
        foreach ($savingsProducts as $product) {
            $header[] = $product->product_name;
        }
        $rows[] = $header;

        // --- Member rows ---
        $members = $branch->members;
        foreach ($members as $member) {
            $row = [$member->fname . ' ' . $member->lname];
            $hasRemit = false;
            // Loans
            foreach ($loanProducts as $product) {
                $remit = LoanRemittance::where('billing_period', $this->billingPeriod)
                    ->where('member_id', $member->id)
                    ->where('remitted_amount', '>', 0)
                    ->with('loanForecast')
                    ->get()
                    ->filter(function($remit) use ($product) {
                        $forecast = $remit->loanForecast;
                        if ($forecast && $forecast->loan_acct_no) {
                            $segments = explode('-', $forecast->loan_acct_no);
                            return ($segments[2] ?? null) == $product->product_code;
                        }
                        return false;
                    });
                $amount = $remit->sum('remitted_amount');
                if ($amount > 0) $hasRemit = true;
                $row[] = $amount > 0 ? $amount : '';
            }
            // Shares
            if ($hasShare) {
                $shareAmount = Remittance::where('branch_id', $branch->id)
                    ->where('member_id', $member->id)
                    ->where('share_dep', '>', 0)
                    ->sum('share_dep');
                if ($shareAmount > 0) $hasRemit = true;
                $row[] = $shareAmount > 0 ? $shareAmount : '';
            }
            // Savings
            foreach ($savingsProducts as $product) {
                $amount = Savings::where('member_id', $member->id)
                    ->where('product_code', $product->product_code)
                    ->where('remittance_amount', '>', 0)
                    ->sum('remittance_amount');
                if ($amount > 0) $hasRemit = true;
                $row[] = $amount > 0 ? $amount : '';
            }
            if ($hasRemit) {
                $rows[] = $row;
            }
        }

        // --- Totals row ---
        $totals = ['TOTAL'];
        // Loans
        foreach ($loanProducts as $product) {
            $amount = $loanRemits->filter(function($remit) use ($product) {
                $forecast = $remit->loanForecast;
                if ($forecast && $forecast->loan_acct_no) {
                    $segments = explode('-', $forecast->loan_acct_no);
                    return ($segments[2] ?? null) == $product->product_code;
                }
                return false;
            })->sum('remitted_amount');
            $totals[] = $amount > 0 ? $amount : '';
        }
        // Shares
        if ($hasShare) {
            $totals[] = $shareRemits->sum('share_dep');
        }
        // Savings
        foreach ($savingsProducts as $product) {
            $amount = $savingsRemits->where('product_code', $product->product_code)->sum('remittance_amount');
            $totals[] = $amount > 0 ? $amount : '';
        }
        $rows[] = $totals;
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [];
        $styles[5] = ['font' => ['bold' => true]];
        $styles[1] = ['font' => ['bold' => true, 'size' => 14]];
        $styles[2] = ['font' => ['bold' => true]];
        $styles[3] = ['font' => ['bold' => true]];
        return $styles;
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 25];
        $col = 'B';
        $count = $this->loanProducts->count() + $this->shareProducts->count() + $this->savingProducts->count();
        for ($i = 0; $i < $count; $i++) {
            $widths[$col] = 18;
            $col++;
        }
        return $widths;
    }

    public function title(): string
    {
        return 'Remittance Report Per Branch Member';
    }
}
