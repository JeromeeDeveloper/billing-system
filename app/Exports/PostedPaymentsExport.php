<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\LoanPayment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PostedPaymentsExport implements FromCollection, WithHeadings
{
    protected $paymentsData;

    public function __construct($paymentsData)
    {
        $this->paymentsData = $paymentsData;
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

        foreach ($this->paymentsData as $record) {
            $member = Member::with(['branch', 'loanForecasts'])
                ->find($record['member_id']);

            if (!$member) {
                Log::warning('Member not found for record: ' . json_encode($record));
                continue;
            }

            // Get all loan payments for this member
            $payments = LoanPayment::where('member_id', $member->id)
                ->where('payment_date', $record['payment_date'])
                ->get();

            foreach ($payments as $payment) {
                // Add loan payment row
                $exportRows->push([
                    'branch_code' => $member->branch->code ?? '',
                    'product_code/dr' => '',
                    'gl/sl cct no' => '',
                    'amt' => '',
                    'product_code/cr' => '4',
                    'gl/sl acct no' => str_replace('-', '', $payment->loanForecast->loan_acct_no),
                    'amount' => number_format($payment->amount, 2, '.', '')
                ]);
            }
        }

        return $exportRows;
    }
}
