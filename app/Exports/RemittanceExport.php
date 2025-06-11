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
                        'total_due' => $forecast->total_due
                    ];
                })->sortBy('prioritization'); // Sort by prioritization (1 being highest priority)

                // Log the sorted forecasts for debugging
                Log::info('Sorted forecasts for member ' . $member->id . ':');
                foreach ($forecasts as $f) {
                    Log::info("Loan Account: {$f['forecast']->loan_acct_no}, Priority: {$f['prioritization']}, Total Due: {$f['total_due']}");
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

                        // Add loan deduction row
                        $exportRows->push([
                            'branch_code' => $member->branch->code ?? '',
                            'product_code' => '4',
                            'dr' => '',
                            'gl/sl cct no' => '',
                            'amt' => '',
                            'account_number' => str_replace('-', '', $forecast->loan_acct_no),
                            'amount' => number_format($deductionAmount, 2, '.', '')
                        ]);

                        // Subtract the deduction amount from remaining payment
                        $remainingPayment -= $deductionAmount;
                        Log::info("- Remaining Payment After: {$remainingPayment}");
                    }
                }

                // If there's still remaining payment, log it as unused
                if ($remainingPayment > 0) {
                    Log::warning("Member {$member->id} has unused loan payment: {$remainingPayment}");
                }
            }

            // Handle savings
            if (!empty($record['savings'])) {
                foreach ($record['savings'] as $productName => $amount) {
                    if ($amount > 0) {
                        Log::info('Processing savings for member: ' . $member->id . ', product: ' . $productName . ', amount: ' . $amount);

                        // Find savings account
                        $savings = $member->savings()
                            ->where('product_name', $productName)
                            ->first();

                        if (!$savings) {
                            Log::warning('Savings account not found for member: ' . $member->id . ', product: ' . $productName);
                            continue;
                        }

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
                            'amount' => number_format($savings->remittance_amount, 2, '.', '') // Use remittance_amount from savings
                        ]);
                    }
                }
            }
        }

        return $exportRows;
    }
}