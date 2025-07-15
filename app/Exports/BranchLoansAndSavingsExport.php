<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\ContraAcc;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Log;
use App\Models\Savings;

class BranchLoansAndSavingsExport implements FromCollection, WithHeadings
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
            'amount'
        ];
    }

    public function collection()
    {
        $exportRows = new Collection();
        $branchTotals = [
            'loans' => 0,
            'savings' => 0
        ];

        foreach ($this->remittanceData as $record) {
            if (empty($record->member_id)) {
                continue;
            }

            $member = Member::with(['branch', 'loanForecasts', 'savings.savingProduct'])
                ->where('branch_id', $this->branch_id) // Only get members from this branch
                ->find($record->member_id);

            if (!$member) {
                Log::warning('Member not found or not in branch for record: ' . json_encode($record));
                continue;
            }

            // Verify member belongs to the correct branch
            if ($member->branch_id !== $this->branch_id) {
                Log::warning('Member ' . $member->id . ' does not belong to branch ' . $this->branch_id);
                continue;
            }

            Log::info("Processing member {$member->id} ({$member->fname} {$member->lname}) with " . $member->savings->count() . " savings accounts");
            foreach ($member->savings as $savings) {
                Log::info("Member savings account: {$savings->account_number}, product: " . ($savings->savingProduct ? $savings->savingProduct->product_name : 'N/A'));
            }

            // Handle loan payments
            if ($record->loans > 0) {
                foreach ($member->loanForecasts as $forecast) {
                    if ($forecast->total_due_after_remittance > 0) {
                        $originalAccountNumber = $forecast->loan_acct_no;
                        $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);

                        Log::info("Loan account number transformation: '{$originalAccountNumber}' -> '{$formattedAccountNumber}'");

                        $exportRows->push([
                            'branch_code' => $member->branch->code ?? '',
                            'product_code/dr' => '',
                            'gl/sl cct no' => '',
                            'amt' => '',
                            'product_code/cr' => '4', // Assuming 4 is for Loans
                            'gl/sl acct no' => $formattedAccountNumber,
                            'amount' => number_format($forecast->total_due_after_remittance, 2, '.', '')
                        ]);
                        $branchTotals['loans'] += $forecast->total_due_after_remittance;
                    }
                }
            }

            // Handle savings
            if (!empty($record->savings)) {
                // Handle new savings structure with total and distribution
                $savingsDistribution = [];
                if (is_array($record->savings) && isset($record->savings['distribution'])) {
                    $savingsDistribution = $record->savings['distribution'];
                }

                // Output deduction rows for all savings except regular and mortuary
                foreach ($savingsDistribution as $distribution) {
                    $productName = $distribution['product'];
                    $amount = floatval($distribution['amount']);
                    $deductionAmount = floatval($distribution['deduction_amount'] ?? 0);

                    // Exclude mortuary products
                    $savingAccount = $member->savings->first(function ($s) use ($distribution) {
                        return $s->savingProduct && $s->savingProduct->product_type === ($distribution['product_type'] ?? null);
                    });
                    if ($savingAccount && $savingAccount->savingProduct && $savingAccount->savingProduct->product_type === 'mortuary') {
                        continue;
                    }

                    // Output deduction row for each product (except regular)
                    if ($savingAccount && $savingAccount->savingProduct && $savingAccount->savingProduct->product_type !== 'regular') {
                        if ($savingAccount && $deductionAmount > 0) {
                            $originalAccountNumber = $savingAccount->account_number;
                            $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);

                            $exportRows->push([
                                'branch_code' => $member->branch->code ?? '',
                                'product_code/dr' => '',
                                'gl/sl cct no' => '',
                                'amt' => '',
                                'product_code/cr' => '1',
                                'gl/sl acct no' => $formattedAccountNumber,
                                'amount' => number_format($deductionAmount, 2, '.', '')
                            ]);
                            $branchTotals['savings'] += $deductionAmount;
                        }
                    }
                }

                // Sum all remaining for regular savings (Savings Deposit-Regular, is_remaining=true)
                $totalRegularRemaining = 0;
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

                if ($totalRegularRemaining > 0) {
                    // Find the member's regular savings account
                    $regularSavings = $member->savings->first(function ($s) {
                        return $s->savingProduct && $s->savingProduct->product_type === 'regular';
                    });
                    if ($regularSavings) {
                        $originalAccountNumber = $regularSavings->account_number;
                        $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);

                        $exportRows->push([
                            'branch_code' => $member->branch->code ?? '',
                            'product_code/dr' => '',
                            'gl/sl cct no' => '',
                            'amt' => '',
                            'product_code/cr' => '1',
                            'gl/sl acct no' => $formattedAccountNumber,
                            'amount' => number_format($totalRegularRemaining, 2, '.', '')
                        ]);
                        $branchTotals['savings'] += $totalRegularRemaining;
                    }
                }
            }
        }

        // Get branch info for summary rows
        $branch = Member::where('branch_id', $this->branch_id)->with('branch')->first();
        $branchCode = $branch->branch->code ?? '';

        // Add branch totals summary rows
        if ($branchTotals['loans'] > 0) {
            // Get contra account for loans
            $loansContra = ContraAcc::where('type', 'loans')->first();
            if ($loansContra) {
                $exportRows->push([
                    'branch_code' => $branchCode,
                    'product_code/dr' => '4',
                    'gl/sl cct no' => $loansContra->account_number,
                    'amt' => number_format($branchTotals['loans'], 2, '.', ''),
                    'product_code/cr' => '',
                    'gl/sl acct no' => '',
                    'amount' => ''
                ]);
            }
        }

        if ($branchTotals['savings'] > 0) {
            // Get contra account for savings
            $savingsContra = ContraAcc::where('type', 'savings')->first();
            if ($savingsContra) {
                $exportRows->push([
                    'branch_code' => $branchCode,
                    'product_code/dr' => '1',
                    'gl/sl cct no' => $savingsContra->account_number,
                    'amt' => number_format($branchTotals['savings'], 2, '.', ''),
                    'product_code/cr' => '',
                    'gl/sl acct no' => '',
                    'amount' => ''
                ]);
            }
        }

        return $exportRows;
    }
}
