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
            'forecast_file' => 'required|mimes:xlsx,xls,csv|max:10240',
            'detail_file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        // 1. Process forecast file (CSV: skip first 4 rows, row 5 is header, row 6+ is data)
        $forecastRowsRaw = Excel::toCollection(null, $request->file('forecast_file'))[0];
        $forecastRows = collect();
        $headerRow = [];
        foreach ($forecastRowsRaw as $i => $row) {
            if ($i == 4) {
                $headerRow = $row->toArray();
            } elseif ($i > 4) {
                $assoc = [];
                foreach ($headerRow as $k => $header) {
                    $assoc[strtolower(trim($header))] = $row[$k] ?? null;
                }
                $forecastRows->push($assoc);
            }
        }
        $forecastGrouped = $forecastRows->groupBy(function ($row) {
            return strval(trim($row['cid'] ?? ''));
        });
        $forecastData = [];
        foreach ($forecastGrouped as $cid => $rows) {
            if (empty($cid)) continue;
            $totalDue = $rows->sum(function ($row) {
                $value = $row['total due'] ?? 0;
                $value = preg_replace('/[^0-9.]/', '', str_replace(',', '', $value));
                return floatval($value);
            });
            $name = $rows->first()['name'] ?? null;
            $forecastData[$cid] = [
                'cid' => $cid,
                'name' => $name,
                'amortization' => $totalDue,
            ];
        }

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

        // Group detail rows by CID and process with prioritization logic
        $detailByCid = [];
        foreach ($detailRows as $row) {
            $cid = strval(trim($row['cid'] ?? ''));
            $accountNo = strval(trim($row['account no'] ?? ''));

            // Clean principal release value (remove commas and format properly)
            $principalReleaseRaw = $row['principal release'] ?? 0;
            $principalRelease = 0;
            if (!empty($principalReleaseRaw)) {
                $principalRelease = preg_replace('/[^0-9.]/', '', str_replace(',', '', $principalReleaseRaw));
                $principalRelease = floatval($principalRelease);

                // Log the cleaning process for debugging
                Log::info("Principal Release cleaning for CID {$cid}:", [
                    'original' => $principalReleaseRaw,
                    'cleaned' => $principalRelease,
                    'account_no' => $accountNo
                ]);
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

        // 4. Merge and store in special_billings
        foreach ($forecastData as $cid => $data) {
            $detail = $detailByCid[$cid] ?? null;
            \App\Models\SpecialBilling::updateOrCreate(
                ['cid' => $cid],
                [
                    'employee_id' => 'N/A',
                    'name' => $data['name'],
                    'amortization' => $data['amortization'],
                    'start_date' => $detail['start_date'] ?? null,
                    'end_date' => $detail['end_date'] ?? null,
                    'gross' => $detail['gross'] ?? 0,
                    'office' => null,
                ]
            );
        }

        return redirect()->route('special-billing.index')->with('success', 'Special billing files imported and merged successfully with prioritization logic.');
    }

    public function export()
    {
        return Excel::download(new SpecialBillingExport, 'special_billing_export_' . now()->format('Y-m-d') . '.xlsx');
    }
}
