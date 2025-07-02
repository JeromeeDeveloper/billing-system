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
        // Get the branch_id from the authenticated user
        $branch_id = Auth::user()->branch_id;
        $currentBillingPeriod = Auth::user()->billing_period;

        // Get all preview data and filter by branch members and current billing period
        $previewCollection = RemittancePreview::whereHas('member', function($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })
        ->where('billing_period', $currentBillingPeriod)
        ->get();

        // Calculate stats for branch members only
        $stats = [
            'matched' => $previewCollection->where('status', 'success')->count(),
            'unmatched' => $previewCollection->where('status', '!=', 'success')->count(),
            'total_amount' => $previewCollection->sum(function ($record) {
                $savingsTotal = 0;
                if (is_array($record->savings) && isset($record->savings['total'])) {
                    $savingsTotal = $record->savings['total'];
                } elseif (is_array($record->savings)) {
                    $savingsTotal = collect($record->savings)->sum();
                }
                return $record->loans + $savingsTotal;
            })
        ];

        // Get unique dates for the dropdown (only for this branch)
        $dates = Remittance::whereHas('member', function($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })
        ->select(DB::raw('DATE(created_at) as date'))
        ->distinct()
        ->orderBy('date', 'desc')
        ->get()
        ->map(function($item) {
            return [
                'date' => $item->date,
                'formatted' => Carbon::parse($item->date)->format('M d, Y')
            ];
        });

        // Filter preview data if filter is set
        if ($previewCollection->isNotEmpty()) {
            $filter = $request->get('filter');
            if ($filter === 'matched') {
                $previewCollection = $previewCollection->filter(function($record) {
                    return $record->status === 'success';
                });
            } elseif ($filter === 'unmatched') {
                $previewCollection = $previewCollection->filter(function($record) {
                    return $record->status !== 'success';
                });
            }

            // Paginate the filtered collection
            $perPage = 10;
            $currentPage = $request->get('page', 1);
            $pagedData = $previewCollection->forPage($currentPage, $perPage);

            $preview = new \Illuminate\Pagination\LengthAwarePaginator(
                $pagedData,
                $previewCollection->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $preview = null;
        }

        return view('components.branch.remittance.remittance', compact('dates', 'preview', 'stats'));
    }

    public function generateExport(Request $request)
    {
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
}
