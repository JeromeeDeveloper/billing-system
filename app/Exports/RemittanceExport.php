<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\LoanForecast;
use App\Models\Savings;
use App\Models\SavingProduct;
use App\Models\Remittance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RemittanceExport implements FromCollection, WithHeadings
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
            'amount'
        ];
    }

    public function collection()
    {
        $exportRows = new Collection();

        // First, get ALL savings with deduction_amount > 0 from the Savings model
        $allSavingsWithDeductions = Savings::with(['member.branch'])
            ->where('deduction_amount', '>', 0)
            ->get();

        Log::info('Found ' . $allSavingsWithDeductions->count() . ' savings accounts with deduction amounts');

        foreach ($allSavingsWithDeductions as $savings) {
            $member = $savings->member;
            if (!$member) {
                Log::warning('No member found for savings account: ' . $savings->account_number);
                continue;
            }

            Log::info('Processing savings deduction for member: ' . $member->id . ', account: ' . $savings->account_number);
            Log::info('Deduction amount: ' . $savings->deduction_amount);

            // Add savings deduction row
            $exportRows->push([
                'branch_code' => $member->branch->code ?? '',
                'product_code/dr' => '',
                'gl/sl cct no' => '',
                'amt' => '',
                'product_code/cr' => '1',
                'gl/sl acct no' => str_replace('-', '', $savings->account_number),
                'amount' => number_format($savings->deduction_amount, 2, '.', '')
            ]);
        }

        // Then process the existing remittance data
        foreach ($this->remittanceData as $record) {
            $member = Member::with(['branch', 'loanForecasts', 'loanProductMembers.loanProduct', 'savings'])
                ->find($record['member_id']);

            if (!$member) {
                Log::warning('Member not found for record: ' . json_encode($record));
                continue;
            }

            // Handle loan payments
            if ($record['loans'] > 0) {
                // Get all loan forecasts
                $forecasts = $member->loanForecasts;

                foreach ($forecasts as $forecast) {
                    if ($forecast->total_due_after_remittance > 0) {
                        // Add loan deduction row
                        $exportRows->push([
                            'branch_code' => $member->branch->code ?? '',
                            'product_code/dr' => '',
                            'gl/sl cct no' => '',
                            'amt' => '',
                            'product_code/cr' => '4',
                            'gl/sl acct no' => str_replace('-', '', $forecast->loan_acct_no),
                            'amount' => number_format($forecast->total_due_after_remittance, 2, '.', '')
                        ]);
                    }
                }
            }

            // Handle savings
            if ($member) {
                // Get all savings accounts with remittance_amount
                $savingsAccounts = $member->savings()
                    ->where('remittance_amount', '>', 0)
                    ->get();

                foreach ($savingsAccounts as $savings) {
                    Log::info('Processing savings for member: ' . $member->id . ', account: ' . $savings->account_number);

                    // Debug log to see raw account number and amount
                    Log::info('Raw account number from database: [' . $savings->account_number . ']');
                    Log::info('Remittance amount from database: [' . $savings->remittance_amount . ']');

                    // Add savings row
                    $exportRows->push([
                        'branch_code' => $member->branch->code ?? '',
                        'product_code/dr' => '',
                        'gl/sl cct no' => '',
                        'amt' => '',
                        'product_code/cr' => '1',
                        'gl/sl acct no' => str_replace('-', '', $savings->getRawOriginal('account_number')),
                        'amount' => number_format($savings->remittance_amount, 2, '.', '')
                    ]);
                }

                // Handle shares
                $remittance = Remittance::where('member_id', $member->id)
                    ->whereDate('created_at', now()->toDateString())
                    ->first();

                if ($remittance && $remittance->share_dep > 0) {
                    // Get the member's share account
                    $share = $member->shares()->first();

                    if ($share) {
                        Log::info('Processing shares for member: ' . $member->id . ', account: ' . $share->account_number);
                        Log::info('Share amount from database: [' . $remittance->share_dep . ']');

                        // Add share row
                        $exportRows->push([
                            'branch_code' => $member->branch->code ?? '',
                            'product_code/dr' => '',
                            'gl/sl cct no' => '',
                            'amt' => '',
                            'product_code/cr' => '2',
                            'gl/sl acct no' => str_replace('-', '', $share->getRawOriginal('account_number')),
                            'amount' => number_format($remittance->share_dep, 2, '.', '')
                        ]);
                    } else {
                        Log::warning('Member ' . $member->id . ' has share_dep but no share account found');
                    }
                }
            }
        }

        return $exportRows;
    }
}
