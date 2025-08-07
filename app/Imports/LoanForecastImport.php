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

            // Prepare values from Excel
            $newPrincipalDue = $this->cleanNumber($row['principal'] ?? $row['i5'] ?? 0);
            $newInterestDue = $this->cleanNumber($row['interest'] ?? $row['j5'] ?? 0);
            // Calculate total_due based on principal_due + interest_due
            $newTotalDue = $newPrincipalDue + $newInterestDue;

            // Update or create loan forecast with billing period (without total_due)
            $existingForecast = LoanForecast::where('loan_acct_no', $row['loan_account_no'])->first();
            if ($existingForecast) {
                $existingForecast->refresh(); // Always get the latest from DB
                $existingForecast->open_date = $this->parseDate($row['open_date']);
                $existingForecast->maturity_date = $this->parseDate($row['maturity_date']);
                $existingForecast->amortization_due_date = $this->parseDate($row['amortization_due_date']);
                $existingForecast->billing_period = $this->billingPeriod;
                $existingForecast->updated_at = $now;
                // Only update if status is not 'paid' (check before assignment!)
                if ($existingForecast->principal_due_status !== 'paid') {
                    $originalPrincipal = $existingForecast->original_principal_due ?? $existingForecast->principal_due;
                    // If billing period was reset (original values are 0), use new values
                    if ($originalPrincipal == 0 && $newPrincipalDue > 0) {
                        $existingForecast->principal_due = $newPrincipalDue;
                    } else if ($newPrincipalDue > $originalPrincipal) {
                        $existingForecast->principal_due = $originalPrincipal;
                    } else {
                        $existingForecast->principal_due = $newPrincipalDue;
                    }
                }
                if ($existingForecast->interest_due_status !== 'paid') {
                    $originalInterest = $existingForecast->original_interest_due ?? $existingForecast->interest_due;
                    // If billing period was reset (original values are 0), use new values
                    if ($originalInterest == 0 && $newInterestDue > 0) {
                        $existingForecast->interest_due = $newInterestDue;
                    } else if ($newInterestDue > $originalInterest) {
                        $existingForecast->interest_due = $originalInterest;
                    } else {
                        $existingForecast->interest_due = $newInterestDue;
                    }
                }
                // Auto-calculate total_due based on principal_due + interest_due
                $existingForecast->total_due = $existingForecast->principal_due + $existingForecast->interest_due;

                // Do not update statuses here - they will be updated by RemittanceImport
                $existingForecast->save();
                $loanForecast = $existingForecast;
            } else {
                $loanForecast = LoanForecast::create([
                    'loan_acct_no' => $row['loan_account_no'],
                    'open_date' => $this->parseDate($row['open_date']),
                    'maturity_date' => $this->parseDate($row['maturity_date']),
                    'amortization_due_date' => $this->parseDate($row['amortization_due_date']),
                    'principal_due' => $newPrincipalDue,
                    'interest_due' => $newInterestDue,
                    'member_id' => $member->id,
                    'billing_period' => $this->billingPeriod,
                    'updated_at' => $now,
                    'interest_due_status' => 'unpaid', // Default status, will be updated by RemittanceImport
                    'principal_due_status' => 'unpaid', // Default status, will be updated by RemittanceImport
                    'total_due_status' => 'unpaid', // Default status, will be updated by RemittanceImport
                    'original_principal_due' => $newPrincipalDue,
                    'original_interest_due' => $newInterestDue,
                ]);
            }

            // total_due will be auto-calculated by the model based on principal_due + interest_due

            // Set original_total_due if null or if billing_period is different or if original values are 0 (reset)
            if (is_null($loanForecast->original_total_due) ||
                $loanForecast->billing_period !== $this->billingPeriod ||
                $loanForecast->original_total_due == 0) {
                $loanForecast->original_total_due = $loanForecast->total_due;
                // Set original_principal_due and original_interest_due as well
                $loanForecast->original_principal_due = $loanForecast->principal_due;
                $loanForecast->original_interest_due = $loanForecast->interest_due;
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
                    'billing_period' => $this->billingPeriod,
                ],
                [
                    'branches_id' => $branch->id,
                    'loan_forecast_id' => $loanForecast->id,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $this->stats['processed']++;
        }

        // Update loan balance for each processed member
        foreach ($this->memberCache as $member) {
            $loanForecasts = LoanForecast::where('member_id', $member->id)
                ->where('billing_period', $this->billingPeriod)
                ->get();

            $productMap = [];
            foreach (LoanProduct::all() as $product) {
                $productMap[$product->product_code] = $product->billing_type;
            }

            $billingEnd = \Carbon\Carbon::parse($this->billingPeriod . '-01')->endOfMonth();
            $today = now();
            $loan_balance = 0;
            foreach ($loanForecasts as $forecast) {
                $segments = explode('-', $forecast->loan_acct_no);
                $productCode = $segments[2] ?? null;
                $billingType = $productMap[$productCode] ?? null;
                // Registered, not special/not_billed
                $hasSpecialProduct = $member->loanProductMembers()->whereHas('loanProduct', function($query) use ($productCode) {
                    $query->where('product_code', $productCode)
                          ->where('billing_type', 'special');
                })->exists();
                $hasNotBilledProduct = $member->loanProductMembers()->whereHas('loanProduct', function($query) use ($productCode) {
                    $query->where('product_code', $productCode)
                          ->where('billing_type', 'not_billed');
                })->exists();
                $hasRegisteredProduct = $member->loanProductMembers()->whereHas('loanProduct', function($query) use ($productCode) {
                    $query->where('product_code', $productCode);
                })->exists();
                // Account status logic
                $isDeduction = $forecast->account_status === 'deduction';
                $isNonDeductionOutsideHold = $forecast->account_status === 'non-deduction' && (
                    (empty($forecast->start_hold) || $forecast->start_hold > $today) ||
                    (!empty($forecast->expiry_date) && $forecast->expiry_date < $today)
                );
                // Amortization due date logic (robust for Carbon or string)
                $isDue = true;
                if ($forecast->amortization_due_date) {
                    $dueDate = $forecast->amortization_due_date;
                    if (!($dueDate instanceof \Carbon\Carbon)) {
                        try {
                            $dueDate = \Carbon\Carbon::parse($dueDate);
                        } catch (\Exception $e) {
                            try {
                                $dueDate = \Carbon\Carbon::createFromFormat('n/j/Y', $dueDate);
                            } catch (\Exception $e2) {
                                $isDue = false;
                            }
                        }
                    }
                    if ($isDue) {
                        $isDue = $dueDate->lte($billingEnd);
                    }
                }
                if ($hasRegisteredProduct && !$hasSpecialProduct && !$hasNotBilledProduct && $billingType === 'regular' && ($isDeduction || $isNonDeductionOutsideHold) && $isDue) {
                    $loan_balance += $forecast->original_total_due ?? $forecast->total_due;
                }
            }
            $member->update(['loan_balance' => $loan_balance]);
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
