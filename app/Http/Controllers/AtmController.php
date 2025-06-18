<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Exports\ListOfProfileExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RemittanceReportConsolidatedExport;
use App\Models\LoanPayment;
use Illuminate\Support\Facades\Log;

class AtmController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::query()
            ->with(['branch', 'savings', 'shares', 'loanForecasts', 'loanPayments'])
            ->when($request->filled('name'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('fname', 'like', '%' . $request->name . '%')
                        ->orWhere('lname', 'like', '%' . $request->name . '%');
                });
            })
            ->when($request->filled('emp_id'), function ($query) use ($request) {
                $query->where('emp_id', 'like', '%' . $request->emp_id . '%');
            })
            ->when($request->filled('cid'), function ($query) use ($request) {
                $query->where('cid', 'like', '%' . $request->cid . '%');
            });

        $members = $query->paginate(10);

        return view('components.admin.atm.atm', compact('members'));
    }

    public function updateBalance(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'savings' => 'array',
            'savings.*.account_number' => 'required|string',
            'savings.*.balance' => 'required|numeric|min:0',
            'shares' => 'array',
            'shares.*.account_number' => 'required|string',
            'shares.*.balance' => 'required|numeric|min:0',
            'loans' => 'array',
            'loans.*.account_number' => 'required|string',
            'loans.*.balance' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $member = Member::findOrFail($request->member_id);

            // Update savings balances
            if ($request->has('savings')) {
                foreach ($request->savings as $saving) {
                    $member->savings()
                        ->where('account_number', $saving['account_number'])
                        ->update(['current_balance' => $saving['balance']]);
                }
            }

            // Update share balances
            if ($request->has('shares')) {
                foreach ($request->shares as $share) {
                    $member->shares()
                        ->where('account_number', $share['account_number'])
                        ->update(['current_balance' => $share['balance']]);
                }
            }

            // Update loan balances
            if ($request->has('loans')) {
                foreach ($request->loans as $loan) {
                    $member->loanForecasts()
                        ->where('loan_acct_no', $loan['account_number'])
                        ->update(['total_due' => $loan['balance']]);
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Account balances updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating account balances: ' . $e->getMessage());
        }
    }

    public function generateSummaryReport()
    {
        $summary = Member::select(
            DB::raw('SUM(savings_balance) as total_savings'),
            DB::raw('SUM(share_balance) as total_shares'),
            DB::raw('SUM(loan_balance) as total_loans')
        )->first();

        $branchSummary = Member::select(
            'branches.name as branch_name',
            DB::raw('SUM(members.savings_balance) as total_savings'),
            DB::raw('SUM(members.share_balance) as total_shares'),
            DB::raw('SUM(members.loan_balance) as total_loans')
        )
        ->join('branches', 'members.branch_id', '=', 'branches.id')
        ->groupBy('branches.id', 'branches.name')
        ->get();

        return view('components.admin.atm.summary-report', compact('summary', 'branchSummary'));
    }

    public function generateBranchReport()
    {
        $branches = Member::with(['branch', 'savings', 'shares', 'loanForecasts'])
            ->select('members.*')
            ->join('branches', 'members.branch_id', '=', 'branches.id')
            ->orderBy('branches.name')
            ->get()
            ->groupBy('branch.name');

        return view('components.admin.atm.branch-report', compact('branches'));
    }

    public function exportListOfProfile()
    {
        return Excel::download(new ListOfProfileExport, 'List_of_Profile_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportRemittanceReportConsolidated()
    {
        return Excel::download(new RemittanceReportConsolidatedExport, 'Remittance_Report_Consolidated_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function postPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'member_id' => 'required|exists:members,id',
                'payment_amount' => 'required|numeric|min:0',
                'payment_date' => 'required|date',
                'payment_reference' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            $member = Member::with(['loanForecasts'])->findOrFail($validated['member_id']);
            $paymentAmount = $validated['payment_amount'];
            $remainingPayment = $paymentAmount;

            DB::beginTransaction();

            // Get all loan forecasts and sort them by product prioritization
            $forecasts = collect($member->loanForecasts)->map(function($forecast) use ($member) {
                // Extract product code from loan_acct_no (e.g., 40102 from 0304-001-40102-000023-3)
                $productCode = explode('-', $forecast->loan_acct_no)[2] ?? null;

                // Find the loan product member with matching product code
                $loanProductMember = $member->loanProductMembers()
                    ->whereHas('loanProduct', function($query) use ($productCode) {
                        $query->where('product_code', $productCode);
                    })
                    ->first();

                return [
                    'forecast' => $forecast,
                    'prioritization' => $loanProductMember ? $loanProductMember->prioritization : 999,
                    'product_code' => $productCode,
                    'total_due' => $forecast->total_due,
                    'principal' => $forecast->principal ?? 0
                ];
            })->sortBy([
                ['prioritization', 'asc'],
                ['principal', 'desc']
            ]);

            foreach ($forecasts as $forecastData) {
                if ($remainingPayment <= 0) break;

                $forecast = $forecastData['forecast'];
                $totalDue = $forecastData['total_due'];
                $productCode = $forecastData['product_code'];

                // Calculate how much to pay for this loan
                $deductionAmount = min($remainingPayment, $totalDue);

                if ($productCode && $deductionAmount > 0) {
                    // Update the total_due in LoanForecast
                    $newTotalDue = $totalDue - $deductionAmount;
                    $forecast->update([
                        'total_due' => max(0, $newTotalDue) // Ensure total_due doesn't go below 0
                    ]);

                    // Create loan payment record
                    LoanPayment::create([
                        'member_id' => $member->id,
                        'loan_forecast_id' => $forecast->id,
                        'amount' => $deductionAmount,
                        'payment_date' => $validated['payment_date'],
                        'reference_number' => $validated['payment_reference'],
                        'notes' => $validated['notes']
                    ]);

                    // Subtract the deduction amount from remaining payment
                    $remainingPayment -= $deductionAmount;

                    // If this loan is fully paid, continue to next loan
                    if ($newTotalDue <= 0) {
                        continue;
                    }
                }
            }

            // Recalculate and update member's total loan balance
            $totalLoanBalance = $member->loanForecasts ? $member->loanForecasts->sum('total_due') : 0;
            $member->update(['loan_balance' => $totalLoanBalance]);
            Log::info("Updated member {$member->id} total loan balance to: {$totalLoanBalance}");

            // If there's still remaining payment, log it as unused
            if ($remainingPayment > 0) {
                Log::warning("Member {$member->id} has unused loan payment: {$remainingPayment}");
            }

            DB::commit();

            return redirect()->back()->with('success', 'Payment processed successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing payment: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to process payment: ' . $e->getMessage());
        }
    }
}
