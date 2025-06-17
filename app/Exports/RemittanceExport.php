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
            'product_code',
            'dr',
            'gl/sl cct no',
            'amt',
            'account_number',
            'amount'
        ];
    }

    public function collection()
    {
        $exportRows = new Collection();

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
                    // Calculate the actual amount paid (difference between total_due and total_due_after_remittance)
                    $amountPaid = $forecast->total_due - $forecast->total_due_after_remittance;

                    if ($amountPaid > 0) {
                        // Add loan deduction row with the actual amount paid
                        $exportRows->push([
                            'branch_code' => $member->branch->code ?? '',
                            'product_code' => '4',
                            'dr' => '',
                            'gl/sl cct no' => '',
                            'amt' => '',
                            'account_number' => str_replace('-', '', $forecast->loan_acct_no),
                            'amount' => number_format($amountPaid, 2, '.', '')
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
                        'product_code' => '1',
                        'dr' => '',
                        'gl/sl cct no' => '',
                        'amt' => '',
                        'account_number' => str_replace('-', '', $savings->getRawOriginal('account_number')),
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
                            'product_code' => '2', // Assuming 2 is the product code for shares
                            'dr' => '',
                            'gl/sl cct no' => '',
                            'amt' => '',
                            'account_number' => str_replace('-', '', $share->getRawOriginal('account_number')),
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
