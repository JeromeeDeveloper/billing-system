<?php

namespace App\Imports;

use App\Models\Branch;
use App\Models\Member;
use App\Models\LoanForecast;
use App\Models\MasterList;
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

            // Cache branch - update or create
            $branchCode = $row['branch_code'];
            $branch = $this->branchCache[$branchCode] ?? null;
            if (!$branch) {
                $branch = Branch::updateOrCreate(
                    ['code' => $branchCode],
                    ['name' => $row['branch_name']]
                );
                $this->branchCache[$branchCode] = $branch;
            }

            // Parse name - Format as "Lastname, Firstname"
            [$lname, $fname] = array_map('trim', explode(',', $row['name'] . ','));

            // Cache member - update or create
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
                $this->memberCache[$cid] = $member;
            }

            // Update or create loan forecast
            $loanForecast = LoanForecast::updateOrCreate(
                ['loan_acct_no' => $row['loan_account_no']],
                [
                    'open_date' => $this->parseDate($row['open_date']),
                    'maturity_date' => $this->parseDate($row['maturity_date']),
                    'amortization_due_date' => $this->parseDate($row['amortization_due_date']),
                    'total_due' => $this->cleanNumber($row['total_due']),
                    'principal_due' => $this->cleanNumber($row['principal_due']),
                    'interest_due' => $this->cleanNumber($row['interest_due']),
                    'penalty_due' => $this->cleanNumber($row['penalty_due']),
                    'amount_due' => 0,
                    'member_id' => $member->id,
                    'updated_at' => $now,
                ]
            );

            // Create or update master_list entry (use branches_id as column name)
            MasterList::updateOrCreate(
                [
                    'member_id' => $member->id,
                    'loan_forecast_id' => $loanForecast->id,
                ],
                [
                    'branches_id' => $branch->id,
                    'updated_at' => $now,
                    'created_at' => $now,
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
