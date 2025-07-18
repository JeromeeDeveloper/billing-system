<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\LoanProduct;
use App\Models\SavingProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BranchLoansAndSavingsWithProductExport implements FromCollection, WithHeadings
{
    protected $remittanceData;
    protected $branch_id;

    public function __construct($remittanceData, $branch_id)
    {
        $this->remittanceData = $remittanceData;
        $this->branch_id = $branch_id;
    }

    public function headings(): array
    {
        return [
            'branch_code',
            'product_code/dr',
            'gl/sl cct no',
            'amt',
            'product_code/cr',
            'gl/sl acct no',
            'amount',
            'product_name'
        ];
    }

    public function collection()
    {
        $exportRows = new Collection();
        $remittanceByMember = collect($this->remittanceData)->groupBy('member_id');

        foreach ($remittanceByMember as $memberId => $records) {
            if (empty($memberId)) {
                continue;
            }
            $member = Member::with(['branch', 'loanForecasts', 'savings.savingProduct'])
                ->where('branch_id', $this->branch_id)
                ->find($memberId);
            if (!$member) {
                continue;
            }

            // Loans
            foreach ($records as $record) {
                if ($record->loans > 0) {
                    foreach ($member->loanForecasts as $forecast) {
                        if ($forecast->total_due_after_remittance > 0) {
                            $originalAccountNumber = $forecast->loan_acct_no;
                            $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);
                            $productCode = explode('-', $forecast->loan_acct_no)[2] ?? null;
                            $productName = '';
                            if ($productCode) {
                                $loanProduct = LoanProduct::where('product_code', $productCode)->first();
                                $productName = $loanProduct ? $loanProduct->product : '';
                            }
                            $exportRows->push([
                                'branch_code' => $member->branch->code ?? '',
                                'product_code/dr' => '',
                                'gl/sl cct no' => '',
                                'amt' => '',
                                'product_code/cr' => '4',
                                'gl/sl acct no' => $formattedAccountNumber,
                                'amount' => number_format($forecast->total_due_after_remittance, 2, '.', ''),
                                'product_name' => $productName,
                            ]);
                        }
                    }
                }
            }

            // Savings
            foreach ($records as $record) {
                if (!empty($record->savings)) {
                    $savingsDistribution = [];
                    if (is_array($record->savings) && isset($record->savings['distribution'])) {
                        $savingsDistribution = $record->savings['distribution'];
                    }
                    foreach ($savingsDistribution as $distribution) {
                        $productName = $distribution['product'] ?? '';
                        $amount = floatval($distribution['amount']);
                        $deductionAmount = floatval($distribution['deduction_amount'] ?? 0);
                        $productType = $distribution['product_type'] ?? null;
                        $savingAccount = $member->savings->first(function ($s) use ($productType) {
                            return $s->savingProduct && $s->savingProduct->product_type === $productType;
                        });
                        if (!$savingAccount && $productName) {
                            $savingAccount = $member->savings->first(function ($s) use ($productName) {
                                return $s->savingProduct && $s->savingProduct->name === $productName;
                            });
                        }
                        if (!$savingAccount && $productName) {
                            $savingAccount = $member->savings->first(function ($s) use ($productName) {
                                return $s->product_name === $productName;
                            });
                        }
                        if ($savingAccount && $savingAccount->savingProduct && $savingAccount->savingProduct->product_type === 'mortuary') {
                            continue;
                        }
                        if ($savingAccount && $savingAccount->savingProduct && $savingAccount->savingProduct->product_type !== 'regular') {
                            if ($savingAccount && $deductionAmount > 0) {
                                $originalAccountNumber = $savingAccount->account_number;
                                $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);
                                $savingProductName = $this->getSavingProductName($savingAccount, $productName);
                                $exportRows->push([
                                    'branch_code' => $member->branch->code ?? '',
                                    'product_code/dr' => '',
                                    'gl/sl cct no' => '',
                                    'amt' => '',
                                    'product_code/cr' => '1',
                                    'gl/sl acct no' => $formattedAccountNumber,
                                    'amount' => number_format($deductionAmount, 2, '.', ''),
                                    'product_name' => $savingProductName,
                                ]);
                            }
                        }
                    }
                }
            }

            // Regular savings
            $totalRegularRemaining = 0;
            foreach ($records as $record) {
                if (!empty($record->savings)) {
                    $savingsDistribution = [];
                    if (is_array($record->savings) && isset($record->savings['distribution'])) {
                        $savingsDistribution = $record->savings['distribution'];
                    }
                    foreach ($savingsDistribution as $distribution) {
                        $savingAccount = $member->savings->first(function ($s) use ($distribution) {
                            return $s->savingProduct && $s->savingProduct->product_type === ($distribution['product_type'] ?? null);
                        });
                        if (
                            $savingAccount && $savingAccount->savingProduct && $savingAccount->savingProduct->product_type === 'regular' &&
                            (isset($distribution['is_remaining']) && $distribution['is_remaining'])
                        ) {
                            $totalRegularRemaining += floatval($distribution['amount']);
                        }
                    }
                }
            }
            if ($totalRegularRemaining > 0) {
                $regularSavings = $member->savings->first(function ($s) {
                    return $s->savingProduct && $s->savingProduct->product_type === 'regular';
                });
                if ($regularSavings) {
                    $originalAccountNumber = $regularSavings->account_number;
                    $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);
                    $savingProductName = $this->getSavingProductName($regularSavings, '');
                    $exportRows->push([
                        'branch_code' => $member->branch->code ?? '',
                        'product_code/dr' => '',
                        'gl/sl cct no' => '',
                        'amt' => '',
                        'product_code/cr' => '1',
                        'gl/sl acct no' => $formattedAccountNumber,
                        'amount' => number_format($totalRegularRemaining, 2, '.', ''),
                        'product_name' => $savingProductName,
                    ]);
                }
            }
        }
        return $exportRows;
    }

    private function getSavingProductName($savingAccount, $fallbackName = '')
    {
        if ($savingAccount->savingProduct && $savingAccount->savingProduct->name) {
            return $savingAccount->savingProduct->name;
        }
        if ($savingAccount->product_name) {
            return $savingAccount->product_name;
        }
        if ($savingAccount->product_code) {
            $savingProduct = SavingProduct::where('product_code', $savingAccount->product_code)->first();
            if ($savingProduct && $savingProduct->name) {
                return $savingProduct->name;
            }
        }
        if ($fallbackName) {
            return $fallbackName;
        }
        if ($savingAccount->product_code) {
            return "Savings Product {$savingAccount->product_code}";
        }
        return 'Unknown Product';
    }
}
