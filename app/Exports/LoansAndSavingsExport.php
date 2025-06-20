<?php

namespace App\Exports;

use App\Models\Member;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Log;

class LoansAndSavingsExport implements FromCollection, WithHeadings
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

        foreach ($this->remittanceData as $record) {
            if (empty($record['member_id'])) {
                continue;
            }

            $member = Member::with(['branch', 'loanForecasts', 'savings.savingProduct'])->find($record['member_id']);

            if (!$member) {
                Log::warning('Member not found for record: ' . json_encode($record));
                continue;
            }

            // Handle loan payments
            if ($record['loans'] > 0) {
                foreach ($member->loanForecasts as $forecast) {
                    if ($forecast->total_due_after_remittance > 0) {
                        $exportRows->push([
                            'branch_code' => $member->branch->code ?? '',
                            'product_code/dr' => '',
                            'gl/sl cct no' => '',
                            'amt' => '',
                            'product_code/cr' => '4', // Assuming 4 is for Loans
                            'gl/sl acct no' => str_replace('-', '', $forecast->loan_acct_no),
                            'amount' => number_format($forecast->total_due_after_remittance, 2, '.', '')
                        ]);
                    }
                }
            }

            // Handle savings
            if (!empty($record['savings'])) {
                $totalRemittedSavings = collect($record['savings'])->sum();
                $totalDeducted = 0;

                // Find Regular Savings account details for later use
                $regularSaving = $member->savings->first(function ($s) {
                    return str_contains(strtolower($s->savingProduct->product_name), 'regular');
                });
                $regularSavingsAccountNumber = $regularSaving ? $regularSaving->account_number : '';
                $regularSavingsProductCode = $regularSaving && $regularSaving->savingProduct ? $regularSaving->savingProduct->product_code : '';


                // Process deductions for ALL savings products, including Regular Savings
                foreach ($member->savings as $saving) {
                    if (isset($record['savings'][$saving->savingProduct->product_name])) {
                        $deductionAmount = $saving->deduction_amount ?? 0;
                        if ($deductionAmount > 0) {
                             $exportRows->push([
                                'branch_code' => $member->branch->code ?? '',
                                'product_code/dr' => '',
                                'gl/sl cct no' => '',
                                'amt' => '',
                                'product_code/cr' => '1',
                                'gl/sl acct no' => str_replace('-', '', $saving->account_number),
                                'amount' => number_format($deductionAmount, 2, '.', '')
                            ]);
                            $totalDeducted += $deductionAmount;
                        }
                    }
                }

                // Second pass: add remainder to regular savings
                $remainingForRegular = $totalRemittedSavings - $totalDeducted;
                if ($remainingForRegular > 0 && !empty($regularSavingsAccountNumber)) {
                    $exportRows->push([
                        'branch_code' => $member->branch->code ?? '',
                        'product_code/dr' => '',
                        'gl/sl cct no' => '',
                        'amt' => '',
                        'product_code/cr' => '1',
                        'gl/sl acct no' => str_replace('-', '', $regularSavingsAccountNumber),
                        'amount' => number_format($remainingForRegular, 2, '.', '')
                    ]);
                }
            }
        }

        return $exportRows;
    }
}
