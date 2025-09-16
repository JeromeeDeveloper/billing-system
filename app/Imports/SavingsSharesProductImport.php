<?php

namespace App\Imports;

use App\Models\Member;
use App\Models\SavingProduct;
use App\Models\ShareProduct;
use App\Models\LoanProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SavingsSharesProductImport implements ToCollection
{
    protected $stats = [
        'processed' => 0,
        'savings_updated' => 0,
        'shares_updated' => 0,
        'loans_updated' => 0,
        'skipped' => 0
    ];

    public function collection(Collection $rows)
    {
        if ($rows->count() < 2) {
            Log::warning("File has insufficient rows for processing");
            return;
        }

        // Get header row (row 1)
        $headerRow = $rows->first();

        // Validate header
        if (trim($headerRow[0] ?? '') !== 'CoreID') {
            Log::error("Invalid header. Expected 'CoreID' in A1, got: " . ($headerRow[0] ?? 'empty'));
            throw new \Exception("Invalid file format. Header A1 must be 'CoreID'");
        }

        // Extract product codes from header (starting from column B)
        $productCodes = [];
        $specialArrangementColumns = [];

        for ($i = 1; $i < count($headerRow); $i++) {
            $productCode = trim($headerRow[$i] ?? '');
            if (!empty($productCode)) {
                // Check if this is a special arrangement column
                if (in_array($productCode, ['SPECIAL ARRANGEMENT-LOAN', 'SPECIAL ARRANGEMENT-SHARE', 'SPECIAL ARRANGEMENT-SAVINGS'])) {
                    $specialArrangementColumns[$i] = $productCode;
                    Log::info("Found special arrangement column: {$productCode} at column " . ($i + 1));
                } else {
                    $productCodes[$i] = $productCode;
                    Log::info("Found product code: {$productCode} at column " . ($i + 1));
                }
            }
        }

        if (empty($productCodes)) {
            Log::error("No product codes found in header row");
            throw new \Exception("No product codes found in header row");
        }

        // Process data rows (starting from row 2)
        for ($rowIndex = 1; $rowIndex < count($rows); $rowIndex++) {
            $row = $rows[$rowIndex];

            // Get CID from column A
            $cidRaw = trim($row[0] ?? '');
            if (empty($cidRaw)) {
                continue; // Skip empty rows
            }

            // Pad CID to 9 digits
            $cid = str_pad(ltrim($cidRaw, "'"), 9, '0', STR_PAD_LEFT);

            // Find member with member_tagging "PGB"
            $member = Member::where('cid', $cid)
                           ->where('member_tagging', 'PGB')
                           ->first();

            if (!$member) {
                Log::warning("Skipping CID {$cid} - member not found or not authorized (PGB)");
                $this->stats['skipped']++;
                continue;
            }

            $this->stats['processed']++;
            Log::info("Processing member: {$member->fname} {$member->lname} (CID: {$cid})");

            // Process special arrangement columns first
            foreach ($specialArrangementColumns as $columnIndex => $specialColumn) {
                $value = trim($row[$columnIndex] ?? '');

                Log::info("Checking special arrangement column {$specialColumn} for member {$member->cid}: value='{$value}'");

                if (empty($value) || !in_array(strtoupper($value), ['SPECIAL ARRANGEMENT', 'SPECIAL ARRANGEMENTS'])) {
                    Log::info("Skipping special arrangement for member {$member->cid}: value='{$value}' (not 'Special Arrangement' or 'Special Arrangements')");
                    continue; // Skip if not "Special Arrangement" or "Special Arrangements"
                }

                Log::info("Processing special arrangement for member {$member->cid}: {$specialColumn}");

                if ($specialColumn === 'SPECIAL ARRANGEMENT-LOAN') {
                    $this->applySpecialArrangementLoans($member);
                } elseif ($specialColumn === 'SPECIAL ARRANGEMENT-SAVINGS') {
                    $this->applySpecialArrangementSavings($member);
                } elseif ($specialColumn === 'SPECIAL ARRANGEMENT-SHARE') {
                    $this->applySpecialArrangementShares($member);
                }
            }

            // Process each product code column
            foreach ($productCodes as $columnIndex => $productCode) {
                $value = trim($row[$columnIndex] ?? '');

                if (empty($value)) {
                    continue; // Skip empty values
                }

                // Check if this is a savings product
                $savingProduct = SavingProduct::where('product_code', $productCode)->first();
                if ($savingProduct) {
                    $this->updateSavingProduct($member, $savingProduct, $value);
                    continue;
                }

                // Check if this is a shares product
                $shareProduct = ShareProduct::where('product_code', $productCode)->first();
                if ($shareProduct) {
                    $this->updateShareProduct($member, $shareProduct, $value);
                    continue;
                }

                // Check if this is a loan product
                $loanProduct = LoanProduct::where('product_code', $productCode)->first();
                if ($loanProduct) {
                    $this->updateLoanProduct($member, $loanProduct, $value);
                    continue;
                }

                Log::warning("Product code {$productCode} not found in savings, shares, or loan products");
            }
        }

        Log::info("Import completed. Stats: " . json_encode($this->stats));
    }

    private function updateSavingProduct($member, $savingProduct, $value)
    {
        try {
            // Find existing saving record for this member and product
            $saving = $member->savings()
                           ->where('product_code', $savingProduct->product_code)
                           ->first();

            if ($saving) {
                // Update existing saving record
                $saving->update([
                    'deduction_amount' => floatval($value),
                    'account_status' => 'deduction'
                ]);
                Log::info("Updated existing saving product {$savingProduct->product_code} for member {$member->cid} with deduction_amount: {$value}");
                $this->stats['savings_updated']++;
            } else {
                // Skip if member doesn't have this savings product - do not create new ones
                Log::info("Member {$member->cid} does not have savings product {$savingProduct->product_code} - skipping (will not create new savings)");
            }
        } catch (\Exception $e) {
            Log::error("Error updating saving product for member {$member->cid}: " . $e->getMessage());
        }
    }

    private function updateShareProduct($member, $shareProduct, $value)
    {
        try {
            // Find existing share record for this member and product
            $share = $member->shares()
                          ->where('product_code', $shareProduct->product_code)
                          ->first();

            if ($share) {
                // Update existing share record
                $share->update([
                    'deduction_amount' => floatval($value),
                    'account_status' => 'deduction'
                ]);
                Log::info("Updated existing share product {$shareProduct->product_code} for member {$member->cid} with deduction_amount: {$value}");
                $this->stats['shares_updated']++;
            } else {
                // Skip if member doesn't have this shares product - do not create new ones
                Log::info("Member {$member->cid} does not have shares product {$shareProduct->product_code} - skipping (will not create new shares)");
            }
        } catch (\Exception $e) {
            Log::error("Error updating share product for member {$member->cid}: " . $e->getMessage());
        }
    }

    private function updateLoanProduct($member, $loanProduct, $value)
    {
        try {
            // Sanitize uploaded total due (remove thousands separators)
            $uploadedTotalDue = (float) str_replace([',', ' '], '', trim((string) $value));

            // Normalize product code
            $productCode = trim((string) $loanProduct->product_code);

            // Current billing period (optional constraint)
            $currentBillingPeriod = Auth::user()->billing_period ?? null;

            // Find existing loan forecasts for this member that match the product code
            $loanForecasts = \App\Models\LoanForecast::where('member_id', $member->id)
                ->where('loan_acct_no', 'like', '%-' . $productCode . '-%')
                ->when($currentBillingPeriod, function($q) use ($currentBillingPeriod) {
                    $q->where(function($qq) use ($currentBillingPeriod) {
                        $qq->whereNull('billing_period')
                           ->orWhere('billing_period', $currentBillingPeriod);
                    });
                })
                ->get();

            Log::info("SavingsSharesProductImport: Member {$member->cid} product {$productCode} -> found {$loanForecasts->count()} loan forecast(s) to update. Uploaded total_due={$uploadedTotalDue}");

            if ($loanForecasts->count() > 0) {
                foreach ($loanForecasts as $loanForecast) {
                    // Keep current interest, adjust principal to match uploaded total
                    $currentInterestDue = (float) ($loanForecast->interest_due ?? 0);
                    $newPrincipalDue = $uploadedTotalDue - $currentInterestDue;
                    if ($newPrincipalDue < 0) {
                        $newPrincipalDue = 0;
                        Log::warning("Adjusted principal_due to 0 for member {$member->cid}, loan {$loanForecast->loan_acct_no} - uploaded total_due ({$uploadedTotalDue}) is less than interest_due ({$currentInterestDue})");
                    }

                    // Apply updates (current)
                    $loanForecast->principal_due = $newPrincipalDue;
                    $loanForecast->total_due = $uploadedTotalDue;
                    if ($currentBillingPeriod) {
                        $loanForecast->billing_period = $currentBillingPeriod;
                    }

                    // Apply updates (originals) with the same values and logic
                    $loanForecast->original_principal_due = $newPrincipalDue;
                    $loanForecast->original_interest_due = $currentInterestDue;
                    $loanForecast->original_total_due = $uploadedTotalDue;

                    $loanForecast->save();

                    Log::info("Updated loan forecast {$loanForecast->loan_acct_no} for member {$member->cid} | new P={$newPrincipalDue}, I={$currentInterestDue}, T={$uploadedTotalDue} | originals set to same values");
                }

                $this->stats['loans_updated'] += $loanForecasts->count();

                // Recalculate member loan_balance to reflect current dues
                try {
                    $billingPeriod = $currentBillingPeriod;
                    $billingEnd = $billingPeriod ? \Carbon\Carbon::parse($billingPeriod . '-01')->endOfMonth() : null;
                    $today = now()->toDateString();

                    $allForecasts = \App\Models\LoanForecast::where('member_id', $member->id)->get();
                    $newLoanBalance = 0.0;

                    foreach ($allForecasts as $lf) {
                        // Due on/before billing period end (or no constraint if billingEnd is null)
                        $isDue = true;
                        if ($billingEnd && $lf->amortization_due_date) {
                            $dueDate = \Carbon\Carbon::parse($lf->amortization_due_date);
                            $isDue = $dueDate->lte($billingEnd);
                        }
                        if (!$isDue) {
                            continue;
                        }

                        // Account status validation: include deduction; include non-deduction only if NOT within hold window
                        if ($lf->account_status === 'non-deduction') {
                            $startHold = $lf->start_hold ? $lf->start_hold : null;
                            $expiryDate = $lf->expiry_date ? $lf->expiry_date : null;
                            $withinHold = (
                                ($startHold && $expiryDate && $today >= $startHold && $today <= $expiryDate) ||
                                ($startHold && !$expiryDate && $today >= $startHold) ||
                                (!$startHold && $expiryDate && $today <= $expiryDate)
                            );
                            if ($withinHold) {
                                continue;
                            }
                        } elseif ($lf->account_status !== 'deduction') {
                            continue;
                        }

                        // Product must be registered for member and billing_type = regular
                        $productCode = explode('-', $lf->loan_acct_no)[2] ?? null;
                        if (!$productCode) {
                            continue;
                        }
                        $hasRegularProduct = $member->loanProductMembers()
                            ->whereHas('loanProduct', function($q) use ($productCode) {
                                $q->where('product_code', $productCode)
                                  ->where('billing_type', 'regular');
                            })
                            ->exists();
                        if (!$hasRegularProduct) {
                            continue;
                        }

                        $newLoanBalance += (float) ($lf->total_due ?? 0);
                    }

                    $member->loan_balance = $newLoanBalance;
                    $member->save();
                    Log::info("Recalculated loan_balance for member {$member->cid}: {$newLoanBalance}");
                } catch (\Exception $e) {
                    Log::error("Failed recalculating loan_balance for member {$member->cid}: " . $e->getMessage());
                }
            } else {
                // Log a brief inventory of member loans to aid troubleshooting
                $sampleLoans = \App\Models\LoanForecast::where('member_id', $member->id)
                    ->select('loan_acct_no', 'total_due', 'principal_due', 'interest_due')
                    ->limit(5)->get();
                Log::warning("No loan forecasts matched for member {$member->cid} and product {$productCode}. Sample member loans: " . $sampleLoans->toJson());
            }

        } catch (\Exception $e) {
            Log::error("Error updating loan product for member {$member->cid}: " . $e->getMessage());
        }
    }

    public function getStats()
    {
        return $this->stats;
    }

    private function applySpecialArrangementLoans($member)
    {
        try {
            $today = now()->format('Y-m');
            Log::info("Applying special arrangement to loans for member {$member->cid}, today: {$today}");

            // Get all existing loan forecasts for this member (do not create new ones)
            $loanForecasts = $member->loanForecasts()->get();
            Log::info("Found {$loanForecasts->count()} existing loan forecasts for member {$member->cid}");

            if ($loanForecasts->isEmpty()) {
                Log::info("No existing loan forecasts found for member {$member->cid} for special arrangement - skipping (will not create new loans)");
                return;
            }

            // Find the longest maturity date among all loans
            $longestMaturityDate = $loanForecasts->max('maturity_date');
            Log::info("Longest maturity date for member {$member->cid}: {$longestMaturityDate}");

            if (!$longestMaturityDate) {
                Log::warning("No maturity dates found for member {$member->cid} loans");
                return;
            }

            // Convert maturity date to YYYY-MM format
            $expiryDate = \Carbon\Carbon::parse($longestMaturityDate)->format('Y-m');
            Log::info("Converted expiry date for member {$member->cid}: {$expiryDate}");

            // Update only existing loan forecasts with special arrangement (do not create new ones)
            foreach ($loanForecasts as $loanForecast) {
                $updateData = [
                    'start_hold' => $today,
                    'expiry_date' => $expiryDate,
                    'account_status' => 'non-deduction'
                ];

                Log::info("Updating existing loan {$loanForecast->loan_acct_no} with data: " . json_encode($updateData));

                $result = $loanForecast->update($updateData);

                Log::info("Update result for loan {$loanForecast->loan_acct_no}: " . ($result ? 'SUCCESS' : 'FAILED'));
                Log::info("Applied special arrangement to existing loan {$loanForecast->loan_acct_no} for member {$member->cid} - start_hold: {$today}, expiry_date: {$expiryDate}");
            }

            Log::info("Special arrangement applied to {$loanForecasts->count()} existing loans for member {$member->cid}");

        } catch (\Exception $e) {
            Log::error("Error applying special arrangement to loans for member {$member->cid}: " . $e->getMessage());
        }
    }

    private function applySpecialArrangementSavings($member)
    {
        try {
            $today = now()->format('Y-m');
            Log::info("Applying special arrangement to savings for member {$member->cid}, today: {$today}");

            // Get all savings for this member
            $savings = $member->savings()->get();
            Log::info("Found {$savings->count()} savings for member {$member->cid}");

            if ($savings->isEmpty()) {
                Log::info("No savings found for member {$member->cid} for special arrangement");
                return;
            }

            // Get the longest maturity date from all loans for this member
            $loanForecasts = $member->loanForecasts()->get();
            $longestMaturityDate = $loanForecasts->max('maturity_date');
            Log::info("Longest maturity date from loans for member {$member->cid}: {$longestMaturityDate}");

            if (!$longestMaturityDate) {
                Log::warning("No maturity dates found for member {$member->cid} loans, using 1 year from today for savings");
                $expiryDate = now()->addYear()->format('Y-m');
            } else {
                // Convert maturity date to YYYY-MM format
                $expiryDate = \Carbon\Carbon::parse($longestMaturityDate)->format('Y-m');
            }

            Log::info("Using expiry date for savings: {$expiryDate}");

            // Update all savings with special arrangement
            foreach ($savings as $saving) {
                $updateData = [
                    'start_hold' => $today,
                    'expiry_date' => $expiryDate,
                    'account_status' => 'non-deduction'
                ];

                Log::info("Updating savings {$saving->account_number} with data: " . json_encode($updateData));

                $result = $saving->update($updateData);

                Log::info("Update result for savings {$saving->account_number}: " . ($result ? 'SUCCESS' : 'FAILED'));
                Log::info("Applied special arrangement to savings {$saving->account_number} for member {$member->cid} - start_hold: {$today}, expiry_date: {$expiryDate}");
            }

            Log::info("Special arrangement applied to {$savings->count()} savings for member {$member->cid}");

        } catch (\Exception $e) {
            Log::error("Error applying special arrangement to savings for member {$member->cid}: " . $e->getMessage());
        }
    }

    private function applySpecialArrangementShares($member)
    {
        try {
            $today = now()->format('Y-m');
            Log::info("Applying special arrangement to shares for member {$member->cid}, today: {$today}");

            // Get all existing shares for this member (do not create new ones)
            $shares = $member->shares()->get();
            Log::info("Found {$shares->count()} existing shares for member {$member->cid}");

            if ($shares->isEmpty()) {
                Log::info("No existing shares found for member {$member->cid} for special arrangement - skipping (will not create new shares)");
                return;
            }

            // Get the longest maturity date from all loans for this member
            $loanForecasts = $member->loanForecasts()->get();
            $longestMaturityDate = $loanForecasts->max('maturity_date');
            Log::info("Longest maturity date from loans for member {$member->cid}: {$longestMaturityDate}");

            if (!$longestMaturityDate) {
                Log::warning("No maturity dates found for member {$member->cid} loans, using 1 year from today for shares");
                $expiryDate = now()->addYear()->format('Y-m');
            } else {
                // Convert maturity date to YYYY-MM format
                $expiryDate = \Carbon\Carbon::parse($longestMaturityDate)->format('Y-m');
            }

            Log::info("Using expiry date for shares: {$expiryDate}");

            // Update only existing shares with special arrangement (do not create new ones)
            foreach ($shares as $share) {
                $updateData = [
                    'start_hold' => $today,
                    'expiry_date' => $expiryDate,
                    'account_status' => 'non-deduction'
                ];

                Log::info("Updating existing shares {$share->account_number} with data: " . json_encode($updateData));

                $result = $share->update($updateData);

                Log::info("Update result for shares {$share->account_number}: " . ($result ? 'SUCCESS' : 'FAILED'));
                Log::info("Applied special arrangement to existing shares {$share->account_number} for member {$member->cid} - start_hold: {$today}, expiry_date: {$expiryDate}");
            }

            Log::info("Special arrangement applied to {$shares->count()} existing shares for member {$member->cid}");

        } catch (\Exception $e) {
            Log::error("Error applying special arrangement to shares for member {$member->cid}: " . $e->getMessage());
        }
    }
}
