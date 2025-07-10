<?php

namespace App\Imports;

use App\Models\Branch;
use App\Models\Member;
use App\Models\LoanForecast;
use App\Models\LoanProduct;
use App\Models\MasterList;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LoanForecastImport implements ToCollection, WithHeadingRow
{
    protected $branchCache = [];
    protected $memberCache = [];
    protected $billingPeriod;
    protected $stats = [
        'processed' => 0,
        'skipped' => 0,
        'not_found' => 0
    ];

    public function __construct(string $billingPeriod)
    {
        $this->billingPeriod = $billingPeriod;
    }

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

            // Check if member exists with PGB or New tagging
            $cid = $row['cid'];
            $member = Member::where('cid', $cid)
                           ->whereIn('member_tagging', ['PGB', 'New'])
                           ->first();

            if (!$member) {
                // Log skipped member and continue
                Log::info("LoanForecast Import - Skipped CID {$cid}: Member not found or not tagged as PGB or New");
                $this->stats['not_found']++;
                continue;
            }

            // Cache member for performance
            $this->memberCache[$cid] = $member;

            // Match member_id from LoanProduct using the 3rd part of loan_acct_no
            $loanAcctParts = explode('-', $row['loan_account_no']);
            $productCodePart = isset($loanAcctParts[2]) ? trim($loanAcctParts[2]) : null;

            $loanProductMemberIds = [];  // Array of member ids linked to loan products

            if ($productCodePart) {
                // Get all loan products with this product code
                $loanProducts = LoanProduct::where('product_code', $productCodePart)
                    ->orderBy('prioritization', 'asc')
                    ->get();

                foreach ($loanProducts as $loanProduct) {
                    // Attach the member to the loan product pivot if not already attached
                    if (!$loanProduct->members()->where('member_id', $member->id)->exists()) {
                        $loanProduct->members()->attach($member->id);
                    }
                }

                // Collect all member IDs linked to these loan products (optional)
                foreach ($loanProducts as $loanProduct) {
                    $loanProductMemberIds = array_merge($loanProductMemberIds, $loanProduct->members()->pluck('members.id')->toArray());
                }
            }

            // Update or create loan forecast with billing period
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
                    'member_id' => $loanProductMemberId ?? $member->id, // use product match or fallback
                    'billing_period' => $this->billingPeriod,
                    'updated_at' => $now,
                ]
            );

            // Set original_total_due if null or if billing_period is different
            if (is_null($loanForecast->original_total_due) || $loanForecast->billing_period !== $this->billingPeriod) {
                $loanForecast->original_total_due = $loanForecast->total_due;
                $loanForecast->save();
            }

            // Update member's branch_id if it's different
            if ($member->branch_id != $branch->id) {
                $member->update(['branch_id' => $branch->id]);
            }

            // Update member's name if provided in import
            if (!empty($fname) || !empty($lname)) {
                $updateData = [];
                if (!empty($fname)) $updateData['fname'] = $fname;
                if (!empty($lname)) $updateData['lname'] = $lname;
                if (!empty($updateData)) {
                    $member->update($updateData);
                }
            }

            // Create or update master_list entry with billing period
            MasterList::updateOrCreate(
                [
                    'member_id' => $member->id,
                    'loan_forecast_id' => $loanForecast->id,
                ],
                [
                    'branches_id' => $branch->id,
                    'updated_at' => $now,
                    'created_at' => $now,
                    'billing_period' => $this->billingPeriod,
                ]
            );

            $this->stats['processed']++;
        }

        // Update loan balance for each processed member
        foreach ($this->memberCache as $member) {
            $loanBalance = LoanForecast::where('member_id', $member->id)
                ->where('billing_period', $this->billingPeriod)
                ->sum('total_due');

            $member->update(['loan_balance' => $loanBalance]);
        }

        // Log import statistics
        Log::info("LoanForecast Import completed - Processed: {$this->stats['processed']}, Not Found: {$this->stats['not_found']}");
    }

    public function getStats()
    {
        return $this->stats;
    }

    private function parseDate($value)
    {
        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value);
            }
            return Carbon::parse($value);
        } catch (\Exception $e) {
            Log::error('Date parse error: ' . $value);
            return null;
        }
    }

    private function cleanNumber($value)
    {
        return floatval(str_replace(',', '', $value));
    }
}
