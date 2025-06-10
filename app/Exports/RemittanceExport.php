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

        try {
            foreach ($this->remittanceData as $record) {
                Log::info('Processing record for member_id: ' . $record['member_id']);

                $member = Member::with([
                    'branch',
                    'loanProductMembers.loanProduct',
                    'loanForecasts' => function($q) {
                        $q->orderBy('total_due', 'desc'); // Prioritize by total due amount
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

                    // Get active loans ordered by forecast amount
                    foreach ($member->loanForecasts as $forecast) {
                        if ($remainingAmount <= 0) break;

                        $deductionAmount = min($remainingAmount, $forecast->total_due);

                        // Get loan product code
                        $loanProduct = $member->loanProductMembers()
                            ->whereHas('loanProduct')
                            ->first();

                        if ($loanProduct) {
                            Log::info('Adding loan row for member: ' . $member->id . ', amount: ' . $deductionAmount);

                            // Add loan deduction row
                            $exportRows->push([
                                'branch_code' => $member->branch->code ?? '',
                                'product_code' => $loanProduct->loanProduct->product_code ?? '',
                                'dr' => '',
                                'gl/sl cct no' => '',
                                'amt' => '',
                                'account_number' => $forecast->loan_acct_no,
                                'amount' => number_format($deductionAmount, 2, '.', '')
                            ]);
                        } else {
                            Log::warning('No loan product found for member: ' . $member->id);
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
                                'product_code' => $savings->product_code,
                                'dr' => '',
                                'gl/sl cct no' => '',
                                'amt' => '',
                                'account_number' => $savings->getRawOriginal('account_number'), // Get raw value from database
                                'amount' => number_format($amount, 2, '.', '')
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

        return $exportRows;
    }
}
