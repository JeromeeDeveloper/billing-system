<?php

namespace App\Http\Controllers;

use App\Models\SpecialBilling;
use App\Models\Member;
use App\Models\LoanProduct;
use Illuminate\Http\Request;
use App\Imports\SpecialBillingImport;
use App\Exports\SpecialBillingExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SpecialBillingController extends Controller
{
    public function index(Request $request)
    {
        $query = SpecialBilling::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('employee_id', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%")
                  ->orWhere('cid', 'LIKE', "%{$search}%");
            });
        }

        // Pagination
        $specialBillings = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('components.admin.special_billing', compact('specialBillings'));
    }

    public function import(Request $request)
    {
        ini_set('max_execution_time', 600);
        $request->validate([
            'forecast_file' => 'required|file|max:10240',
            'detail_file' => 'required|file|max:10240',
        ]);

        // Get all loan products with billing_type = 'special'
        $specialLoanProducts = LoanProduct::where('billing_type', 'special')
            ->pluck('product_code')
            ->toArray();

        Log::info("=== Special Billing Import Started ===");
        Log::info("Special loan products found: " . implode(', ', $specialLoanProducts));
        Log::info("Total special loan products: " . count($specialLoanProducts));

        // Clear existing special billing data
        SpecialBilling::truncate();

        // 1. Process forecast file (same structure as LoanForecastImport)
        $forecastRows = Excel::toCollection(null, $request->file('forecast_file'))[0];
        $memberSpecialLoans = [];

        foreach ($forecastRows as $i => $row) {
            if ($i < 1) continue; // Skip header row

            $cidRaw = trim($row[2] ?? '');
            $loanNumber = trim($row[4] ?? ''); // Column B (Account No.)
            $totalDueRaw = trim($row[8] ?? ''); // Column I (Total Due)

            if (empty($cidRaw) || empty($loanNumber) || empty($totalDueRaw)) {
                continue;
            }

            $cid = ltrim($cidRaw, "'");
            $totalDue = floatval(str_replace(',', '', $totalDueRaw));
            $segments = explode('-', $loanNumber);
            $productCode = $segments[2] ?? null; // 3rd segment

            // Only process loans that are marked as 'special' billing type
            if (!$productCode || !in_array($productCode, $specialLoanProducts)) {
                continue;
            }

            $member = Member::where('cid', $cid)->first();
            if (!$member) {
                Log::warning("Member not found for CID: {$cid}");
                continue;
            }

            Log::info("Processing special loan: CID={$cid}, Loan={$loanNumber}, ProductCode={$productCode}, TotalDue={$totalDue}");

            // Group by member CID and sum total_due for special loans only
            if (!isset($memberSpecialLoans[$cid])) {
                $memberSpecialLoans[$cid] = [
                    'name' => "{$member->fname} {$member->lname}",
                    'total_amortization' => 0,
                    'member' => $member
                ];
            }

            // Add this special loan's total_due to the member's amortization
            $memberSpecialLoans[$cid]['total_amortization'] += $totalDue;
        }

        Log::info("Members with special loans found: " . count($memberSpecialLoans));

        // 2. Process detail file (CSV: skip first 5 rows, row 6 is header, row 7+ is data)
        $detailRowsRaw = Excel::toCollection(null, $request->file('detail_file'))[0];
        $detailRows = collect();
        $headerRow = [];
        foreach ($detailRowsRaw as $i => $row) {
            if ($i == 5) {
                $headerRow = $row->toArray();
            } elseif ($i > 5) {
                $assoc = [];
                foreach ($headerRow as $k => $header) {
                    $assoc[strtolower(trim($header))] = $row[$k] ?? null;
                }
                $detailRows->push($assoc);
            }
        }

        // Filter detail rows to only include those with special billing types
        $filteredDetailRows = $detailRows->filter(function ($row) use ($specialLoanProducts) {
            $accountNo = $row['account no'] ?? null;
            if (empty($accountNo)) {
                return false;
            }

            // Extract product code from account number (e.g., 40102 from 0304-001-40102-000002-7)
            $productCode = explode('-', $accountNo)[2] ?? null;

            if (!$productCode) {
                return false;
            }

            // Only include if the product code matches a loan product with billing_type = 'special'
            return in_array($productCode, $specialLoanProducts);
        });

        Log::info("Detail rows after filtering for special billing: " . $filteredDetailRows->count() . " out of " . $detailRows->count());

        // Group detail rows by CID and process with prioritization logic
        $detailByCid = [];
        foreach ($filteredDetailRows as $row) {
            $cid = strval(trim($row['cid'] ?? ''));
            $accountNo = strval(trim($row['account no'] ?? ''));

            // Clean principal release value (remove commas and format properly)
            $principalReleaseRaw = $row['principal release'] ?? 0;
            $principalRelease = 0;
            if (!empty($principalReleaseRaw)) {
                $principalRelease = preg_replace('/[^0-9.]/', '', str_replace(',', '', $principalReleaseRaw));
                $principalRelease = floatval($principalRelease);
            }

            $openDateRaw = $row['open date'] ?? null;
            $maturityDateRaw = $row['maturity date'] ?? null;

            if (empty($cid) || empty($accountNo)) continue;

            // Parse dates (mm/dd/yyyy format)
            $openDate = null;
            $maturityDate = null;
            try {
                if ($openDateRaw) $openDate = Carbon::createFromFormat('m/d/Y', $openDateRaw)->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning("Invalid open date format for CID {$cid}: {$openDateRaw}");
            }
            try {
                if ($maturityDateRaw) $maturityDate = Carbon::createFromFormat('m/d/Y', $maturityDateRaw)->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning("Invalid maturity date format for CID {$cid}: {$maturityDateRaw}");
            }

            // Extract product code from account number (e.g., 40102 from 0304-001-40102-000002-7)
            $accountSegments = explode('-', $accountNo);
            $productCode = $accountSegments[2] ?? null;

            // Get loan product prioritization
            $loanProduct = LoanProduct::where('product_code', $productCode)->first();
            $prioritization = $loanProduct ? $loanProduct->prioritization : 999;

            $loanData = [
                'account_no' => $accountNo,
                'product_code' => $productCode,
                'prioritization' => $prioritization,
                'principal_release' => $principalRelease,
                'open_date' => $openDate,
                'maturity_date' => $maturityDate,
            ];

            // Store loan data for this CID
            if (!isset($detailByCid[$cid])) {
                $detailByCid[$cid] = [];
            }
            $detailByCid[$cid][] = $loanData;
        }

        // 3. Process each CID to find the best loan based on prioritization and principal release
        foreach ($detailByCid as $cid => $loans) {
            // Sort loans by prioritization first, then by principal release (descending)
            usort($loans, function($a, $b) {
                if ($a['prioritization'] !== $b['prioritization']) {
                    return $a['prioritization'] - $b['prioritization']; // Lower prioritization number = higher priority
                }
                return $b['principal_release'] - $a['principal_release']; // Higher principal release = better
            });

            // Get the best loan (first after sorting)
            $bestLoan = $loans[0];

            Log::info("Selected loan for CID {$cid}:", [
                'account_no' => $bestLoan['account_no'],
                'product_code' => $bestLoan['product_code'],
                'prioritization' => $bestLoan['prioritization'],
                'principal_release' => $bestLoan['principal_release'],
                'open_date' => $bestLoan['open_date'],
                'maturity_date' => $bestLoan['maturity_date'],
            ]);

            $detailByCid[$cid] = [
                'start_date' => $bestLoan['open_date'],
                'end_date' => $bestLoan['maturity_date'],
                'gross' => $bestLoan['principal_release'],
                'account_no' => $bestLoan['account_no'],
                'product_code' => $bestLoan['product_code'],
            ];
        }

        // 4. Create special billing records combining forecast file amortization with detail file data
        foreach ($memberSpecialLoans as $cid => $data) {
            $detail = $detailByCid[$cid] ?? null;

            Log::info("Creating special billing record for CID {$cid}:");
            Log::info("  - Name: {$data['name']}");
            Log::info("  - Amortization (from forecast file): {$data['total_amortization']}");
            Log::info("  - Has detail data: " . ($detail ? 'YES' : 'NO'));

            SpecialBilling::create([
                'cid' => $cid,
                'employee_id' => $data['member']->emp_id ?? 'N/A',
                'name' => $data['name'],
                'amortization' => $data['total_amortization'], // From forecast file data
                'loan_acct_no' => $detail['account_no'] ?? null, // From detail file
                'start_date' => $detail['start_date'] ?? null, // From detail file
                'end_date' => $detail['end_date'] ?? null, // From detail file
                'gross' => $detail['gross'] ?? 0, // From detail file
                'office' => $data['member']->area_officer ?? null,
            ]);
        }

        Log::info("=== Special Billing Import Completed ===");
        Log::info("Total special billing records created: " . count($memberSpecialLoans));

        return redirect()->route('special-billing.index')->with('success', 'Special billing data imported successfully from forecast file and detail file.');
    }

    public function export()
    {
        return Excel::download(new SpecialBillingExport, 'special_billing_export_' . now()->format('Y-m-d') . '.csv');
    }
}
