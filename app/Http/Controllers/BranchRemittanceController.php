<?php

namespace App\Http\Controllers;

use App\Imports\RemittanceImport;
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

        // Get preview data from database for current user
        $previewCollection = RemittancePreview::where('user_id', Auth::id())
            ->where('type', 'branch')
            ->get();

        // Calculate stats
        $stats = [
            'matched' => $previewCollection->where('status', 'success')->count(),
            'unmatched' => $previewCollection->where('status', '!=', 'success')->count(),
            'total_amount' => $previewCollection->sum(function ($record) {
                return $record->loans + collect($record->savings)->sum();
            })
        ];

        // Get unique dates for the dropdown (only for this branch)
        $dates = Remittance::where('branch_id', $branch_id)
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

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // max 10MB
        ]);

        try {
            DB::beginTransaction();

            // Get branch_id from authenticated user
            $branch_id = Auth::user()->branch_id;

            // Create import instance with branch_id
            $import = new RemittanceImport($branch_id);
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();

            // Clear previous preview data for this user
            RemittancePreview::where('user_id', Auth::id())
                ->where('type', 'branch')
                ->delete();

            // Store new preview data
            foreach ($results as $result) {
                RemittancePreview::create([
                    'user_id' => Auth::id(),
                    'emp_id' => $result['emp_id'],
                    'name' => $result['name'],
                    'member_id' => $result['member_id'],
                    'loans' => $result['loans'],
                    'savings' => $result['savings'],
                    'status' => $result['status'],
                    'message' => $result['message'],
                    'type' => 'branch'
                ]);
            }

            DB::commit();

            return redirect()->route('branch.remittance.index')
                ->with('success', 'File processed successfully. Check the preview below.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Upload Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()
                ->with('error', 'Error processing file: ' . $e->getMessage());
        }
    }

    public function generateExport()
    {
        try {
            // Get branch_id from authenticated user
            $branch_id = Auth::user()->branch_id;

            // If we have uploaded data in session, use that
            $remittanceData = session('branch_remittance_data');

            // If no session data, get branch-specific records from database
            if (empty($remittanceData)) {
                $query = Remittance::with(['member', 'member.savings'])
                    ->whereHas('member', function($query) use ($branch_id) {
                        $query->where('branch_id', $branch_id);
                    });

                $remittances = $query->get();

                if ($remittances->isEmpty()) {
                    return redirect()->back()->with('error', 'No remittance records found for your branch.');
                }

                $remittanceData = $remittances->map(function($remittance) {
                    // Get savings data for this member
                    $savingsData = [];
                    foreach ($remittance->member->savings as $saving) {
                        $savingsData[$saving->product_name] = $saving->remittance_amount ?? 0;
                    }

                    return [
                        'member_id' => $remittance->member_id,
                        'loans' => $remittance->loan_payment,
                        'savings' => $savingsData
                    ];
                })->all();
            }

            // Debug information
            \Log::info('Branch ID: ' . $branch_id);
            \Log::info('Remittance Data Count: ' . count($remittanceData));
            \Log::info('First Record: ' . json_encode(reset($remittanceData)));

            $filename = 'branch_remittance_export_' . now()->format('Y-m-d') . '.xlsx';

            Excel::store(
                new BranchRemittanceExport($remittanceData),
                $filename,
                'public'
            );

            return response()->download(storage_path('app/public/' . $filename))->deleteFileAfterSend();
        } catch (\Exception $e) {
            \Log::error('Export Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Error generating export: ' . $e->getMessage());
        }
    }
}
