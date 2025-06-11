<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\LoanForecast;
use App\Models\Savings;
use App\Models\SavingProduct;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BranchRemittanceExport implements FromCollection, WithHeadings
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

        try {
            foreach ($this->remittanceData as $record) {
                Log::info('Processing record for member_id: ' . $record['member_id']);

                $member = Member::with([
                    'branch',
                    'loanProductMembers.loanProduct',
                    'loanForecasts' => function($q) {
                        $q->get();
                    },
                    'savings'
                ])->find($record['member_id']);

                if (!$member) {
                    Log::warning('Member not found: ' . $record['member_id']);
                    continue;
                }

                // Process loan payments
                if ($record['loans'] > 0) {
                    $remainingAmount = $record['loans'];

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
                            'product_code' => $productCode
                        ];
                    })->sortBy('prioritization') // Sort by prioritization (1 being highest priority)
                    ->pluck('forecast');

                    foreach ($forecasts as $forecast) {
                        if ($remainingAmount <= 0) break;

                        $deductionAmount = min($remainingAmount, $forecast->total_due);

                        // Get loan product code from loan_acct_no
                        $productCode = explode('-', $forecast->loan_acct_no)[2] ?? null;

                        if ($productCode) {
                            Log::info('Adding loan row for member: ' . $member->id . ', amount: ' . $deductionAmount . ', product code: ' . $productCode);

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
                        } else {
                            Log::warning('Could not extract product code from loan account number: ' . $forecast->loan_acct_no);
                        }

                        $remainingAmount -= $deductionAmount;
                    }
                }

                // Process savings for each product
                if (isset($record['savings']) && is_array($record['savings'])) {
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

                            // Debug log to see raw account number
                            Log::info('Raw account number from database: [' . $savings->account_number . ']');

                            // Add savings row
                            $exportRows->push([
                                'branch_code' => $member->branch->code ?? '',
                                'product_code' => '1',
                                'dr' => '',
                                'gl/sl cct no' => '',
                                'amt' => '',
                                'account_number' => str_replace('-', '', $savings->getRawOriginal('account_number')),
                                'amount' => number_format($amount, 2, '.', '') // Use the amount from record directly
                            ]);
                        }
                    }
                } else {
                    Log::warning('No savings data found for member: ' . $member->id);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in RemittanceExport: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }

        // Sort the collection by branch_code and product_code
        return $exportRows->sortBy([
            ['branch_code', 'asc'],
            ['product_code', 'asc']
        ]);
    }
}