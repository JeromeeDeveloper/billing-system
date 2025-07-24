<?php

namespace App\Http\Controllers;

use App\Imports\RemittanceImport;
use App\Exports\RemittanceExport;
use App\Models\Remittance;
use App\Models\Savings;
use App\Models\Member;
use App\Models\LoanForecast;
use App\Models\RemittancePreview;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Imports\ShareRemittanceImport;
use App\Models\RemittanceReport;
use Illuminate\Support\Facades\Log;
use App\Exports\RegularSpecialRemittanceExport;

class RemittanceController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $billingPeriod = Auth::user()->billing_period;
        $perPage = 10;

        // Loans & Savings
        $loansQuery = \App\Models\RemittancePreview::where('user_id', $userId)
            ->where('type', 'admin')
            ->where('billing_period', $billingPeriod)
            ->where('remittance_type', 'loans_savings')
            ->whereNotNull('name')
            ->where('name', '!=', '');

        $loansFilter = $request->get('loans_filter');
        if ($loansFilter === 'matched') {
            $loansQuery->where('status', 'success');
        } elseif ($loansFilter === 'unmatched') {
            $loansQuery->where('status', '!=', 'success');
        } elseif ($loansFilter === 'no_branch') {
            $loansQuery->whereHas('member', function($q) {
                $q->whereNull('branch_id');
            });
        }
        $loansSearch = $request->get('loans_search');
        if ($loansSearch) {
            $loansQuery->where(function($q) use ($loansSearch) {
                $q->where('name', 'like', "%$loansSearch%")
                  ->orWhere('emp_id', 'like', "%$loansSearch%") ;
            });
        }
        $loansSavingsPreviewPaginated = $loansQuery->orderBy('id', 'desc')->paginate($perPage, ['*'], 'loans_page');

        // Shares
        $sharesQuery = \App\Models\RemittancePreview::where('user_id', $userId)
            ->where('type', 'admin')
            ->where('billing_period', $billingPeriod)
            ->where('remittance_type', 'shares')
            ->whereNotNull('name')
            ->where('name', '!=', '');

        $sharesFilter = $request->get('shares_filter');
        if ($sharesFilter === 'matched') {
            $sharesQuery->where('status', 'success');
        } elseif ($sharesFilter === 'unmatched') {
            $sharesQuery->where('status', '!=', 'success');
        } elseif ($sharesFilter === 'no_branch') {
            $sharesQuery->whereHas('member', function($q) {
                $q->whereNull('branch_id');
            });
        }
        $sharesSearch = $request->get('shares_search');
        if ($sharesSearch) {
            $sharesQuery->where(function($q) use ($sharesSearch) {
                $q->where('name', 'like', "%$sharesSearch%")
                  ->orWhere('emp_id', 'like', "%$sharesSearch%") ;
            });
        }
        $sharesPreviewPaginated = $sharesQuery->orderBy('id', 'desc')->paginate($perPage, ['*'], 'shares_page');

        // Comparison report (unchanged)
        $comparisonReport = $this->getRemittanceComparisonReport();
        $comparisonPage = $request->get('comparison_page', 1);
        $comparisonReportPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($comparisonReport)->forPage($comparisonPage, $perPage)->values(),
            count($comparisonReport),
            $perPage,
            $comparisonPage,
            ['pageName' => 'comparison_page', 'path' => $request->url(), 'query' => $request->query()]
        );

        // --- Add logic for regular/special billing tables ---
        $billingPeriod = Auth::user()->billing_period;
        $loanRemittances = \App\Models\LoanRemittance::with('loanForecast', 'member')
            ->where('billing_period', $billingPeriod)
            ->get();
        $loanRemittances = $loanRemittances->map(function ($remit) {
            $forecast = $remit->loanForecast;
            $productCode = null;
            if ($forecast && $forecast->loan_acct_no) {
                $segments = explode('-', $forecast->loan_acct_no);
                $productCode = $segments[2] ?? null;
            }
            $product = $productCode ? \App\Models\LoanProduct::where('product_code', $productCode)->first() : null;
            $remit->billing_type = $product ? $product->billing_type : 'regular';
            $remit->remitted_savings = 0; // Placeholder, add logic if needed
            $remit->remitted_shares = 0;  // Placeholder, add logic if needed
            return $remit;
        });
        $regularRemittances = $loanRemittances->where('billing_type', 'regular');
        $specialRemittances = $loanRemittances->where('billing_type', 'special');
        $billings = \App\Models\Billing::with('loanForecast')->where('start', 'like', $billingPeriod . '%')->get();
        $billings = $billings->map(function ($bill) {
            $forecast = $bill->loanForecast;
            $productCode = null;
            if ($forecast && $forecast->loan_acct_no) {
                $segments = explode('-', $forecast->loan_acct_no);
                $productCode = $segments[2] ?? null;
            }
            $product = $productCode ? \App\Models\LoanProduct::where('product_code', $productCode)->first() : null;
            $bill->billing_type = $product ? $product->billing_type : 'regular';
            return $bill;
        });
        $regularBilled = $billings->where('billing_type', 'regular');
        $specialBilled = $billings->where('billing_type', 'special');
        // --- End of new logic ---

        return view('components.admin.remittance.remittance', compact(
            'loansSavingsPreviewPaginated',
            'sharesPreviewPaginated',
            'comparisonReportPaginated',
            'regularRemittances',
            'specialRemittances',
            'regularBilled',
            'specialBilled'
        ));
    }

    public function upload(Request $request)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 2000);
        $request->validate([
            'file' => 'required|file|max:10240', // max 10MB
        ]);

        $remittanceType2 = 'loans_savings';
        $billingType = $request->input('billing_type', 'regular');

        try {
            DB::beginTransaction();

            // Get current billing period
            $currentBillingPeriod = Auth::user()->billing_period;

            $import = new RemittanceImport($currentBillingPeriod, $billingType);
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();

            // Clear previous preview data for this user, billing period, and remittance type
            RemittancePreview::where('user_id', Auth::id())
                ->where('type', 'admin')
                ->where('billing_period', $currentBillingPeriod)
                ->where('remittance_type', $remittanceType2)
                ->delete();

            // Store new preview data and accumulate remitted values
            $hasUnmatched = false;
            foreach ($results as $result) {
                RemittancePreview::create([
                    'user_id' => Auth::id(),
                    'emp_id' => $result['cid'],
                    'name' => $result['name'],
                    'member_id' => $result['member_id'],
                    'loans' => $result['loans'],
                    'savings' => [
                        'total' => $result['savings_total'] ?? 0,
                        'distribution' => $result['savings_distribution'] ?? []
                    ],
                    'share_amount' => 0,
                    'status' => $result['status'],
                    'message' => $result['message'],
                    'type' => 'admin',
                    'billing_period' => $currentBillingPeriod,
                    'remittance_type' => $remittanceType2
                ]);
                if ($result['status'] !== 'success') {
                    $hasUnmatched = true;
                }
                // Accumulate remitted values in remittance_reports
                $report = RemittanceReport::firstOrNew([
                    'cid' => $result['cid'],
                    'period' => $currentBillingPeriod,
                ]);
                $report->member_name = $result['name'];
                $report->remitted_loans += $result['loans'];
                $report->remitted_savings += $result['savings_total'] ?? 0;
                $report->save();
            }
            if ($hasUnmatched) {
                DB::rollBack();
                return redirect()->route('remittance.index')->with('error', 'Import failed: There are unmatched CIDs in your file. Please review the preview and correct unmatched entries before importing.');
            }

            DB::commit();

            return redirect()->route('remittance.index')
                ->with('success', 'File processed successfully. Check the preview below.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error processing file: ' . $e->getMessage());
        }
    }

    public function uploadShare(Request $request)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 2000);
        $request->validate([
            'file' => 'required|file|max:10240', // max 10MB
        ]);

        $remittanceType = 'shares';

        try {
            DB::beginTransaction();

            $import = new ShareRemittanceImport();
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();
            $stats = $import->getStats();

            // Get current billing period
            $currentBillingPeriod = Auth::user()->billing_period;

            // Clear previous preview data for this user, billing period, and remittance type
            RemittancePreview::where('user_id', Auth::id())
                ->where('type', 'admin')
                ->where('billing_period', $currentBillingPeriod)
                ->where('remittance_type', $remittanceType)
                ->delete();

            // Store new preview data and accumulate remitted shares
            foreach ($results as $result) {
                RemittancePreview::create([
                    'user_id' => Auth::id(),
                    'emp_id' => $result['cid'],
                    'name' => $result['name'],
                    'member_id' => $result['member_id'],
                    'loans' => 0,
                    'savings' => [],
                    'share_amount' => $result['share'],
                    'status' => $result['status'],
                    'message' => $result['message'],
                    'type' => 'admin',
                    'billing_period' => $currentBillingPeriod,
                    'remittance_type' => $remittanceType
                ]);

                // Accumulate remitted shares in remittance_reports
                $report = RemittanceReport::firstOrNew([
                    'cid' => $result['cid'],
                    'period' => $currentBillingPeriod,
                ]);
                $report->member_name = $result['name'];
                $report->remitted_shares += $result['share'];
                $report->save();
            }

            DB::commit();

            return redirect()->route('remittance.index')
                ->with('success', 'Share remittance file processed successfully. Check the preview below.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error processing share remittance file: ' . $e->getMessage());
        }
    }

    public function generateExport(Request $request)
    {
        set_time_limit(600); // Allow up to 5 minutes for export

        try {
            // Get current billing period
            $currentBillingPeriod = Auth::user()->billing_period;

            $remittanceData = RemittancePreview::where('user_id', Auth::id())
                ->where('type', 'admin')
                ->where('billing_period', $currentBillingPeriod)
                ->get();

            if ($remittanceData->isEmpty()) {
                return redirect()->back()->with('error', 'No remittance data to export for the current billing period. Please upload a file first.');
            }

            $type = $request->input('type', 'loans_savings');

            if ($type === 'shares') {
                $export = new \App\Exports\SharesExport($remittanceData);
                $filename = 'shares_export_' . $currentBillingPeriod . '_' . now()->format('Y-m-d') . '.xlsx';
            } else if ($type === 'shares_with_product') {
                $export = new \App\Exports\SharesWithProductExport($remittanceData);
                $filename = 'shares_with_product_export_' . $currentBillingPeriod . '_' . now()->format('Y-m-d') . '.xlsx';
            } else if ($type === 'loans_savings_with_product') {
                $export = new \App\Exports\LoansAndSavingsWithProductExport($remittanceData);
                $filename = 'loans_and_savings_with_product_export_' . $currentBillingPeriod . '_' . now()->format('Y-m-d') . '.xlsx';
            } else {
                $export = new \App\Exports\LoansAndSavingsExport($remittanceData);
                $filename = 'loans_and_savings_export_' . $currentBillingPeriod . '_' . now()->format('Y-m-d') . '.xlsx';
            }

            return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);

        } catch (\Exception $e) {
            \Log::error('Error generating export: ' . $e->getMessage() . ' Stack: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Error generating export: ' . $e->getMessage());
        }
    }

    public function export($filename)
    {
        return response()->download(storage_path('app/public/' . $filename));
    }

    protected function updateLoanBalances($member, $loanAmount)
    {
        $remainingAmount = $loanAmount;

        // Get active loans ordered by forecast amount
        $forecasts = LoanForecast::where('member_id', $member->id)
            ->orderBy('total_due', 'desc')
            ->get();

        foreach ($forecasts as $forecast) {
            if ($remainingAmount <= 0) break;

            $deductionAmount = min($remainingAmount, $forecast->total_due);

            // Update loan balance
            $member->loan_balance = $member->loan_balance - $deductionAmount;
            $member->save();

            $remainingAmount -= $deductionAmount;
        }

        return $loanAmount - $remainingAmount; // Return amount actually deducted
    }

    public function getRemittanceComparisonReport($period = null)
    {
        $period = $period ?: Auth::user()->billing_period;
        // Get all forecasts for the period
        $forecasts = \App\Models\LoanForecast::where('billing_period', $period)
            ->with('member')
            ->get();
        $remitted = \App\Models\RemittanceReport::where('period', $period)->get()->keyBy('cid');

        $report = [];
        $memberTotals = [];
        foreach ($forecasts as $forecast) {
            $cid = $forecast->member->cid ?? null;
            if (!$cid) continue;
            if (!isset($memberTotals[$cid])) {
                $memberTotals[$cid] = [
                    'cid' => $cid,
                    'member_name' => trim(($forecast->member->fname ?? '') . ' ' . ($forecast->member->lname ?? '')),
                    'loan_balance' => $forecast->member->loan_balance ?? 0,
                    'amortization' => 0,
                    'total_billed' => 0,
                    'remaining_loan_balance' => 0,
                    'remitted_loans' => 0,
                    'remitted_savings' => 0,
                    'remitted_shares' => 0,
                ];
            }
            $memberTotals[$cid]['amortization'] += $forecast->original_total_due ?? $forecast->total_due;
            $memberTotals[$cid]['total_billed'] += $forecast->total_due;
        }
        foreach ($memberTotals as $cid => &$row) {
            $remit = $remitted[$cid] ?? null;
            $row['remitted_loans'] = $remit->remitted_loans ?? 0;
            $row['remitted_savings'] = $remit->remitted_savings ?? 0;
            $row['remitted_shares'] = $remit->remitted_shares ?? 0;
            $row['remaining_loan_balance'] = ($row['loan_balance'] ?? 0) - ($row['remitted_loans'] ?? 0);
        }
        unset($row);
        return array_values($memberTotals);
    }

    public function exportPreview()
    {
        $currentBillingPeriod = Auth::user()->billing_period;
        $preview = \App\Models\RemittancePreview::where('user_id', Auth::id())
            ->where('type', 'admin')
            ->where('billing_period', $currentBillingPeriod)
            ->get();
        return Excel::download(new \App\Exports\RemittanceExport($preview), 'remittance_preview_' . $currentBillingPeriod . '.xlsx');
    }

    public function exportComparison()
    {
        $currentBillingPeriod = Auth::user()->billing_period;
        $report = $this->getRemittanceComparisonReport($currentBillingPeriod);
        return Excel::download(new \App\Exports\ComparisonReportExport($report), 'billed_vs_remitted_comparison_' . $currentBillingPeriod . '.xlsx');
    }

    public function exportRegularSpecial()
    {
        $billingPeriod = Auth::user()->billing_period;
        $loanRemittances = \App\Models\LoanRemittance::with('loanForecast', 'member')
            ->where('billing_period', $billingPeriod)
            ->get();
        $loanRemittances = $loanRemittances->map(function ($remit) {
            $forecast = $remit->loanForecast;
            $productCode = null;
            if ($forecast && $forecast->loan_acct_no) {
                $segments = explode('-', $forecast->loan_acct_no);
                $productCode = $segments[2] ?? null;
            }
            $product = $productCode ? \App\Models\LoanProduct::where('product_code', $productCode)->first() : null;
            $remit->billing_type = $product ? $product->billing_type : 'regular';
            $remit->remitted_savings = 0; // Placeholder, add logic if needed
            $remit->remitted_shares = 0;  // Placeholder, add logic if needed
            return $remit;
        });
        $regularRemittances = $loanRemittances->where('billing_type', 'regular');
        $specialRemittances = $loanRemittances->where('billing_type', 'special');
        return \Maatwebsite\Excel\Facades\Excel::download(
            new RegularSpecialRemittanceExport($regularRemittances, $specialRemittances, $billingPeriod),
            'Regular_Special_Billing_Remittance_' . $billingPeriod . '_' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
