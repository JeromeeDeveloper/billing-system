<?php

namespace App\Exports;

use App\Models\Member;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Log;
use App\Models\Savings;

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
                // Iterate through each savings type from the imported record
                foreach ($record['savings'] as $productName => $amountFromImport) {
                    if ($amountFromImport <= 0) {
                        continue;
                    }

                    // Exclude savings products named 'Mortuary'
                    if (strtolower($productName) === 'mortuary') {
                        continue;
                    }

                    // Find the specific savings account for this member and product name
                    $savingAccount = $member->savings->first(function ($s) use ($productName) {
                        return $s->savingProduct && strtolower($s->savingProduct->product_name) === strtolower($productName);
                    });

                    if ($savingAccount) {
                        $deductionAmount = $savingAccount->deduction_amount ?? 0;

                        // Add a row for the deduction amount, if it exists
                        if ($deductionAmount > 0) {
                            $exportRows->push([
                                'branch_code' => $member->branch->code ?? '',
                                'product_code/dr' => '',
                                'gl/sl cct no' => '',
                                'amt' => '',
                                'product_code/cr' => '1',
                                'gl/sl acct no' => str_replace('-', '', $savingAccount->account_number),
                                'amount' => number_format($deductionAmount, 2, '.', '')
                            ]);
                        }

                        // Add a row for the remaining amount from the import
                        $remainingAmount = $amountFromImport - $deductionAmount;
                        if ($remainingAmount > 0) {
                            $exportRows->push([
                                'branch_code' => $member->branch->code ?? '',
                                'product_code/dr' => '',
                                'gl/sl cct no' => '',
                                'amt' => '',
                                'product_code/cr' => '1',
                                'gl/sl acct no' => str_replace('-', '', $savingAccount->account_number),
                                'amount' => number_format($remainingAmount, 2, '.', '')
                            ]);
                        }
                    } else {
                        Log::warning("No savings account found for member {$member->id} with product name '{$productName}'");
                    }
                }
            }
        }

        return $exportRows;
    }
}
