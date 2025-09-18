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

class PostedPaymentsExportwithDescription implements FromCollection, WithHeadings
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
            'amount',
            'interest',
            'penalty',
            'principal',
            'reference_code',
            'notes',
            'loan_product_name',
            'savings_product_name'
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

            // Build shared details once per ATM payment
            $loanParts = $loanPayments->map(function($p) {
                $acct = optional($p->loanForecast)->loan_acct_no;
                return ($acct ? (str_replace('-', '', $acct)) : 'LOAN') . ':' . number_format($p->amount, 2, '.', '');
            })->toArray();

            $savingsPayments = SavingsPayment::where('atm_payment_id', $atmPayment->id)->get();
            $savingsParts = $savingsPayments->map(function($sp) {
                return (str_replace('-', '', $sp->account_number)) . ':' . number_format($sp->amount, 2, '.', '');
            })->toArray();

            $referenceCode = (string)($atmPayment->reference_number ?? '');
            $notes = (string)($atmPayment->notes ?? '');

            // Add loan payment rows with distribution details
            foreach ($loanPayments as $payment) {
                $loanProductName = optional($payment->loanForecast)->loan_product_name ?? '';
                $loanAcctNoRaw = optional($payment->loanForecast)->loan_acct_no;
                $loanAcctNo = $loanAcctNoRaw ? str_replace('-', '', $loanAcctNoRaw) : '';
                $exportRows->push([
                    'branch_code' => $member->branch->code ?? '',
                    'product_code/dr' => '',
                    'gl/sl cct no' => '',
                    'amt' => '',
                    'product_code/cr' => '4',
                    'gl/sl acct no' => $loanAcctNo,
                    'amount' => number_format($payment->amount, 2, '.', ''),
                    'interest' => number_format($payment->applied_to_interest, 2, '.', ''),
                    'penalty' => number_format($payment->penalty, 2, '.', ''),
                    'principal' => number_format($payment->applied_to_principal, 2, '.', ''),
                    'reference_code' => $referenceCode,
                    'notes' => $notes,
                    'loan_product_name' => $loanProductName,
                    'savings_product_name' => ''
                ]);

                Log::info("Added loan payment row: {$payment->loanForecast->loan_acct_no} - {$payment->amount} (Interest: {$payment->applied_to_interest}, Principal: {$payment->applied_to_principal}, Penalty: {$payment->penalty})");
            }

            // Add savings payment rows for all savings deposits for this ATM payment
            foreach ($savingsPayments as $savingPayment) {
                $saving = $member->savings()->where('account_number', $savingPayment->account_number)->with('savingProduct')->first();
                $savingsProductName = optional(optional($saving)->savingProduct)->product_name ?? '';
                $exportRows->push([
                    'branch_code' => $member->branch->code ?? '',
                    'product_code/dr' => '',
                    'gl/sl cct no' => '',
                    'amt' => '',
                    'product_code/cr' => '1',
                    'gl/sl acct no' => str_replace('-', '', $savingPayment->account_number),
                    'amount' => number_format($savingPayment->amount, 2, '.', ''),
                    'interest' => '',
                    'penalty' => '',
                    'principal' => '',
                    'reference_code' => $referenceCode,
                    'notes' => $notes,
                    'loan_product_name' => '',
                    'savings_product_name' => $savingsProductName
                ]);
                Log::info("Added savings payment row: {$savingPayment->account_number} - {$savingPayment->amount}");
            }

            // (Retain the old single allocation logic as fallback if needed)
        }

        Log::info("Total export rows generated: {$exportRows->count()}");
        return $exportRows;
    }
}
