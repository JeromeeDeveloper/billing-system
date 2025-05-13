<?php

namespace App\Imports;

use App\Models\Branch;
use App\Models\Member;
use App\Models\LoanForecast;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;
use Illuminate\Support\Str;

class LoanForecastImport implements ToCollection, WithHeadingRow
{
    protected $branchCache = [];
    protected $memberCache = [];

    public function headingRow(): int
    {
        return 5;
    }

    public function collection(Collection $rows)
    {
        $now = now();

        foreach ($rows as $row) {
            if (empty($row['cid']) || empty($row['branch_code'])) {
                continue;
            }

            // Cache branch - Use updateOrCreate to either update or insert the branch
            $branchCode = $row['branch_code'];
            $branch = $this->branchCache[$branchCode] ?? null;
            if (!$branch) {
                $branch = Branch::updateOrCreate(
                    ['code' => $branchCode],
                    ['name' => $row['branch_name']]
                );
                $this->branchCache[$branchCode] = $branch; // Cache the branch
            }

            // Parse name - Format as "Lastname, Firstname"
            [$lname, $fname] = array_map('trim', explode(',', $row['name'] . ','));

            // Cache member - Use updateOrCreate to either update or insert the member
            $cid = $row['cid'];
            $member = $this->memberCache[$cid] ?? null;
            if (!$member) {
                $member = Member::updateOrCreate(
                    ['cid' => $cid],
                    [
                        'branch_id' => $branch->id,
                        'fname' => $fname,
                        'lname' => $lname,
                        'emp_id' => 'EMP-' . Str::random(8),
                        'address' => '',
                        'savings_balance' => 0,
                        'share_balance' => 0,
                        'loan_balance' => 0,
                    ]
                );
                $this->memberCache[$cid] = $member; // Cache the member
            }

            // Use updateOrCreate to either insert or update the loan forecast
            LoanForecast::updateOrCreate(
                ['loan_acct_no' => $row['loan_account_no']], // Match by loan account number
                [
                    'open_date'              => $this->parseDate($row['open_date']),
                    'maturity_date'          => $this->parseDate($row['maturity_date']),
                    'amortization_due_date'  => $this->parseDate($row['amortization_due_date']),
                    'total_due'              => $this->cleanNumber($row['total_due']),
                    'principal_due'          => $this->cleanNumber($row['principal_due']),
                    'interest_due'           => $this->cleanNumber($row['interest_due']),
                    'penalty_due'            => $this->cleanNumber($row['penalty_due']),
                    'amount_due'             => 0, // Default value, adjust as needed
                    'member_id'              => $member->id,
                    'updated_at'             => $now,
                ]
            );
        }
    }

    private function parseDate($value)
    {
        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value);
            }
            return Carbon::parse($value);
        } catch (\Exception $e) {
            \Log::error('Date parse error: ' . $value);
            return null;
        }
    }

    private function cleanNumber($value)
    {
        return floatval(str_replace(',', '', $value));
    }
}
