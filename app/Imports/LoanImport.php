<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\Member;
use App\Models\LoanProduct;
use App\Models\LoanForecast;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class LoanImport implements ToCollection
{
    protected $billingPeriod;

    public function __construct($billingPeriod)
    {
        $this->billingPeriod = $billingPeriod;
    }

    public function collection(Collection $rows)
    {
        if ($rows->count() < 2) return;

        $priorities = LoanProduct::pluck('prioritization', 'product_code')->toArray();

        $cidLoans = [];

        foreach ($rows as $row) {
            $cidRaw = trim($row[0] ?? '');
            $loanNumber = trim($row[1] ?? ''); // Column C (index 2)
            $principalRaw = trim($row[11] ?? '');
            $startDateRaw = trim($row[7] ?? '');
            $endDateRaw = trim($row[8] ?? '');

            if (empty($cidRaw) || empty($principalRaw) || empty($loanNumber)) {
                continue;
            }

            $cid = ltrim($cidRaw, "'");
            $principal = floatval(str_replace(',', '', $principalRaw));
            $segments = explode('-', $loanNumber);
            $productCode = $segments[2] ?? null; // 3rd segment

            $priority = $priorities[$productCode] ?? null;

            $loanData = [
                'principal' => $principal,
                'priority' => $priority ?? 999, // Non-priority loans get lowest rank
                'start_date' => $this->parseDate($startDateRaw),
                'end_date' => $this->parseDate($endDateRaw),
            ];

            // If no record or better priority found or same priority but higher principal
            if (
                !isset($cidLoans[$cid]) ||
                $loanData['priority'] < $cidLoans[$cid]['priority'] ||
                (
                    $loanData['priority'] === $cidLoans[$cid]['priority'] &&
                    $loanData['principal'] > $cidLoans[$cid]['principal']
                )
            ) {
                $cidLoans[$cid] = $loanData;
            }

            // Update LoanForecast with principal amount
            LoanForecast::where('loan_acct_no', $loanNumber)
                ->update(['principal' => $principal]);
        }

        foreach ($cidLoans as $cid => $data) {
            $member = Member::where('cid', $cid)->first();

            if ($member) {
                $member->update([
                    'principal' => $data['principal'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                ]);
            }
        }
    }


    private function parseDate($date)
    {
        try {
            return Carbon::createFromFormat('m/d/Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
