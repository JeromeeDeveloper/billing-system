<?php

namespace App\Http\Controllers;

use App\Imports\RemittanceImport;
use App\Exports\RemittanceExport;
use App\Models\Remittance;
use App\Models\Savings;
use App\Models\Member;
use App\Models\LoanForecast;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RemittanceController extends Controller
{
    public function index()
    {
        // Get the preview data from session
        $preview = session('preview');
        $stats = session('stats');

        // If no preview data in session, check if we have processed data
        if (!$preview && session('remittance_data')) {
            $processedData = session('remittance_data');
            $unmatched = session('unmatched_data') ?? [];

            // Reconstruct preview data from both processed and unmatched data
            $preview = collect($processedData)->map(function($record) {
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
            }))->all();

            // Reconstruct stats
            $stats = [
                'matched' => count($processedData),
                'unmatched' => count($unmatched),
                'total_amount' => collect($processedData)->sum(function($record) {
                    return $record['loans'] + collect($record['savings'])->sum();
                })
            ];
        }

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

            // Store all data in session
            session([
                'remittance_data' => $processedData,
                'unmatched_data' => $unmatchedData,
                'preview' => $results,
                'stats' => $import->getStats()
            ]);

            DB::commit();

            return redirect()->route('remittance.index')
                ->with('success', 'File processed successfully. Check the preview below.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error processing file: ' . $e->getMessage());
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
