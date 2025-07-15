<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\LoanPayment;
use App\Models\AtmPayment;
use App\Models\SavingsPayment;
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

            // Add savings payment rows for all savings deposits for this ATM payment
            $savingsPayments = SavingsPayment::where('atm_payment_id', $atmPayment->id)->get();
            foreach ($savingsPayments as $savingPayment) {
                $exportRows->push([
                    'branch_code' => $member->branch->code ?? '',
                    'product_code/dr' => '',
                    'gl/sl cct no' => '',
                    'amt' => '',
                    'product_code/cr' => '1',
                    'gl/sl acct no' => str_replace('-', '', $savingPayment->account_number),
                    'amount' => number_format($savingPayment->amount, 2, '.', '')
                ]);
                Log::info("Added savings payment row: {$savingPayment->account_number} - {$savingPayment->amount}");
            }

            // (Retain the old single allocation logic as fallback if needed)
        }

        Log::info("Total export rows generated: {$exportRows->count()}");
        return $exportRows;
    }
}
