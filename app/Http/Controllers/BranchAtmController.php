<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\LoanPayment;
use App\Models\LoanForecast;
use App\Models\Saving;
use App\Models\Share;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BranchAtmController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::with(['branch', 'savings', 'shares', 'loanForecasts'])
            ->where('branch_id', Auth::user()->branch_id);

        if ($request->filled('name')) {
            $query->where(function ($q) use ($request) {
                $q->where('fname', 'like', '%' . $request->name . '%')
                    ->orWhere('lname', 'like', '%' . $request->name . '%');
            });
        }

        if ($request->filled('emp_id')) {
            $query->where('emp_id', 'like', '%' . $request->emp_id . '%');
        }

        if ($request->filled('cid')) {
            $query->where('cid', 'like', '%' . $request->cid . '%');
        }

        $members = $query->paginate(10);

        return view('components.branch.atm.atm', compact('members'));
    }

    public function updateBalance(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'savings' => 'array',
            'savings.*.account_number' => 'required|string',
            'savings.*.balance' => 'required|numeric',
            'shares' => 'array',
            'shares.*.account_number' => 'required|string',
            'shares.*.balance' => 'required|numeric',
            'loans' => 'array',
            'loans.*.account_number' => 'required|string',
            'loans.*.balance' => 'required|numeric',
        ]);

        $member = Member::findOrFail($request->member_id);

        // Verify member belongs to user's branch
        if ($member->branch_id !== Auth::user()->branch_id) {
            return back()->with('error', 'Unauthorized access to member data.');
        }

        DB::beginTransaction();

        try {
            // Update savings balances
            if ($request->has('savings')) {
                foreach ($request->savings as $savingData) {
                    $saving = Saving::where('account_number', $savingData['account_number'])
                        ->where('member_id', $member->id)
                        ->first();

                    if ($saving) {
                        $saving->update([
                            'current_balance' => $savingData['balance']
                        ]);
                    }
                }
            }

            // Update share balances
            if ($request->has('shares')) {
                foreach ($request->shares as $shareData) {
                    $share = Share::where('account_number', $shareData['account_number'])
                        ->where('member_id', $member->id)
                        ->first();

                    if ($share) {
                        $share->update([
                            'current_balance' => $shareData['balance']
                        ]);
                    }
                }
            }

            // Update loan balances
            if ($request->has('loans')) {
                foreach ($request->loans as $loanData) {
                    $loan = LoanForecast::where('loan_acct_no', $loanData['account_number'])
                        ->where('member_id', $member->id)
                        ->first();

                    if ($loan) {
                        $loan->update([
                            'total_due' => $loanData['balance']
                        ]);
                    }
                }

                // Recalculate total loan balance
                $totalLoanBalance = $member->loanForecasts()->sum('total_due');
                $member->update(['loan_balance' => $totalLoanBalance]);
            }

            DB::commit();
            return back()->with('success', 'Account balances updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating account balances: ' . $e->getMessage());
            return back()->with('error', 'Failed to update account balances.');
        }
    }

    public function postPayment(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_reference' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        $member = Member::findOrFail($request->member_id);

        // Verify member belongs to user's branch
        if ($member->branch_id !== Auth::user()->branch_id) {
            return back()->with('error', 'Unauthorized access to member data.');
        }

        DB::beginTransaction();

        try {
            $remainingPayment = $request->payment_amount;

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

            // Process payments for each loan forecast
            foreach ($forecasts as $forecastData) {
                if ($remainingPayment <= 0) break;

                $forecast = $forecastData['forecast'];
                $paymentAmount = min($remainingPayment, $forecast->total_due);

                if ($paymentAmount > 0) {
                    // Create loan payment record
                    LoanPayment::create([
                        'member_id' => $member->id,
                        'loan_forecast_id' => $forecast->id,
                        'amount' => $paymentAmount,
                        'payment_date' => $request->payment_date,
                        'reference_number' => $request->payment_reference,
                        'notes' => $request->notes
                    ]);

                    // Update loan forecast
                    $forecast->update([
                        'total_due' => $forecast->total_due - $paymentAmount,
                        'total_due_after_remittance' => max(0, $forecast->total_due_after_remittance - $paymentAmount)
                    ]);

                    $remainingPayment -= $paymentAmount;
                }
            }

            // Update member's total loan balance
            $totalLoanBalance = $member->loanForecasts()->sum('total_due');
            $member->update(['loan_balance' => $totalLoanBalance]);

            DB::commit();
            return back()->with('success', 'Payment posted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error posting payment: ' . $e->getMessage());
            return back()->with('error', 'Failed to post payment.');
        }
    }
}
