<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\LoanProduct;
use App\Models\SavingProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LoansAndSavingsWithProductExport implements FromCollection, WithHeadings
{
    protected $remittanceData;

    public function __construct($remittanceData)
    {
        $this->remittanceData = $remittanceData;
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
            $member = Member::with(['branch', 'loanForecasts', 'savings.savingProduct'])->find($memberId);
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

                            // Extract product code from loan_acct_no (e.g., 40102 from 0304-001-40102-000002-7)
                            $productCode = explode('-', $forecast->loan_acct_no)[2] ?? null;

                            // Get product name from LoanProduct
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

                        // Find saving account by product type first
                        $savingAccount = $member->savings->first(function ($s) use ($productType) {
                            return $s->savingProduct && $s->savingProduct->product_type === $productType;
                        });

                        // If not found by product type, try to find by product name
                        if (!$savingAccount && $productName) {
                            $savingAccount = $member->savings->first(function ($s) use ($productName) {
                                return $s->savingProduct && $s->savingProduct->name === $productName;
                            });
                        }

                        // If still not found, try to find by product code from account number
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

                                // Get product name with multiple fallback strategies
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

                    // Get product name with multiple fallback strategies
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

    /**
     * Get saving product name with multiple fallback strategies
     */
    private function getSavingProductName($savingAccount, $fallbackName = '')
    {
        // Strategy 1: Get from savingProduct relationship
        if ($savingAccount->savingProduct && $savingAccount->savingProduct->name) {
            return $savingAccount->savingProduct->name;
        }

        // Strategy 2: Get from product_name field in savings table
        if ($savingAccount->product_name) {
            return $savingAccount->product_name;
        }

        // Strategy 3: Query SavingProduct table directly using product_code
        if ($savingAccount->product_code) {
            $savingProduct = SavingProduct::where('product_code', $savingAccount->product_code)->first();
            if ($savingProduct && $savingProduct->name) {
                return $savingProduct->name;
            }
        }

        // Strategy 4: Use fallback name from distribution
        if ($fallbackName) {
            return $fallbackName;
        }

        // Strategy 5: Generate a name from product code
        if ($savingAccount->product_code) {
            return "Savings Product {$savingAccount->product_code}";
        }

        return 'Unknown Product';
    }
}
