<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\Member;
use App\Models\LoanProduct;
use App\Models\SpecialBilling;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Log;

class SpecialBillingImport implements ToCollection
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
        $uploadedCids = []; // Track all CIDs from the uploaded file

        foreach ($rows as $row) {
            $cidRaw = trim($row[0] ?? '');
            $loanNumber = trim($row[1] ?? ''); // Column B (Account No.)
            $principalRaw = trim($row[11] ?? '');
            // Use total_amort (or k5) for total_due, matching LoanForecastImport
            $totalDueRaw = trim($row['total_amort'] ?? $row['k5'] ?? $row[8] ?? '');
            $startDateRaw = trim($row[7] ?? '');
            $endDateRaw = trim($row[8] ?? '');

            if (empty($cidRaw) || empty($principalRaw) || empty($loanNumber)) {
                continue;
            }

            $cid = ltrim($cidRaw, "'");

            // Check if member exists with member_tagging 'PGB' or 'New' before processing
            $member = Member::where('cid', $cid)
                           ->whereIn('member_tagging', ['PGB', 'New'])
                           ->first();

            if (!$member) {
                Log::warning("Skipping CID {$cid} - member not found or not authorized (PGB or New)");
                continue;
            }

            $uploadedCids[] = $cid; // Only track CIDs that match existing members
            $principal = floatval(str_replace(',', '', $principalRaw));
            $totalDue = floatval(str_replace(',', '', $totalDueRaw));
            $segments = explode('-', $loanNumber);
            $productCode = $segments[2] ?? null; // 3rd segment

            $priority = $priorities[$productCode] ?? null;
            $billingType = $billingTypes[$productCode] ?? 'regular';

            // Log the processing for debugging
            Log::info("Processing special loan: CID={$cid}, Account={$loanNumber}, ProductCode={$productCode}, BillingType={$billingType}, Priority={$priority}, Principal={$principal}, TotalDue={$totalDue}");

            // Only process loans that are marked as 'special' billing type
            if ($billingType !== 'special') {
                Log::info("Skipping non-special loan: {$loanNumber} (BillingType: {$billingType})");
                continue;
            }

            $loanData = [
                'loan_number' => $loanNumber,
                'principal' => $principal,
                'total_due' => $totalDue,
                'priority' => $priority ?? 999,
                'billing_type' => $billingType,
                'product_code' => $productCode,
                'start_date' => $this->parseDate($startDateRaw),
                'end_date' => $this->parseDate($endDateRaw),
            ];

            // Group by member
            if (!isset($memberLoans[$cid])) {
                $memberLoans[$cid] = [
                    'member' => $member,
                    'special_loans' => []
                ];
            }

            // Add to special loans
            $memberLoans[$cid]['special_loans'][] = $loanData;
            Log::info("Added to special loans for CID {$cid} with total_due: {$totalDue}");
        }

        // Remove existing SpecialBilling records that don't match uploaded CIDs
        $uniqueUploadedCids = array_unique($uploadedCids);
        $deletedCount = SpecialBilling::whereNotIn('cid', $uniqueUploadedCids)->delete();
        Log::info("Removed {$deletedCount} SpecialBilling records that don't match uploaded CIDs");

        // Process each member's special loans
        foreach ($memberLoans as $cid => $data) {
            $member = $data['member'];
            $loans = $data['special_loans'];

            Log::info("Processing member: {$member->fname} {$member->lname} (CID: {$cid})");
            Log::info("Special loans count: " . count($loans));

            $updateData = [];

            // Process Special Loans - Find highest principal among special loans
            if (!empty($loans)) {
                $bestSpecialLoan = $this->findBestSpecialLoan($loans);
                if ($bestSpecialLoan) {
                    $updateData['special_principal'] = $bestSpecialLoan['principal'];
                    Log::info("Selected special loan: {$bestSpecialLoan['loan_number']} (Principal: {$bestSpecialLoan['principal']})");

                    // Use special loan dates for start_date and end_date
                    $updateData['start_date'] = $bestSpecialLoan['start_date'];
                    $updateData['end_date'] = $bestSpecialLoan['end_date'];
                }
            }

            // Update member with special_principal
            if (!empty($updateData)) {
                $member->update($updateData);
                Log::info("Updated member {$cid} with special_principal: " . json_encode($updateData));
            }

            // Create/update SpecialBilling record using ONLY file data for amortization
            if (!empty($loans)) {
                $bestSpecialLoan = $this->findBestSpecialLoan($loans);
                if ($bestSpecialLoan) {
                    // Calculate amortization from file data ONLY (not from LoanForecast)
                    $fileAmortization = $this->calculateFileAmortization($loans);

                    SpecialBilling::updateOrCreate(
                        [
                            'cid' => $cid, // Use CID for matching
                        ],
                        [
                            'loan_acct_no' => $bestSpecialLoan['loan_number'],
                            'employee_id'  => $member->emp_id ?? $member->cid,
                            'name'         => "{$member->fname} {$member->lname}",
                            'amortization' => $fileAmortization,
                            'start_date'   => $bestSpecialLoan['start_date'],
                            'end_date'     => $bestSpecialLoan['end_date'],
                            'gross'        => $bestSpecialLoan['principal'],
                            'office'       => $member->area_officer ?? null,
                        ]
                    );
                    Log::info("Created/Updated SpecialBilling record for member {$cid} with file-only amortization: {$fileAmortization}");
                }
            }
        }
    }

    private function findBestSpecialLoan($loans)
    {
        if (empty($loans)) {
            return null;
        }

        // For Special Loans: Find highest principal
        usort($loans, function($a, $b) {
            // Sort by highest principal first
            return $b['principal'] <=> $a['principal'];
        });

        // Return the best special loan (highest principal)
        return $loans[0];
    }

    private function calculateFileAmortization($specialLoans)
    {
        // Calculate amortization from the uploaded file data (Total Due values)
        $totalAmortization = 0;

        foreach ($specialLoans as $loan) {
            $totalAmortization += $loan['total_due'] ?? 0;
            Log::info("Added to file amortization: {$loan['loan_number']} (Total Due: {$loan['total_due']})");
        }

        Log::info("Total file amortization: {$totalAmortization}");
        return $totalAmortization;
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
