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

        return view('components.admin.remittance.remittance', compact('dates'));
    }

    public function list()
    {
        $remittances = Remittance::with(['member', 'branch'])
            ->orderBy('created_at', 'desc')
            ->get();

        $savings = Savings::with('member')
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('components.admin.remittance.list', compact('remittances', 'savings'));
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

            // Store the processed data in the session
            $processedData = collect($import->getResults())->filter(function($record) {
                return $record['status'] === 'success';
            })->map(function($record) {
                return [
                    'member_id' => $record['member_id'] ?? null,
                    'loans' => $record['loans'] ?? 0,
                    'savings' => $record['savings'] ?? []
                ];
            })->values()->all();

            session(['remittance_data' => $processedData]);

            DB::commit();

            return redirect()->back()
                ->with('preview', $import->getResults())
                ->with('stats', $import->getStats())
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
