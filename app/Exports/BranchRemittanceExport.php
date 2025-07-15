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

class BranchRemittanceExport implements FromCollection, WithHeadings
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
                ->where('branch_id', $this->branch_id) // Only get members from this branch
                ->find($record['member_id']);

            if (!$member) {
                Log::warning('Member not found or not in branch for record: ' . json_encode($record));
                continue;
            }

            // Verify member belongs to the correct branch
            if ($member->branch_id !== $this->branch_id) {
                Log::warning('Member ' . $member->id . ' does not belong to branch ' . $this->branch_id);
                continue;
            }

            // Handle loan payments
            if ($record['loans'] > 0) {
                $remainingPayment = $record['loans'];

                // Get all loan forecasts and sort them by product prioritization
                $forecasts = collect($member->loanForecasts)->map(function($forecast) use ($member) {
                    // Extract product code from loan_acct_no (e.g., 40102 from 0304-001-40102-000023-3)
                    $productCode = explode('-', $forecast->loan_acct_no)[2] ?? null;

                    // Find the loan product member with matching product code
                    $loanProductMember = $member->loanProductMembers()
                        ->whereHas('loanProduct', function($query) use ($productCode) {
                            $query->where('product_code', $productCode);
                        })
                        ->first();

                    return [
                        'forecast' => $forecast,
                        'prioritization' => $loanProductMember ? $loanProductMember->prioritization : 999,
                        'product_code' => $productCode,
                        'total_due' => $forecast->total_due,
                        'principal' => $forecast->principal ?? 0
                    ];
                })->sortBy([
                    ['prioritization', 'asc'],
                    ['principal', 'desc']
                ]); // Sort by prioritization first, then by principal amount (descending) for same priority

                // Log the sorted forecasts for debugging
                Log::info('Sorted forecasts for member ' . $member->id . ':');
                foreach ($forecasts as $f) {
                    Log::info("Loan Account: {$f['forecast']->loan_acct_no}, Priority: {$f['prioritization']}, Principal: {$f['principal']}, Total Due: {$f['total_due']}");
                }

                foreach ($forecasts as $forecastData) {
                    if ($remainingPayment <= 0) break;

                    $forecast = $forecastData['forecast'];
                    $totalDue = $forecastData['total_due'];
                    $productCode = $forecastData['product_code'];

                    // Calculate how much to pay for this loan
                    $deductionAmount = min($remainingPayment, $totalDue);

                    if ($productCode && $deductionAmount > 0) {
                        Log::info("Processing payment for member {$member->id}:");
                        Log::info("- Loan Account: {$forecast->loan_acct_no}");
                        Log::info("- Total Due: {$totalDue}");
                        Log::info("- Payment Amount: {$deductionAmount}");
                        Log::info("- Remaining Payment Before: {$remainingPayment}");

                        // Format account number with single quote prefix
                        $originalAccountNumber = $forecast->loan_acct_no;
                        $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);

                        // Add loan deduction row with the actual deduction amount
                        $exportRows->push([
                            'branch_code' => $member->branch->code ?? '',
                            'product_code' => '4',
                            'dr' => '',
                            'gl/sl cct no' => '',
                            'amt' => '',
                            'account_number' => $formattedAccountNumber,
                            'amount' => number_format($deductionAmount, 2, '.', '') // Use actual deduction amount
                        ]);

                        // Update the total_due in LoanForecast
                        $newTotalDue = $totalDue - $deductionAmount;
                        $forecast->update([
                            'total_due' => max(0, $newTotalDue) // Ensure total_due doesn't go below 0
                        ]);
                        Log::info("- Updated Total Due: {$newTotalDue}");

                        // Subtract the deduction amount from remaining payment
                        $remainingPayment -= $deductionAmount;
                        Log::info("- Remaining Payment After: {$remainingPayment}");

                        // If this loan is fully paid, break the loop
                        if ($newTotalDue <= 0) {
                            Log::info("- Loan fully paid, moving to next loan");
                            continue;
                        }
                    }
                }

                // If there's still remaining payment, log it as unused
                if ($remainingPayment > 0) {
                    Log::warning("Member {$member->id} has unused loan payment: {$remainingPayment}");
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

                    // Format account number with single quote prefix
                    $originalAccountNumber = $savings->getRawOriginal('account_number');
                    $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);

                    // Add savings row
                    $exportRows->push([
                        'branch_code' => $member->branch->code ?? '',
                        'product_code' => '1',
                        'dr' => '',
                        'gl/sl cct no' => '',
                        'amt' => '',
                        'account_number' => $formattedAccountNumber,
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

                        // Format account number with single quote prefix
                        $originalAccountNumber = $share->getRawOriginal('account_number');
                        $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);

                        // Add share row
                        $exportRows->push([
                            'branch_code' => $member->branch->code ?? '',
                            'product_code' => '2', // Assuming 2 is the product code for shares
                            'dr' => '',
                            'gl/sl cct no' => '',
                            'amt' => '',
                            'account_number' => $formattedAccountNumber,
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
