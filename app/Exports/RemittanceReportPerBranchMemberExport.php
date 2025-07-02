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

class RemittanceReportPerBranchMemberExport implements FromArray, WithStyles, WithColumnWidths, WithTitle
{
    protected $loanProducts;
    protected $savingProducts;
    protected $shareProducts;
    protected $billingPeriod;

    public function __construct($billingPeriod = null)
    {
        $this->loanProducts = LoanProduct::all();
        $this->savingProducts = SavingProduct::all();
        $this->shareProducts = ShareProduct::all();
        $this->billingPeriod = $billingPeriod ?? now()->format('Y-m');
    }

    public function array(): array
    {
        $rows = [];
        $branches = Branch::all();
        foreach ($branches as $branch) {
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
            // Loans
            $remitted = RemittancePreview::where('billing_period', $this->billingPeriod)
                ->where('status', 'success')
                ->whereHas('member', function($q) use ($branch) {
                    $q->where('branch_id', $branch->id);
                })
                ->get();
            foreach ($this->loanProducts as $product) {
                $total = $remitted->sum('loans');
                $totals[] = $total;
            }
            // Shares
            foreach ($this->shareProducts as $product) {
                $total = $remitted->sum('share_amount');
                $totals[] = $total;
            }
            // Savings
            foreach ($this->savingProducts as $product) {
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
                $totals[] = $total;
            }
            $rows[] = $totals;
            $rows[] = [''];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Bold for header rows (dynamic header and totals for each branch)
        $styles = [];
        $row = 5;
        $branches = Branch::count();
        for ($i = 0; $i < $branches; $i++) {
            $styles[$row] = ['font' => ['bold' => true]];
            $row++; // header
            $row += Branch::with('members')->get()[$i]->members->count(); // skip member rows
            $styles[$row] = ['font' => ['bold' => true]];
            $row += 2; // total row + blank row
            $row += 4; // static header rows
        }
        // Bold for A1, A2, A3
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
