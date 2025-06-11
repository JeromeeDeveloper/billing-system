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

class BranchRemittanceController extends Controller
{
    public function index(Request $request)
    {
        // Get the branch_id from the authenticated user
        $branch_id = Auth::user()->branch_id;

        // Get the preview data from session
        $preview = session('branch_preview');
        $stats = session('branch_stats');

        // Initialize empty stats if none exist
        if (!$stats) {
            $stats = [
                'matched' => 0,
                'unmatched' => 0,
                'total_amount' => 0
            ];
        }

        $previewCollection = collect([]);

        // If no preview data in session, check if we have processed data
        if (!$preview && session('branch_remittance_data')) {
            $processedData = session('branch_remittance_data');
            $unmatched = session('branch_unmatched_data') ?? [];

            // Reconstruct preview data from both processed and unmatched data
            $previewCollection = collect($processedData)->map(function($record) {
                $member = Member::find($record['member_id']);
                return [
                    'status' => 'success',
                    'emp_id' => $member ? $member->emp_id : '',
                    'name' => $member ? $member->fname . ' ' . $member->lname : '',
                    'loans' => $record['loans'],
                    'savings' => $record['savings'],
                    'message' => 'Record processed successfully'
                ];
            })->concat(collect($unmatched)->map(function($record) {
                return [
                    'status' => 'error',
                    'emp_id' => $record['emp_id'] ?? '',
                    'name' => $record['name'] ?? '',
                    'loans' => $record['loans'] ?? 0,
                    'savings' => $record['savings'] ?? [],
                    'message' => $record['message'] ?? 'Record could not be processed'
                ];
            }));
        } elseif ($preview) {
            $previewCollection = collect($preview);
        }

        // Filter preview data if filter is set
        $filter = $request->get('filter');
        if ($filter === 'matched') {
            $previewCollection = $previewCollection->filter(function($record) {
                return $record['status'] === 'success';
            });
        } elseif ($filter === 'unmatched') {
            $previewCollection = $previewCollection->filter(function($record) {
                return $record['status'] === 'error';
            });
        }

        // Paginate the collection
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

            // Separate matched and unmatched records
            $processedData = collect($results)->filter(function($record) {
                return $record['status'] === 'success';
            })->map(function($record) {
                return [
                    'member_id' => $record['member_id'] ?? null,
                    'loans' => $record['loans'] ?? 0,
                    'savings' => $record['savings'] ?? []
                ];
            })->values()->all();

            $unmatchedData = collect($results)->filter(function($record) {
                return $record['status'] !== 'success';
            })->values()->all();

            // Debug information
            \Log::info('Upload - Branch ID: ' . $branch_id);
            \Log::info('Upload - Processed Data Count: ' . count($processedData));
            \Log::info('Upload - First Processed Record: ' . json_encode(reset($processedData)));

            // Store all data in session with branch prefix
            session([
                'branch_remittance_data' => $processedData,
                'branch_unmatched_data' => $unmatchedData,
                'branch_preview' => $results,
                'branch_stats' => $import->getStats()
            ]);

            DB::commit();

            return redirect()->route('branch.remittance.index')
                ->with('success', 'File processed successfully. Check the preview below.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Upload Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
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
