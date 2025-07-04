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
use Illuminate\Support\Facades\Log;

class RemittanceController extends Controller
{
    public function index(Request $request)
    {
        // Get current billing period
        $currentBillingPeriod = Auth::user()->billing_period;

        // Get preview data from database for current user and current billing period
        $previewCollection = RemittancePreview::where('user_id', Auth::id())
            ->where('type', 'admin')
            ->where('billing_period', $currentBillingPeriod)
            ->get();

        // Calculate stats
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

        // Get unique dates for the dropdown
        $dates = Remittance::select(DB::raw('DATE(created_at) as date'))
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
            } elseif ($filter === 'no_branch') {
                $previewCollection = $previewCollection->filter(function($record) {
                    if (!$record->member_id) return false;
                    $member = \App\Models\Member::find($record->member_id);
                    return $member && is_null($member->branch_id);
                });
            }

            // Search filter (by CID or name)
            $search = $request->get('search');
            if ($search) {
                $search = strtolower($search);
                $previewCollection = $previewCollection->filter(function($record) use ($search) {
                    $cid = strtolower($record->emp_id ?? '');
                    $name = strtolower($record->name ?? '');
                    return strpos($cid, $search) !== false || strpos($name, $search) !== false;
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

        return view('components.admin.remittance.remittance', compact('dates', 'preview', 'stats'));
    }

    public function upload(Request $request)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 2000);
        $request->validate([
            'file' => 'required|file|max:10240', // max 10MB
        ]);

        try {
            DB::beginTransaction();

            $import = new RemittanceImport();
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();

            // Get current billing period
            $currentBillingPeriod = Auth::user()->billing_period;

            // Clear previous preview data for this user and billing period
            RemittancePreview::where('user_id', Auth::id())
                ->where('type', 'admin')
                ->where('billing_period', $currentBillingPeriod)
                ->delete();

            // Store new preview data
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
                    'billing_period' => $currentBillingPeriod
                ]);
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

        try {
            DB::beginTransaction();

            $import = new ShareRemittanceImport();
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();
            $stats = $import->getStats();

            // Get current billing period
            $currentBillingPeriod = Auth::user()->billing_period;

            // Clear previous preview data for this user and billing period
            RemittancePreview::where('user_id', Auth::id())
                ->where('type', 'admin')
                ->where('billing_period', $currentBillingPeriod)
                ->delete();

            // Store new preview data
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
                    'billing_period' => $currentBillingPeriod
                ]);
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
                $filename = 'shares_export_' . $currentBillingPeriod . '_' . now()->format('Y-m-d') . '.csv';
            } else {
                $export = new \App\Exports\LoansAndSavingsExport($remittanceData);
                $filename = 'loans_and_savings_export_' . $currentBillingPeriod . '_' . now()->format('Y-m-d') . '.csv';
            }

            return Excel::download($export, $filename);

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
}
