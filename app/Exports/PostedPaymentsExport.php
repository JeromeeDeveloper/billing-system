<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\LoanPayment;
use App\Models\AtmPayment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PostedPaymentsExport implements FromCollection, WithHeadings
{
    protected $atmPayments;

    public function __construct($atmPayments)
    {
        $this->atmPayments = $atmPayments;
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

        foreach ($this->atmPayments as $atmPayment) {
            $member = $atmPayment->member;

            if (!$member) {
                Log::warning('Member not found for ATM payment: ' . $atmPayment->id);
                continue;
            }

            Log::info("Processing ATM payment for member {$member->id}: Withdrawal: {$atmPayment->withdrawal_amount}, Loan Payment: {$atmPayment->total_loan_payment}, Savings: {$atmPayment->savings_allocation}");

            // Get all loan payments for this ATM payment
            $loanPayments = LoanPayment::where('member_id', $member->id)
                ->where('payment_date', $atmPayment->payment_date)
                ->where('withdrawal_amount', $atmPayment->withdrawal_amount)
                ->get();

            Log::info("Found {$loanPayments->count()} loan payments for this ATM payment");

            // Add loan payment rows
            foreach ($loanPayments as $payment) {
                $exportRows->push([
                    'branch_code' => $member->branch->code ?? '',
                    'product_code/dr' => '',
                    'gl/sl cct no' => '',
                    'amt' => '',
                    'product_code/cr' => '4',
                    'gl/sl acct no' => str_replace('-', '', $payment->loanForecast->loan_acct_no),
                    'amount' => number_format($payment->amount, 2, '.', '')
                ]);

                Log::info("Added loan payment row: {$payment->loanForecast->loan_acct_no} - {$payment->amount}");
            }

            // Add savings allocation row if there is one
            if ($atmPayment->savings_allocation > 0) {
                if ($atmPayment->savings_account_number) {
                    $exportRows->push([
                        'branch_code' => $member->branch->code ?? '',
                        'product_code/dr' => '',
                        'gl/sl cct no' => '',
                        'amt' => '',
                        'product_code/cr' => '1',
                        'gl/sl acct no' => str_replace('-', '', $atmPayment->savings_account_number),
                        'amount' => number_format($atmPayment->savings_allocation, 2, '.', '')
                    ]);

                    Log::info("Added savings allocation row: {$atmPayment->savings_account_number} - {$atmPayment->savings_allocation}");
                } else {
                    // If no account number, try to find the member's savings account
                    $memberSavings = $member->savings()->first();
                    if ($memberSavings) {
                        $exportRows->push([
                            'branch_code' => $member->branch->code ?? '',
                            'product_code/dr' => '',
                            'gl/sl cct no' => '',
                            'amt' => '',
                            'product_code/cr' => '1',
                            'gl/sl acct no' => str_replace('-', '', $memberSavings->account_number),
                            'amount' => number_format($atmPayment->savings_allocation, 2, '.', '')
                        ]);

                        Log::info("Added savings allocation row (fallback): {$memberSavings->account_number} - {$atmPayment->savings_allocation}");
                    } else {
                        Log::warning("ATM Payment {$atmPayment->id} has savings allocation {$atmPayment->savings_allocation} but no savings account found for member {$member->id}");
                    }
                }
            }
        }

        Log::info("Total export rows generated: {$exportRows->count()}");
        return $exportRows;
    }
}
