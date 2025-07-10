<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\LoanProduct;
use App\Models\SavingProduct;
use App\Models\ShareProduct;
use App\Models\RemittancePreview;
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
        $rows[] = ['Remittance Date', now()->format('F d, Y')];
        $rows[] = ['For Billing Period', $this->billingPeriod];
        $rows[] = [''];

        $header = ['Member Name'];
        foreach ($this->loanProducts as $product) {
            $header[] = $product->product;
        }
        foreach ($this->shareProducts as $product) {
            $header[] = $product->product_name;
        }
        foreach ($this->savingProducts as $product) {
            $header[] = $product->product_name;
        }
        $rows[] = $header;

        $members = $branch->members;
        foreach ($members as $member) {
            $remitted = RemittancePreview::where('member_id', $member->id)
                ->where('billing_period', $this->billingPeriod)
                ->where('status', 'success')
                ->first();
            $row = [$member->fname . ' ' . $member->lname];
            // Loans: Only display remitted loan amount in the first loan product column
            $loanDisplayed = false;
            foreach ($this->loanProducts as $product) {
                if (!$loanDisplayed && $remitted && $remitted->loans > 0) {
                    $row[] = $remitted->loans;
                    $loanDisplayed = true;
                } else {
                    $row[] = '';
                }
            }
            // Shares
            foreach ($this->shareProducts as $product) {
                $amount = $remitted ? $remitted->share_amount : 0;
                $row[] = $amount;
            }
            // Savings
            foreach ($this->savingProducts as $product) {
                $amount = 0;
                if ($remitted && is_array($remitted->savings) && isset($remitted->savings['distribution'])) {
                    foreach ($remitted->savings['distribution'] as $dist) {
                        if (($dist['product_code'] ?? null) == $product->product_code) {
                            $amount = $dist['amount'] ?? 0;
                            break;
                        }
                    }
                }
                $row[] = $amount;
            }
            $rows[] = $row;
        }

        // Totals row
        $totals = ['TOTAL'];
        $remitted = RemittancePreview::where('billing_period', $this->billingPeriod)
            ->where('status', 'success')
            ->whereHas('member', function($q) use ($branch) {
                $q->where('branch_id', $branch->id);
            })
            ->get();
        // Loans: Only show total in the first loan product column
        $loanTotalDisplayed = false;
        foreach ($this->loanProducts as $product) {
            if (!$loanTotalDisplayed) {
                $total = $remitted->sum('loans');
                $totals[] = $total > 0 ? $total : '';
                $loanTotalDisplayed = true;
            } else {
                $totals[] = '';
            }
        }
        // Shares: Only show total in the first share product column
        $shareTotalDisplayed = false;
        foreach ($this->shareProducts as $product) {
            if (!$shareTotalDisplayed) {
                $total = $remitted->sum('share_amount');
                $totals[] = $total > 0 ? $total : '';
                $shareTotalDisplayed = true;
            } else {
                $totals[] = '';
            }
        }
        // Savings: Only show total in the first savings product column
        $savingsTotalDisplayed = false;
        foreach ($this->savingProducts as $product) {
            if (!$savingsTotalDisplayed) {
                $total = $remitted->sum(function($item) use ($product) {
                    if (is_array($item->savings) && isset($item->savings['distribution'])) {
                        foreach ($item->savings['distribution'] as $dist) {
                            if (($dist['product_code'] ?? null) == $product->product_code) {
                                return $dist['amount'] ?? 0;
                            }
                        }
                    }
                    return 0;
                });
                $totals[] = $total > 0 ? $total : '';
                $savingsTotalDisplayed = true;
            } else {
                $totals[] = '';
            }
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
