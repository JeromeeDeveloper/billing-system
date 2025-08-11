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
use App\Models\RemittanceBatch;
use App\Models\RemittanceUploadCount;
use App\Models\ExportStatus;

class RemittanceController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $billingPeriod = Auth::user()->billing_period;
        $perPage = 10;

        // Get all remittance preview data and group by member
        $allRemittanceData = \App\Models\RemittancePreview::where('user_id', $userId)
            ->where('type', 'admin')
            ->where('billing_period', $billingPeriod)
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->get();

        // Group by member for loans & savings
        $loansSavingsGrouped = [];
        foreach ($allRemittanceData as $remit) {
            if ($remit->remittance_type === 'loans_savings') {
                $memberId = $remit->member_id;
                if (!isset($loansSavingsGrouped[$memberId])) {
                    $loansSavingsGrouped[$memberId] = [
                        'member_id' => $memberId,
                        'name' => $remit->name,
                        'emp_id' => $remit->emp_id,
                        'loans' => 0,
                        'savings' => 0,
                        'status' => $remit->status,
                        'message' => $remit->message
                    ];
                }
                $loansSavingsGrouped[$memberId]['loans'] += $remit->loans ?? 0;
                $loansSavingsGrouped[$memberId]['savings'] += is_array($remit->savings) ? ($remit->savings['total'] ?? 0) : ($remit->savings ?? 0);
            }
        }

        // Group by member for shares
        $sharesGrouped = [];
        foreach ($allRemittanceData as $remit) {
            if ($remit->remittance_type === 'shares') {
                $memberId = $remit->member_id;
                if (!isset($sharesGrouped[$memberId])) {
                    $sharesGrouped[$memberId] = [
                        'member_id' => $memberId,
                        'name' => $remit->name,
                        'emp_id' => $remit->emp_id,
                        'share_amount' => 0,
                        'status' => $remit->status,
                        'message' => $remit->message
                    ];
                }
                $sharesGrouped[$memberId]['share_amount'] += $remit->share_amount ?? 0;
            }
        }

        // Apply filters to loans & savings
        $loansSavingsFiltered = collect($loansSavingsGrouped);
        $loansFilter = $request->get('loans_filter');
        if ($loansFilter === 'matched') {
            $loansSavingsFiltered = $loansSavingsFiltered->where('status', 'success');
        } elseif ($loansFilter === 'unmatched') {
            $loansSavingsFiltered = $loansSavingsFiltered->where('status', '!=', 'success');
        } elseif ($loansFilter === 'no_branch') {
            // Note: This filter would need member relationship to work properly
        }
        $loansSearch = $request->get('loans_search');
        if ($loansSearch) {
            $loansSavingsFiltered = $loansSavingsFiltered->filter(function($item) use ($loansSearch) {
                return stripos($item['name'], $loansSearch) !== false ||
                       stripos($item['emp_id'], $loansSearch) !== false;
            });
        }

        // Apply filters to shares
        $sharesFiltered = collect($sharesGrouped);
        $sharesFilter = $request->get('shares_filter');
        if ($sharesFilter === 'matched') {
            $sharesFiltered = $sharesFiltered->where('status', 'success');
        } elseif ($sharesFilter === 'unmatched') {
            $sharesFiltered = $sharesFiltered->where('status', '!=', 'success');
        } elseif ($sharesFilter === 'no_branch') {
            // Note: This filter would need member relationship to work properly
        }
        $sharesSearch = $request->get('shares_search');
        if ($sharesSearch) {
            $sharesFiltered = $sharesFiltered->filter(function($item) use ($sharesSearch) {
                return stripos($item['name'], $sharesSearch) !== false ||
                       stripos($item['emp_id'], $sharesSearch) !== false;
            });
        }

        // Create paginated collections
        $loansSavingsPreviewPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $loansSavingsFiltered->forPage($request->get('loans_page', 1), $perPage)->values(),
            $loansSavingsFiltered->count(),
            $perPage,
            $request->get('loans_page', 1),
            ['pageName' => 'loans_page', 'path' => $request->url(), 'query' => $request->query()]
        );

        $sharesPreviewPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $sharesFiltered->forPage($request->get('shares_page', 1), $perPage)->values(),
            $sharesFiltered->count(),
            $perPage,
            $request->get('shares_page', 1),
            ['pageName' => 'shares_page', 'path' => $request->url(), 'query' => $request->query()]
        );

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

                // --- Add logic for regular/special billing tables using RemittanceReport data ---
        $billingPeriod = Auth::user()->billing_period;

        // Get accumulated remittance report data for the current billing period
        $allRemittanceData = RemittanceReport::where('period', $billingPeriod)->get();

        // Get billing type information from RemittancePreview to determine which members belong to which billing type
        $userId = Auth::id();
        $billingTypeMap = RemittancePreview::where('user_id', $userId)
            ->where('type', 'admin')
            ->where('billing_period', $billingPeriod)
            ->where('remittance_type', 'loans_savings')
            ->get()
            ->groupBy('member_id')
            ->map(function ($group) {
                // Get the most recent billing type for each member
                return $group->sortByDesc('created_at')->first()->billing_type ?? 'regular';
            });

        // Separate members by billing type
        $regularMembers = [];
        $specialMembers = [];

        foreach ($allRemittanceData as $report) {
            // Skip members with no values
            if ($report->remitted_loans <= 0 && $report->remitted_savings <= 0 && $report->remitted_shares <= 0) {
                continue;
            }

            $memberId = $report->cid;
            $billingType = $billingTypeMap->get($memberId, 'regular');

            $memberData = [
                'member_id' => $memberId,
                'name' => $report->member_name,
                'loans_total' => $report->remitted_loans,
                'savings_total' => $report->remitted_savings,
                'shares_total' => $report->remitted_shares,
                'status' => 'success',
                'message' => 'Accumulated remittance data'
            ];

            if ($billingType === 'regular') {
                $regularMembers[$memberId] = $memberData;
            } else {
                $specialMembers[$memberId] = $memberData;
            }
        }

        // Convert to collections for the view
        $regularRemittances = collect($regularMembers)->map(function ($member) {
            return (object) [
                'member_id' => $member['member_id'],
                'member' => (object) ['full_name' => $member['name']],
                'remitted_amount' => $member['loans_total'],
                'remitted_savings' => $member['savings_total'],
                'remitted_shares' => $member['shares_total'],
                'billing_type' => 'regular',
                'status' => $member['status'],
                'message' => $member['message']
            ];
        });

        $specialRemittances = collect($specialMembers)->map(function ($member) {
            return (object) [
                'member_id' => $member['member_id'],
                'member' => (object) ['full_name' => $member['name']],
                'remitted_amount' => $member['loans_total'],
                'remitted_savings' => $member['savings_total'],
                'remitted_shares' => $member['shares_total'],
                'billing_type' => 'special',
                'status' => $member['status'],
                'message' => $member['message']
            ];
        });

        // For billed data, we'll use the separate member totals for each billing type
        $regularBilled = collect($regularMembers)->map(function ($member) use ($billingPeriod) {
            $billedTotal = \App\Models\LoanForecast::where('member_id', $member['member_id'])
                ->where('billing_period', $billingPeriod)
                ->get()
                ->filter(function($forecast) {
                    $productCode = null;
                    if ($forecast->loan_acct_no) {
                        $segments = explode('-', $forecast->loan_acct_no);
                        $productCode = $segments[2] ?? null;
                    }
                    $product = $productCode ? \App\Models\LoanProduct::where('product_code', $productCode)->first() : null;
                    return $product && $product->billing_type === 'regular';
                })
                ->sum('total_due');

            return (object) [
                'member_id' => $member['member_id'],
                'member' => (object) ['full_name' => $member['name']],
                'billed_total' => $billedTotal
            ];
        });

        $specialBilled = collect($specialMembers)->map(function ($member) use ($billingPeriod) {
            $billedTotal = \App\Models\LoanForecast::where('member_id', $member['member_id'])
                ->where('billing_period', $billingPeriod)
                ->get()
                ->filter(function($forecast) {
                    $productCode = null;
                    if ($forecast->loan_acct_no) {
                        $segments = explode('-', $forecast->loan_acct_no);
                        $productCode = $segments[2] ?? null;
                    }
                    $product = $productCode ? \App\Models\LoanProduct::where('product_code', $productCode)->first() : null;
                    return $product && $product->billing_type === 'special';
                })
                ->sum('total_due');

            return (object) [
                'member_id' => $member['member_id'],
                'member' => (object) ['full_name' => $member['name']],
                'billed_total' => $billedTotal
            ];
        });
        // --- End of new logic ---

        // Count regular and special remittance imports using new counting system
        $remittanceImportRegularCount = RemittanceUploadCount::getCount($billingPeriod, 'regular');
        $remittanceImportSpecialCount = RemittanceUploadCount::getCount($billingPeriod, 'special');
        // Count shares remittance imports
        $sharesRemittanceImportCount = RemittanceUploadCount::getCount($billingPeriod, 'shares');

        // Get export statuses for this billing period
        $exportStatuses = ExportStatus::getStatuses($billingPeriod, Auth::id());

        // === MONITORING DATA ===
        // Get latest remittance batches for this billing period
        $latestBatches = \App\Models\RemittanceBatch::where('billing_period', $billingPeriod)
            ->orderBy('imported_at', 'desc')
            ->get()
            ->groupBy('billing_type');

        // Get data counts for monitoring
        $monitoringData = [
            'loans_savings' => [
                'total_records' => RemittancePreview::where('user_id', Auth::id())
                    ->where('type', 'admin')
                    ->where('billing_period', $billingPeriod)
                    ->where('remittance_type', 'loans_savings')
                    ->count(),
                'matched_records' => RemittancePreview::where('user_id', Auth::id())
                    ->where('type', 'admin')
                    ->where('billing_period', $billingPeriod)
                    ->where('remittance_type', 'loans_savings')
                    ->where('status', 'success')
                    ->count(),
                'latest_batch' => $latestBatches->get('regular')?->first() ?? $latestBatches->get('special')?->first(),
                'available_types' => $latestBatches->keys()->filter(function($type) {
                    return in_array($type, ['regular', 'special']);
                })->values()
            ],
            'shares' => [
                'total_records' => RemittancePreview::where('user_id', Auth::id())
                    ->where('type', 'admin')
                    ->where('billing_period', $billingPeriod)
                    ->where('remittance_type', 'shares')
                    ->count(),
                'matched_records' => RemittancePreview::where('user_id', Auth::id())
                    ->where('type', 'admin')
                    ->where('billing_period', $billingPeriod)
                    ->where('remittance_type', 'shares')
                    ->where('status', 'success')
                    ->count(),
                'latest_batch' => $latestBatches->get('shares')?->first(),
                'available_types' => $latestBatches->keys()->filter(function($type) {
                    return in_array($type, ['shares']);
                })->values()
            ]
        ];

        // Calculate collection status
        $collectionStatus = [
            'loans_savings' => [
                'match_rate' => $monitoringData['loans_savings']['total_records'] > 0
                    ? round(($monitoringData['loans_savings']['matched_records'] / $monitoringData['loans_savings']['total_records']) * 100, 1)
                    : 0
            ],
            'shares' => [
                'match_rate' => $monitoringData['shares']['total_records'] > 0
                    ? round(($monitoringData['shares']['matched_records'] / $monitoringData['shares']['total_records']) * 100, 1)
                    : 0
            ]
        ];

        return view('components.admin.remittance.remittance', compact(
            'loansSavingsPreviewPaginated',
            'sharesPreviewPaginated',
            'comparisonReportPaginated',
            'regularRemittances',
            'specialRemittances',
            'regularBilled',
            'specialBilled',
            'remittanceImportRegularCount',
            'remittanceImportSpecialCount',
            'sharesRemittanceImportCount',
            'exportStatuses',
            'monitoringData',
            'collectionStatus'
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
                    'remittance_type' => $remittanceType2,
                    'billing_type' => $billingType
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

            // Increment the upload count for this billing type
            RemittanceUploadCount::incrementCount($currentBillingPeriod, $billingType);

            // Mark new upload for loans & savings exports
            ExportStatus::markUploaded($currentBillingPeriod, 'loans_savings', Auth::id());
            ExportStatus::markUploaded($currentBillingPeriod, 'loans_savings_with_product', Auth::id());

            // Enable exports for all users (including branch users) when admin uploads
            // Get all users and enable exports for them
            $allUsers = \App\Models\User::all();
            foreach ($allUsers as $user) {
                ExportStatus::markUploaded($currentBillingPeriod, 'loans_savings', $user->id);
                ExportStatus::markUploaded($currentBillingPeriod, 'loans_savings_with_product', $user->id);
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

            // Get current billing period
            $currentBillingPeriod = Auth::user()->billing_period;

            $import = new ShareRemittanceImport($currentBillingPeriod);
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();
            $stats = $import->getStats();

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

            // Increment the upload count for shares
            RemittanceUploadCount::incrementCount($currentBillingPeriod, 'shares');

            // Mark new upload for shares exports
            ExportStatus::markUploaded($currentBillingPeriod, 'shares', Auth::id());
            ExportStatus::markUploaded($currentBillingPeriod, 'shares_with_product', Auth::id());

            // Enable exports for all users (including branch users) when admin uploads shares
            // Get all users and enable exports for them
            $allUsers = \App\Models\User::all();
            foreach ($allUsers as $user) {
                ExportStatus::markUploaded($currentBillingPeriod, 'shares', $user->id);
                ExportStatus::markUploaded($currentBillingPeriod, 'shares_with_product', $user->id);
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

            // Get the latest RemittanceBatch for this billing period
            $latestBatch = RemittanceBatch::where('billing_period', $currentBillingPeriod)
                ->whereIn('billing_type', ['regular', 'special'])
                ->orderBy('imported_at', 'desc')
                ->first();

            if (!$latestBatch) {
                return redirect()->back()->with('error', 'No remittance batch found for the current billing period. Please upload a file first.');
            }

            // Get remittance data for the latest batch only, filtered by billing type
            $remittanceData = RemittancePreview::where('type', 'admin')
                ->where('billing_period', $currentBillingPeriod)
                ->where('remittance_type', 'loans_savings')
                ->where('created_at', '>=', $latestBatch->imported_at)
                ->where('billing_type', $latestBatch->billing_type)
                ->get();

            if ($remittanceData->isEmpty()) {
                return redirect()->back()->with('error', 'No remittance data to export for the latest upload. Please upload a file first.');
            }

            $type = $request->input('type', 'loans_savings');

            if ($type === 'shares') {
                // Check if export is enabled
                if (!ExportStatus::isEnabled($currentBillingPeriod, 'shares', Auth::id())) {
                    return redirect()->back()->with('error', 'Export is disabled. Please upload a new shares remittance file to enable export.');
                }

                // For shares, get the latest shares batch
                $latestSharesBatch = RemittanceBatch::where('billing_period', $currentBillingPeriod)
                    ->where('billing_type', 'shares')
                    ->orderBy('imported_at', 'desc')
                    ->first();

                if (!$latestSharesBatch) {
                    return redirect()->back()->with('error', 'No shares remittance batch found for the current billing period. Please upload a shares file first.');
                }

                $remittanceData = RemittancePreview::where('type', 'admin')
                    ->where('billing_period', $currentBillingPeriod)
                    ->where('remittance_type', 'shares')
                    ->where('created_at', '>=', $latestSharesBatch->imported_at)
                    ->get();

                if ($remittanceData->isEmpty()) {
                    return redirect()->back()->with('error', 'No shares remittance data to export for the latest upload. Please upload a shares file first.');
                }

                // Mark export as generated
                ExportStatus::markExported($currentBillingPeriod, 'shares', Auth::id());

                $export = new \App\Exports\SharesExport($remittanceData);
                $filename = 'shares_export_' . $currentBillingPeriod . '_' . now()->format('Y-m-d') . '.xlsx';
            } else if ($type === 'shares_with_product') {
                // Check if export is enabled
                if (!ExportStatus::isEnabled($currentBillingPeriod, 'shares_with_product', Auth::id())) {
                    return redirect()->back()->with('error', 'Export is disabled. Please upload a new shares remittance file to enable export.');
                }

                // For shares with product, get the latest shares batch
                $latestSharesBatch = RemittanceBatch::where('billing_period', $currentBillingPeriod)
                    ->where('billing_type', 'shares')
                    ->orderBy('imported_at', 'desc')
                    ->first();

                if (!$latestSharesBatch) {
                    return redirect()->back()->with('error', 'No shares remittance batch found for the current billing period. Please upload a shares file first.');
                }

                $remittanceData = RemittancePreview::where('type', 'admin')
                    ->where('billing_period', $currentBillingPeriod)
                    ->where('remittance_type', 'shares')
                    ->where('created_at', '>=', $latestSharesBatch->imported_at)
                    ->get();

                if ($remittanceData->isEmpty()) {
                    return redirect()->back()->with('error', 'No shares remittance data to export for the latest upload. Please upload a shares file first.');
                }

                // Mark export as generated
                ExportStatus::markExported($currentBillingPeriod, 'shares_with_product', Auth::id());

                $export = new \App\Exports\SharesWithProductExport($remittanceData);
                $filename = 'shares_with_product_export_' . $currentBillingPeriod . '_' . now()->format('Y-m-d') . '.xlsx';
            } else if ($type === 'loans_savings_with_product') {
                // Check if export is enabled
                if (!ExportStatus::isEnabled($currentBillingPeriod, 'loans_savings_with_product', Auth::id())) {
                    return redirect()->back()->with('error', 'Export is disabled. Please upload a new remittance file to enable export.');
                }

                // Mark export as generated
                ExportStatus::markExported($currentBillingPeriod, 'loans_savings_with_product', Auth::id());

                $export = new \App\Exports\LoansAndSavingsWithProductExport($remittanceData, $currentBillingPeriod);
                $filename = 'loans_and_savings_with_product_export_' . $currentBillingPeriod . '_' . now()->format('Y-m-d') . '.xlsx';
            } else {
                // Check if export is enabled
                if (!ExportStatus::isEnabled($currentBillingPeriod, 'loans_savings', Auth::id())) {
                    return redirect()->back()->with('error', 'Export is disabled. Please upload a new remittance file to enable export.');
                }

                // Mark export as generated
                ExportStatus::markExported($currentBillingPeriod, 'loans_savings', Auth::id());

                $export = new \App\Exports\LoansAndSavingsExport($remittanceData, $currentBillingPeriod);
                $filename = 'loans_and_savings_export_' . $currentBillingPeriod . '_' . now()->format('Y-m-d') . '.xlsx';
            }

            return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);

        } catch (\Exception $e) {
            Log::error('Error generating export: ' . $e->getMessage() . ' Stack: ' . $e->getTraceAsString());
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
        $userId = Auth::id();

        // Get accumulated remittance report data for the current billing period (same as table)
        $allRemittanceData = RemittanceReport::where('period', $billingPeriod)->get();

        // Get billing type information from RemittancePreview to determine which members belong to which billing type
        $billingTypeMap = RemittancePreview::where('user_id', $userId)
            ->where('type', 'admin')
            ->where('billing_period', $billingPeriod)
            ->where('remittance_type', 'loans_savings')
            ->get()
            ->groupBy('member_id')
            ->map(function ($group) {
                // Get the most recent billing type for each member
                return $group->sortByDesc('created_at')->first()->billing_type ?? 'regular';
            });

        // Separate members by billing type (same logic as table)
        $regularMembers = [];
        $specialMembers = [];

        foreach ($allRemittanceData as $report) {
            // Skip members with no values
            if ($report->remitted_loans <= 0 && $report->remitted_savings <= 0 && $report->remitted_shares <= 0) {
                continue;
            }

            $memberId = $report->cid;
            $billingType = $billingTypeMap->get($memberId, 'regular');

            $memberData = [
                'member_id' => $memberId,
                'name' => $report->member_name,
                'loans_total' => $report->remitted_loans,
                'savings_total' => $report->remitted_savings,
                'shares_total' => $report->remitted_shares,
                'status' => 'success',
                'message' => 'Accumulated remittance data'
            ];

            if ($billingType === 'regular') {
                $regularMembers[$memberId] = $memberData;
            } else {
                $specialMembers[$memberId] = $memberData;
            }
        }

        // Convert to collections for the export (same structure as billing tables)
        $regularRemittances = collect($regularMembers)->map(function ($member) {
            return (object) [
                'member_id' => $member['member_id'],
                'member' => (object) ['full_name' => $member['name']],
                'remitted_amount' => $member['loans_total'],
                'remitted_savings' => $member['savings_total'],
                'remitted_shares' => $member['shares_total'],
                'billing_type' => 'regular',
                'status' => $member['status'],
                'message' => $member['message']
            ];
        });

        $specialRemittances = collect($specialMembers)->map(function ($member) {
            return (object) [
                'member_id' => $member['member_id'],
                'member' => (object) ['full_name' => $member['name']],
                'remitted_amount' => $member['loans_total'],
                'remitted_savings' => $member['savings_total'],
                'remitted_shares' => $member['shares_total'],
                'billing_type' => 'special',
                'status' => $member['status'],
                'message' => $member['message']
            ];
        });

        // Get preview data for all uploads (same as table)
        $loansSavingsPreviewPaginated = RemittancePreview::where('user_id', $userId)
            ->where('type', 'admin')
            ->where('billing_period', $billingPeriod)
            ->where('remittance_type', 'loans_savings')
            ->get();

        $sharesPreviewPaginated = RemittancePreview::where('user_id', $userId)
            ->where('type', 'admin')
            ->where('billing_period', $billingPeriod)
            ->where('remittance_type', 'shares')
            ->get();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new RegularSpecialRemittanceExport($regularRemittances, $specialRemittances, $billingPeriod, $loansSavingsPreviewPaginated, $sharesPreviewPaginated, false, null),
            'Regular_Special_Billing_Remittance_' . $billingPeriod . '_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportConsolidated()
    {
        $billingPeriod = Auth::user()->billing_period;
        $userId = Auth::id();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ConsolidatedRemittanceReportExport($billingPeriod, $userId),
            'Consolidated_Remittance_Report_' . $billingPeriod . '_' . now()->format('Y-m-d') . '.xlsx'
        );
    }


}
