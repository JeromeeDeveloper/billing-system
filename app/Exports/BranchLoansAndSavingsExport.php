<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\ContraAcc;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Log;
use App\Models\Savings;
use App\Models\RemittanceBatch;

class BranchLoansAndSavingsExport implements FromCollection, WithHeadings
{
    protected $remittanceData;
    protected $branch_id;
    protected $billingPeriod;

    public function __construct($remittanceData, $branch_id, $billingPeriod = null)
    {
        $this->remittanceData = $remittanceData;
        $this->branch_id = $branch_id;
        $this->billingPeriod = $billingPeriod;
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
            'interest',
            'penalty',
            'principal'
        ];
    }

    public function collection()
    {
        $exportRows = new Collection();
        $branchTotals = [];

        // Group remittance data by member_id
        $remittanceByMember = collect($this->remittanceData)->groupBy('member_id');

        // Find the latest batch_id from LoanRemittance
        $latestBatch = \App\Models\LoanRemittance::orderByDesc('imported_at')->value('batch_id');

        // Find the latest remittance_tag for the current billing period using RemittanceBatch
        $latestTag = null;
        if ($this->billingPeriod) {
            $latestTag = RemittanceBatch::where('billing_period', $this->billingPeriod)->max('remittance_tag');
        }

        foreach ($remittanceByMember as $memberId => $records) {
            if (empty($memberId)) {
                continue;
            }

            $member = Member::with(['branch', 'loanForecasts', 'savings.savingProduct'])
                ->where('branch_id', $this->branch_id)
                ->find($memberId);
            if (!$member) {
                Log::warning('Member not found for record: ' . json_encode($records));
                continue;
            }

            $branchCode = $member->branch->code ?? '';

            // Initialize branch totals if not exists
            if (!isset($branchTotals[$branchCode])) {
                $branchTotals[$branchCode] = [
                    'savings_total' => 0,
                    'loans_total' => 0
                ];
            }

            // Handle loan payments (deduction logic)
            foreach ($member->loanForecasts as $forecast) {
                // Get the latest batch to determine billing type
                $latestBatch = \App\Models\RemittanceBatch::where('billing_period', $this->billingPeriod)
                    ->whereIn('billing_type', ['regular', 'special'])
                    ->orderBy('imported_at', 'desc')
                    ->first();

                // Find the highest remittance_tag for this loan and billing period
                $latestTag = \App\Models\LoanRemittance::where('loan_forecast_id', $forecast->id)
                    ->max('remittance_tag');
                if (!$latestTag) {
                    continue;
                }
                $remittances = \App\Models\LoanRemittance::where('loan_forecast_id', $forecast->id)
                    ->where('member_id', $member->id)
                    ->where('remittance_tag', $latestTag);

                // Filter by billing type if latest batch exists
                if ($latestBatch) {
                    $remittances = $remittances->where('billing_type', $latestBatch->billing_type);
                }

                $remittances = $remittances->get();
                foreach ($remittances as $remit) {
                    if ($remit->remitted_amount > 0) {
                        $originalAccountNumber = $forecast->loan_acct_no;
                        $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);
                        $exportRows->push([
                            'branch_code' => $branchCode,
                            'product_code/dr' => '',
                            'gl/sl cct no' => '',
                            'amt' => '',
                            'product_code/cr' => '4',
                            'gl/sl acct no' => $formattedAccountNumber,
                            'amount' => number_format($remit->remitted_amount, 2, '.', ''),
                            'interest' => number_format($remit->applied_to_interest, 2, '.', ''),
                            'penalty' => '',
                            'principal' => number_format($remit->applied_to_principal, 2, '.', '')
                        ]);
                        $branchTotals[$branchCode]['loans_total'] += $remit->remitted_amount;
                    }
                }
            }

            // Output deduction rows for all savings except regular and mortuary
            foreach ($records as $record) {
                if (!empty($record->savings)) {
                    $savingsDistribution = [];
                    if (is_array($record->savings) && isset($record->savings['distribution'])) {
                        $savingsDistribution = $record->savings['distribution'];
                    }
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
                                    'branch_code' => $branchCode,
                                    'product_code/dr' => '',
                                    'gl/sl cct no' => '',
                                    'amt' => '',
                                    'product_code/cr' => '1',
                                    'gl/sl acct no' => $formattedAccountNumber,
                                    'amount' => number_format($deductionAmount, 2, '.', ''),
                                    'interest' => '',
                                    'principal' => ''
                                ]);
                                $branchTotals[$branchCode]['savings_total'] += $deductionAmount;
                            }
                        }
                    }
                }
            }

            // Sum all remaining for regular savings (Savings Deposit-Regular, is_remaining=true)
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
                // Find the member's regular savings account
                $regularSavings = $member->savings->first(function ($s) {
                    return $s->savingProduct && $s->savingProduct->product_type === 'regular';
                });
                if ($regularSavings) {
                    $originalAccountNumber = $regularSavings->account_number;
                    $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);
                    $exportRows->push([
                        'branch_code' => $branchCode,
                        'product_code/dr' => '',
                        'gl/sl cct no' => '',
                        'amt' => '',
                        'product_code/cr' => '1',
                        'gl/sl acct no' => $formattedAccountNumber,
                        'amount' => number_format($totalRegularRemaining, 2, '.', ''),
                        'interest' => '',
                        'principal' => ''
                    ]);
                    $branchTotals[$branchCode]['savings_total'] += $totalRegularRemaining;
                }
            }
        }

        // Add totals for each branch at the bottom
        foreach ($branchTotals as $branchCode => $totals) {
            // Get contra account for savings
            $savingsContraAcc = ContraAcc::where('type', 'savings')->first();
            $loansContraAcc = ContraAcc::where('type', 'loans')->first();

            // Add savings total row
            if ($totals['savings_total'] > 0) {
                $exportRows->push([
                    'branch_code' => $branchCode,
                    'product_code/dr' => '5',
                    'gl/sl cct no' => $savingsContraAcc ? $savingsContraAcc->account_number : '',
                    'amt' => number_format($totals['savings_total'], 2, '.', ''),
                    'product_code/cr' => '',
                    'gl/sl acct no' => '',
                    'amount' => '',
                    'interest' => '',
                    'principal' => ''
                ]);
            }

            // Add loans total row
            if ($totals['loans_total'] > 0) {
                $exportRows->push([
                    'branch_code' => $branchCode,
                    'product_code/dr' => '5',
                    'gl/sl cct no' => $loansContraAcc ? $loansContraAcc->account_number : '',
                    'amt' => number_format($totals['loans_total'], 2, '.', ''),
                    'product_code/cr' => '',
                    'gl/sl acct no' => '',
                    'amount' => '',
                    'interest' => '',
                    'principal' => ''
                ]);
            }
        }

        return $exportRows;
    }
}
