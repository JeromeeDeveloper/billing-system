<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\Member;
use App\Models\LoanProduct;
use App\Models\LoanForecast;
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
            Log::info("Processing special loan: CID={$cid}, Account={$loanNumber}, ProductCode={$productCode}, BillingType={$billingType}, Priority={$priority}, Principal={$principal}");

            // Only process loans that are marked as 'special' billing type
            if ($billingType !== 'special') {
                Log::info("Skipping non-special loan: {$loanNumber} (BillingType: {$billingType})");
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
                    'special_loans' => []
                ];
            }

            // Add to special loans
            $memberLoans[$cid]['special_loans'][] = $loanData;
            Log::info("Added to special loans for CID {$cid}");

            // Update LoanForecast with principal amount
            LoanForecast::where('loan_acct_no', $loanNumber)
                ->update(['principal' => $principal]);
        }

        // Process each member's special loans
        foreach ($memberLoans as $cid => $loans) {
            $member = Member::where('cid', $cid)->first();

            if (!$member) {
                Log::warning("Member not found for CID: {$cid}");
                continue;
            }

            Log::info("Processing member: {$member->fname} {$member->lname} (CID: {$cid})");
            Log::info("Special loans count: " . count($loans['special_loans']));

            $updateData = [];

            // Process Special Loans - Find highest principal among special loans
            if (!empty($loans['special_loans'])) {
                $bestSpecialLoan = $this->findBestSpecialLoan($loans['special_loans']);
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

            // Also create/update SpecialBilling record
            if (!empty($loans['special_loans'])) {
                $bestSpecialLoan = $this->findBestSpecialLoan($loans['special_loans']);
                if ($bestSpecialLoan) {
                    SpecialBilling::updateOrCreate(
                        [
                            'employee_id' => $member->emp_id ?? $member->cid,
                        ],
                        [
                            'name'         => "{$member->fname} {$member->lname}",
                            'amortization' => $this->calculateSpecialAmortization($member, $bestSpecialLoan['product_code']),
                            'start_date'   => $bestSpecialLoan['start_date'],
                            'end_date'     => $bestSpecialLoan['end_date'],
                            'gross'        => $bestSpecialLoan['principal'],
                            'office'       => $member->area_officer ?? null,
                        ]
                    );
                    Log::info("Created/Updated SpecialBilling record for member {$cid}");
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

    private function calculateSpecialAmortization($member, $productCode)
    {
        // Calculate amortization from loan forecast data for this member and product code
        $totalAmortization = LoanForecast::where('member_id', $member->id)
            ->where('loan_acct_no', 'like', "%-{$productCode}-%")
            ->sum('total_due');

        Log::info("Calculated special amortization for {$member->fname} {$member->lname}: {$totalAmortization}");
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
