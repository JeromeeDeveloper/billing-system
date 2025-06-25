<?php

namespace App\Imports;

use App\Models\Member;
use App\Models\LoanForecast;
use App\Models\LoanProduct;
use App\Models\SpecialBilling;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SpecialBillingImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // Get all loan products with billing_type = 'special'
        $specialLoanProducts = LoanProduct::where('billing_type', 'special')
            ->pluck('product_code')
            ->toArray();

        Log::info("=== Special Billing Import Started ===");
        Log::info("Special loan products found: " . implode(', ', $specialLoanProducts));
        Log::info("Total special loan products: " . count($specialLoanProducts));

        // Process all rows and calculate amortization based on special billing types
        $grouped = $rows->groupBy(function ($row) {
            return $row['employee_id'] ?? $row['emp_id'] ?? null;
        });

        Log::info("Total employee groups to process: " . $grouped->count());

        foreach ($grouped as $employeeId => $employeeRows) {
            $first = $employeeRows->first();
            $name = $first['name'] ?? null;
            if (empty($employeeId) || empty($name)) {
                continue; // skip invalid rows
            }

            Log::info("Processing employee: {$employeeId} - {$name}");

            // Calculate amortization from loan forecast data for this member
            $amortization = $this->calculateSpecialAmortization($employeeId, $specialLoanProducts);

            SpecialBilling::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                ],
                [
                    'name'         => $name,
                    'amortization' => $amortization,
                    'start_date'   => $first['start_date'] ?? null,
                    'end_date'     => $first['end_date'] ?? null,
                    'gross'        => $first['gross'] ?? 0,
                    'office'       => $first['office'] ?? null,
                ]
            );
        }

        Log::info("=== Special Billing Import Completed ===");
    }

    private function calculateSpecialAmortization($employeeId, $specialLoanProducts)
    {
        // Find member by employee_id - try different field names
        $member = Member::where('emp_id', $employeeId)
            ->orWhere('employee_id', $employeeId)
            ->orWhere('cid', $employeeId)
            ->first();

        if (!$member) {
            Log::info("Member not found for employee_id: {$employeeId}");
            return 0;
        }

        Log::info("Found member: {$member->fname} {$member->lname} (ID: {$member->id}, emp_id: {$member->emp_id}, cid: {$member->cid})");

        // Get all loan forecasts for this member
        $loanForecasts = LoanForecast::where('member_id', $member->id)->get();

        Log::info("Processing member: {$member->fname} {$member->lname} (ID: {$member->id})");
        Log::info("Special loan products: " . implode(', ', $specialLoanProducts));
        Log::info("Total loan forecasts found: " . $loanForecasts->count());

        $totalAmortization = 0;
        $processedLoans = [];

        foreach ($loanForecasts as $loanForecast) {
            // Extract product code from loan_acct_no (e.g., 40102 from 0304-001-40102-000025-9)
            $productCode = explode('-', $loanForecast->loan_acct_no)[2] ?? null;

            Log::info("Loan: {$loanForecast->loan_acct_no}, Product Code: {$productCode}, Total Due: {$loanForecast->total_due}");

            if ($productCode && in_array($productCode, $specialLoanProducts)) {
                // This loan has a product code that matches a special billing type
                $totalAmortization += $loanForecast->total_due ?? 0;
                $processedLoans[] = $loanForecast->loan_acct_no;
                Log::info("✓ Added to special billing: {$loanForecast->loan_acct_no} (Amount: {$loanForecast->total_due})");
            } else {
                Log::info("✗ Skipped (not special): {$loanForecast->loan_acct_no}");
            }
        }

        Log::info("Final amortization for {$member->fname} {$member->lname}: {$totalAmortization}");
        Log::info("Processed loans: " . implode(', ', $processedLoans));

        return $totalAmortization;
    }
}
