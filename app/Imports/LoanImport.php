<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\Member;
use App\Models\LoanProduct;
use App\Models\LoanForecast;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Log;

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
        $billingTypes = LoanProduct::pluck('billing_type', 'product_code')->toArray();

        // Group loans by member (CID)
        $memberLoans = [];

        foreach ($rows as $row) {
            $cidRaw = trim($row[0] ?? '');
            $loanNumber = trim($row[1] ?? ''); // Column B (Account No.)
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
            $billingType = $billingTypes[$productCode] ?? 'regular';

            // Log the processing for debugging
            Log::info("Processing loan: CID={$cid}, Account={$loanNumber}, ProductCode={$productCode}, BillingType={$billingType}, Priority={$priority}, Principal={$principal}");

            // Skip special loans - they are handled by SpecialBillingImport
            if ($billingType === 'special') {
                Log::info("Skipping special loan: {$loanNumber} (handled by SpecialBillingImport)");
                continue;
            }

            $loanData = [
                'loan_number' => $loanNumber,
                'principal' => $principal,
                'priority' => $priority ?? 999,
                'billing_type' => $billingType,
                'product_code' => $productCode,
                'start_date' => $this->parseDate($startDateRaw),
                'end_date' => $this->parseDate($endDateRaw),
            ];

            // Group by member
            if (!isset($memberLoans[$cid])) {
                $memberLoans[$cid] = [
                    'regular_loans' => []
                ];
            }

            // Add to regular loans only
            $memberLoans[$cid]['regular_loans'][] = $loanData;
            Log::info("Added to regular loans for CID {$cid}");

            // Update LoanForecast with principal amount
            LoanForecast::where('loan_acct_no', $loanNumber)
                ->update(['principal' => $principal]);
        }

        // Process each member's regular loans
        foreach ($memberLoans as $cid => $loans) {
            $member = Member::where('cid', $cid)->first();

            if (!$member) {
                Log::warning("Member not found for CID: {$cid}");
                continue;
            }

            Log::info("Processing member: {$member->fname} {$member->lname} (CID: {$cid})");
            Log::info("Regular loans count: " . count($loans['regular_loans']));

            $updateData = [];

            // Process Regular Loans - Check prioritization first, then highest principal
            if (!empty($loans['regular_loans'])) {
                $bestRegularLoan = $this->findBestLoan($loans['regular_loans'], 'regular');
                if ($bestRegularLoan) {
                    $updateData['regular_principal'] = $bestRegularLoan['principal'];
                    $updateData['start_date'] = $bestRegularLoan['start_date'];
                    $updateData['end_date'] = $bestRegularLoan['end_date'];
                    Log::info("Selected regular loan: {$bestRegularLoan['loan_number']} (Principal: {$bestRegularLoan['principal']}, Priority: {$bestRegularLoan['priority']})");
                }
            }

            // Update main principal field with the highest principal from regular loans
            if (!empty($loans['regular_loans'])) {
                $highestPrincipal = max(array_column($loans['regular_loans'], 'principal'));
                $updateData['principal'] = $highestPrincipal;
                Log::info("Highest principal from regular loans: {$highestPrincipal}");
            }

            // Update member
            if (!empty($updateData)) {
                $member->update($updateData);
                Log::info("Updated member {$cid} with: " . json_encode($updateData));
            }
        }
    }

    private function findBestLoan($loans, $billingType)
    {
        if (empty($loans)) {
            return null;
        }

        if ($billingType === 'regular') {
            // For Regular Loans:
            // 1. Check prioritization (lowest number first)
            // 2. If same prioritization, find highest principal
            // 3. Check 3rd segment to ensure it's NOT a special billing_type
            usort($loans, function($a, $b) {
                // First priority: lowest priority number
                if ($a['priority'] != $b['priority']) {
                    return $a['priority'] <=> $b['priority'];
                }
                // Second priority: highest principal
                return $b['principal'] <=> $a['principal'];
            });

            // Return the best regular loan (already sorted by priority then principal)
            return $loans[0];

        } else {
            // For Special Loans:
            // 1. Check 3rd segment (product_code)
            // 2. Check if billing_type is 'special' for that product_code
            // 3. Find highest principal among special billing_type loans
            usort($loans, function($a, $b) {
                // Sort by highest principal first
                return $b['principal'] <=> $a['principal'];
            });

            // Return the best special loan (highest principal)
            return $loans[0];
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
