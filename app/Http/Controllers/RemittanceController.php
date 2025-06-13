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

class RemittanceController extends Controller
{
    public function index(Request $request)
    {
        // Get preview data from database for current user
        $previewCollection = RemittancePreview::where('user_id', Auth::id())
            ->where('type', 'admin')
            ->get();

        // Calculate stats
        $stats = [
            'matched' => $previewCollection->where('status', 'success')->count(),
            'unmatched' => $previewCollection->where('status', '!=', 'success')->count(),
            'total_amount' => $previewCollection->sum(function ($record) {
                return $record->loans + collect($record->savings)->sum();
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
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // max 10MB
        ]);

        try {
            DB::beginTransaction();

            $import = new RemittanceImport();
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();

            // Clear previous preview data for this user
            RemittancePreview::where('user_id', Auth::id())
                ->where('type', 'admin')
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
                    'type' => 'admin'
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
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // max 10MB
        ]);

        try {
            DB::beginTransaction();

            $import = new ShareRemittanceImport();
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();
            $stats = $import->getStats();

            // Clear previous preview data for this user
            RemittancePreview::where('user_id', Auth::id())
                ->where('type', 'admin')
                ->delete();

            // Store new preview data
            foreach ($results as $result) {
                RemittancePreview::create([
                    'user_id' => Auth::id(),
                    'emp_id' => $result['emp_id'],
                    'name' => $result['name'],
                    'member_id' => $result['member_id'],
                    'loans' => 0,
                    'savings' => [],
                    'status' => $result['status'],
                    'message' => $result['message'],
                    'type' => 'admin'
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
            // If we have uploaded data in session, use that
            $remittanceData = session('remittance_data');

            // If no session data, get all records from database
            if (empty($remittanceData)) {
                $query = Remittance::with(['member.branch', 'member.loanProductMembers.loanProduct', 'member.savings'])
                    ->whereHas('member'); // Only get records with valid members

                $remittances = $query->get();

                if ($remittances->isEmpty()) {
                    return redirect()->back()->with('error', 'No remittance records found.');
                }

                $remittanceData = $remittances->map(function($remittance) {
                    // Get savings data for this member
                    $savingsData = [];
                    foreach ($remittance->member->savings as $saving) {
                        $savingsData[$saving->product_name] = $saving->deduction_amount ?? 0;
                    }

                    return [
                        'member_id' => $remittance->member_id,
                        'loans' => $remittance->loan_payment,
                        'savings' => $savingsData
                    ];
                })->all();
            }

            $filename = 'remittance_export_' . now()->format('Y-m-d') . '.xlsx';

            Excel::store(
                new RemittanceExport($remittanceData),
                $filename,
                'public'
            );

            return response()->download(storage_path('app/public/' . $filename))->deleteFileAfterSend();
        } catch (\Exception $e) {
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
