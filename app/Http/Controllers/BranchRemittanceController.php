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
use App\Exports\RegularSpecialRemittanceExport;
use App\Models\ExportStatus;

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

        // --- Add logic for regular/special billing tables for branch ---
        $loanRemittances = \App\Models\LoanRemittance::with('loanForecast', 'member')
            ->where('billing_period', $currentBillingPeriod)
            ->whereHas('member', function($q) use ($branch_id) {
                $q->where('branch_id', $branch_id);
            })
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
        $billings = \App\Models\Billing::with('loanForecast')
            ->where('start', 'like', $currentBillingPeriod . '%')
            ->whereHas('member', function($q) use ($branch_id) {
                $q->where('branch_id', $branch_id);
            })
            ->get();
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

        // Get export statuses for this billing period
        $exportStatuses = ExportStatus::getStatuses($currentBillingPeriod);

        return view('components.branch.remittance.remittance', compact(
            'loansSavingsPreviewPaginated',
            'sharesPreviewPaginated',
            'comparisonReportPaginated',
            'regularRemittances',
            'specialRemittances',
            'regularBilled',
            'specialBilled',
            'exportStatuses'
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

            // Get the latest RemittanceBatch for this billing period
            $latestBatch = \App\Models\RemittanceBatch::where('billing_period', $currentBillingPeriod)
                ->whereIn('billing_type', ['regular', 'special'])
                ->orderBy('imported_at', 'desc')
                ->first();

            if (!$latestBatch) {
                return redirect()->back()->with('error', 'No remittance batch found for the current billing period. Please upload a file first.');
            }

            // Get remittance data for the latest batch only (branch members)
            $remittanceData = RemittancePreview::whereHas('member', function($query) use ($branch_id) {
                $query->where('branch_id', $branch_id);
            })
            ->where('billing_period', $currentBillingPeriod)
            ->where('remittance_type', 'loans_savings')
            ->where('created_at', '>=', $latestBatch->imported_at)
            ->get();

            if ($remittanceData->isEmpty()) {
                return redirect()->back()->with('error', 'No remittance data found for your branch members in the current billing period.');
            }

            Log::info('Found ' . $remittanceData->count() . ' records for branch ' . $branch_id . ' in billing period ' . $currentBillingPeriod);

            if ($type === 'shares') {
                // Check if export is enabled
                if (!ExportStatus::isEnabled($currentBillingPeriod, 'shares')) {
                    return redirect()->back()->with('error', 'Export is disabled. Please upload a new shares remittance file to enable export.');
                }

                // For shares, get the latest shares batch
                $latestSharesBatch = \App\Models\RemittanceBatch::where('billing_period', $currentBillingPeriod)
                    ->where('billing_type', 'shares')
                    ->orderBy('imported_at', 'desc')
                    ->first();

                if (!$latestSharesBatch) {
                    return redirect()->back()->with('error', 'No shares remittance batch found for the current billing period. Please upload a shares file first.');
                }

                // Get shares remittance data for the latest batch only (branch members)
                $remittanceData = RemittancePreview::whereHas('member', function($query) use ($branch_id) {
                    $query->where('branch_id', $branch_id);
                })
                ->where('billing_period', $currentBillingPeriod)
                ->where('remittance_type', 'shares')
                ->where('created_at', '>=', $latestSharesBatch->imported_at)
                ->get();

                if ($remittanceData->isEmpty()) {
                    return redirect()->back()->with('error', 'No shares remittance data to export for the latest upload. Please upload a shares file first.');
                }

                // Mark export as generated
                ExportStatus::markExported($currentBillingPeriod, 'shares');

                $export = new \App\Exports\BranchSharesExport($remittanceData, $branch_id);
                $filename = 'branch_shares_export_' . $currentBillingPeriod . '_' . now()->format('Y-m-d') . '.csv';
            } else if ($type === 'shares_with_product') {
                // Check if export is enabled
                if (!ExportStatus::isEnabled($currentBillingPeriod, 'shares_with_product')) {
                    return redirect()->back()->with('error', 'Export is disabled. Please upload a new shares remittance file to enable export.');
                }

                // For shares with product, get the latest shares batch
                $latestSharesBatch = \App\Models\RemittanceBatch::where('billing_period', $currentBillingPeriod)
                    ->where('billing_type', 'shares')
                    ->orderBy('imported_at', 'desc')
                    ->first();

                if (!$latestSharesBatch) {
                    return redirect()->back()->with('error', 'No shares remittance batch found for the current billing period. Please upload a shares file first.');
                }

                // Get shares remittance data for the latest batch only (branch members)
                $remittanceData = RemittancePreview::whereHas('member', function($query) use ($branch_id) {
                    $query->where('branch_id', $branch_id);
                })
                ->where('billing_period', $currentBillingPeriod)
                ->where('remittance_type', 'shares')
                ->where('created_at', '>=', $latestSharesBatch->imported_at)
                ->get();

                if ($remittanceData->isEmpty()) {
                    return redirect()->back()->with('error', 'No shares remittance data to export for the latest upload. Please upload a shares file first.');
                }

                // Mark export as generated
                ExportStatus::markExported($currentBillingPeriod, 'shares_with_product');

                $export = new \App\Exports\BranchSharesWithProductExport($remittanceData, $branch_id);
                $filename = 'branch_shares_with_product_export_' . $currentBillingPeriod . '_' . now()->format('Y-m-d') . '.csv';
            } else if ($type === 'loans_savings_with_product') {
                // Check if export is enabled
                if (!ExportStatus::isEnabled($currentBillingPeriod, 'loans_savings_with_product')) {
                    return redirect()->back()->with('error', 'Export is disabled. Please upload a new remittance file to enable export.');
                }

                // Mark export as generated
                ExportStatus::markExported($currentBillingPeriod, 'loans_savings_with_product');

                $export = new \App\Exports\BranchLoansAndSavingsWithProductExport($remittanceData, $branch_id);
                $filename = 'branch_loans_and_savings_with_product_export_' . $currentBillingPeriod . '_' . now()->format('Y-m-d') . '.csv';
            } else {
                // Check if export is enabled
                if (!ExportStatus::isEnabled($currentBillingPeriod, 'loans_savings')) {
                    return redirect()->back()->with('error', 'Export is disabled. Please upload a new remittance file to enable export.');
                }

                // Mark export as generated
                ExportStatus::markExported($currentBillingPeriod, 'loans_savings');

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

    public function exportRegularSpecial()
    {
        $branch_id = Auth::user()->branch_id;
        $currentBillingPeriod = Auth::user()->billing_period;
        $loanRemittances = \App\Models\LoanRemittance::with('loanForecast', 'member')
            ->where('billing_period', $currentBillingPeriod)
            ->whereHas('member', function($q) use ($branch_id) {
                $q->where('branch_id', $branch_id);
            })
            ->get();
        $loanRemittances = $loanRemittances->map(function ($remit) {
            $forecast = $remit->loanForecast;
            $productCode = null;
            if ($forecast && $forecast->loan_acct_no) {
                $segments = explode('-', $forecast->loan_acct_no);
                $productCode = $segments[2] ?? null;
            }
            $remit->product_code = $productCode;
            $remit->billing_type = null;
            if ($productCode) {
                $loanProduct = \App\Models\LoanProduct::where('product_code', $productCode)->first();
                $remit->billing_type = $loanProduct ? $loanProduct->billing_type : null;
            }
            return $remit;
        });
        $regularRemittances = $loanRemittances->where('billing_type', 'regular')->values();
        $specialRemittances = $loanRemittances->where('billing_type', 'special')->values();
        // Calculate billed totals for each member (sum of total_due from LoanForecast for the billing type)
        $memberIds = $loanRemittances->pluck('member_id')->unique();
        $regularBilled = collect();
        $specialBilled = collect();
        foreach ($memberIds as $memberId) {
            $forecasts = \App\Models\LoanForecast::where('member_id', $memberId)
                ->where('billing_period', $currentBillingPeriod)
                ->get();
            $regularTotal = $forecasts->filter(function($forecast) {
                $productCode = null;
                if ($forecast->loan_acct_no) {
                    $segments = explode('-', $forecast->loan_acct_no);
                    $productCode = $segments[2] ?? null;
                }
                if (!$productCode) return false;
                $loanProduct = \App\Models\LoanProduct::where('product_code', $productCode)->first();
                return $loanProduct && $loanProduct->billing_type === 'regular';
            })->sum('total_due');
            $specialTotal = $forecasts->filter(function($forecast) {
                $productCode = null;
                if ($forecast->loan_acct_no) {
                    $segments = explode('-', $forecast->loan_acct_no);
                    $productCode = $segments[2] ?? null;
                }
                if (!$productCode) return false;
                $loanProduct = \App\Models\LoanProduct::where('product_code', $productCode)->first();
                return $loanProduct && $loanProduct->billing_type === 'special';
            })->sum('total_due');
            $regularBilled->push(['member_id' => $memberId, 'total_billed' => $regularTotal]);
            $specialBilled->push(['member_id' => $memberId, 'total_billed' => $specialTotal]);
        }
        return \Maatwebsite\Excel\Facades\Excel::download(
            new RegularSpecialRemittanceExport($regularRemittances, $specialRemittances, $currentBillingPeriod, $regularBilled, $specialBilled),
            'Branch-Regular-Special-Billing-Remittance.xlsx'
        );
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
}
