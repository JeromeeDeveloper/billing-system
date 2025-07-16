<?php

namespace App\Http\Controllers;

use App\Exports\BranchRemittanceExport;
use App\Models\Remittance;
use App\Models\Savings;
use App\Models\Member;
use App\Models\LoanForecast;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\RemittancePreview;

class BranchRemittanceController extends Controller
{
    public function index(Request $request)
    {
        $branch_id = Auth::user()->branch_id;
        $currentBillingPeriod = Auth::user()->billing_period;
        $perPage = 10;

        // Loans & Savings Preview (branch)
        $loansQuery = RemittancePreview::whereHas('member', function($query) use ($branch_id) {
                $query->where('branch_id', $branch_id);
            })
            ->where('billing_period', $currentBillingPeriod)
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

        // Shares Preview (branch)
        $sharesQuery = RemittancePreview::whereHas('member', function($query) use ($branch_id) {
                $query->where('branch_id', $branch_id);
            })
            ->where('billing_period', $currentBillingPeriod)
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

        // Comparison Report (branch)
        $comparisonReport = $this->getRemittanceComparisonReport($branch_id, $currentBillingPeriod);
        $comparisonPage = $request->get('comparison_page', 1);
        $comparisonReportPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($comparisonReport)->forPage($comparisonPage, $perPage)->values(),
            count($comparisonReport),
            $perPage,
            $comparisonPage,
            ['pageName' => 'comparison_page', 'path' => $request->url(), 'query' => $request->query()]
        );

        return view('components.branch.remittance.remittance', compact(
            'loansSavingsPreviewPaginated',
            'sharesPreviewPaginated',
            'comparisonReportPaginated'
        ));
    }

    public function generateExport(Request $request)
    {
        set_time_limit(600); // Allow up to 5 minutes for export

        try {
            // Get branch_id from authenticated user
            $branch_id = Auth::user()->branch_id;
            $currentBillingPeriod = Auth::user()->billing_period;
            $type = $request->get('type', 'loans_savings'); // Default to loans_savings

            Log::info('Branch Export Request - Branch ID: ' . $branch_id . ', Type: ' . $type . ', Billing Period: ' . $currentBillingPeriod);

            // Get all remittance data for branch members and current billing period
            $remittanceData = RemittancePreview::whereHas('member', function($query) use ($branch_id) {
                $query->where('branch_id', $branch_id);
            })
            ->where('billing_period', $currentBillingPeriod)
            ->get();

            if ($remittanceData->isEmpty()) {
                return redirect()->back()->with('error', 'No remittance data found for your branch members in the current billing period.');
            }

            Log::info('Found ' . $remittanceData->count() . ' records for branch ' . $branch_id . ' in billing period ' . $currentBillingPeriod);

            if ($type === 'shares') {
                $export = new \App\Exports\BranchSharesExport($remittanceData, $branch_id);
                $filename = 'branch_shares_export_' . $currentBillingPeriod . '_' . now()->format('Y-m-d') . '.csv';
            } else if ($type === 'shares_with_product') {
                $export = new \App\Exports\BranchSharesWithProductExport($remittanceData, $branch_id);
                $filename = 'branch_shares_with_product_export_' . $currentBillingPeriod . '_' . now()->format('Y-m-d') . '.csv';
            } else if ($type === 'loans_savings_with_product') {
                $export = new \App\Exports\BranchLoansAndSavingsWithProductExport($remittanceData, $branch_id);
                $filename = 'branch_loans_and_savings_with_product_export_' . $currentBillingPeriod . '_' . now()->format('Y-m-d') . '.csv';
            } else {
                $export = new \App\Exports\BranchLoansAndSavingsExport($remittanceData, $branch_id);
                $filename = 'branch_loans_and_savings_export_' . $currentBillingPeriod . '_' . now()->format('Y-m-d') . '.csv';
            }

            return Excel::download($export, $filename);

        } catch (\Exception $e) {
            Log::error('Branch Export Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Error generating export: ' . $e->getMessage());
        }
    }

    // Add a branch-specific comparison report method
    private function getRemittanceComparisonReport($branch_id, $period)
    {
        // Get all forecasts for the period and branch
        $forecasts = \App\Models\LoanForecast::where('billing_period', $period)
            ->whereHas('member', function($q) use ($branch_id) {
                $q->where('branch_id', $branch_id);
            })
            ->with('member')
            ->get();
        $remitted = \App\Models\RemittanceReport::where('period', $period)
            ->whereHas('member', function($q) use ($branch_id) {
                $q->where('branch_id', $branch_id);
            })
            ->get()->keyBy('cid');

        $report = [];
        $memberTotals = [];
        foreach ($forecasts as $forecast) {
            $cid = $forecast->member->cid ?? null;
            if (!$cid) continue;
            if (!isset($memberTotals[$cid])) {
                $memberTotals[$cid] = [
                    'cid' => $cid,
                    'member_name' => trim(($forecast->member->fname ?? '') . ' ' . ($forecast->member->lname ?? '')),
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
            $row['remaining_loan_balance'] = ($row['total_billed'] ?? 0) - ($row['remitted_loans'] ?? 0);
        }
        unset($row);
        return array_values($memberTotals);
    }
}
