<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\LoanPayment;
use App\Models\LoanForecast;
use App\Models\Saving;
use App\Models\Shares;
use App\Models\AtmPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PostedPaymentsExport;

class BranchAtmController extends Controller
{
    public function index(Request $request)
    {
        $billingPeriod = Auth::user()->billing_period;

        $query = Member::with(['branch', 'savings', 'shares', 'loanForecasts', 'loanPayments'])
            ->where('branch_id', Auth::user()->branch_id)
            ->when($billingPeriod, function ($query, $billingPeriod) {
                $query->where('billing_period', 'like', $billingPeriod . '%');
            });

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
                    $share = Shares::where('account_number', $shareData['account_number'])
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
        try {
            $validated = $request->validate([
                'member_id' => 'required|exists:members,id',
                'withdrawal_amount' => 'required|numeric|min:0',
                'selected_loans' => 'required|array|min:1',
                'selected_loans.*' => 'string',
                'loan_amounts' => 'required|array',
                'payment_date' => 'required|date',
                'payment_reference' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            $member = Member::with(['loanForecasts', 'savings'])->findOrFail($validated['member_id']);

            // Verify member belongs to user's branch
            if ($member->branch_id !== Auth::user()->branch_id) {
                return back()->with('error', 'Unauthorized access to member data.');
            }

            $withdrawalAmount = $validated['withdrawal_amount'];
            $selectedLoans = $validated['selected_loans'];
            $loanAmounts = $validated['loan_amounts'];

            // Validate loan amounts for selected loans only
            foreach ($selectedLoans as $loanAcctNo) {
                if (!isset($loanAmounts[$loanAcctNo])) {
                    return redirect()->back()->with('error', "Payment amount is required for loan {$loanAcctNo}");
                }

                if (!is_numeric($loanAmounts[$loanAcctNo]) || $loanAmounts[$loanAcctNo] < 0) {
                    return redirect()->back()->with('error', "Invalid payment amount for loan {$loanAcctNo}");
                }
            }

            // Calculate total loan payment
            $totalLoanPayment = 0;
            foreach ($selectedLoans as $loanAcctNo) {
                if (isset($loanAmounts[$loanAcctNo])) {
                    $totalLoanPayment += $loanAmounts[$loanAcctNo];
                }
            }

            // Calculate remaining amount for savings
            $remainingToSavings = $withdrawalAmount - $totalLoanPayment;

            // Validate that total loan payment doesn't exceed withdrawal amount
            if ($totalLoanPayment > $withdrawalAmount) {
                return redirect()->back()->with('error', 'Total loan payment amount cannot exceed withdrawal amount');
            }

            DB::beginTransaction();

            // Process loan payments
            foreach ($selectedLoans as $loanAcctNo) {
                if (!isset($loanAmounts[$loanAcctNo]) || $loanAmounts[$loanAcctNo] <= 0) {
                    continue;
                }

                $paymentAmount = $loanAmounts[$loanAcctNo];

                // Find the loan forecast
                $forecast = $member->loanForecasts()->where('loan_acct_no', $loanAcctNo)->first();

                if (!$forecast) {
                    DB::rollBack();
                    return redirect()->back()->with('error', "Loan account {$loanAcctNo} not found");
                }

                // Validate payment amount doesn't exceed total due
                if ($paymentAmount > $forecast->total_due) {
                    DB::rollBack();
                    return redirect()->back()->with('error', "Payment amount for loan {$loanAcctNo} cannot exceed total due");
                }

                // Update the total_due in LoanForecast
                $newTotalDue = $forecast->total_due - $paymentAmount;
                $forecast->update([
                    'total_due' => max(0, $newTotalDue)
                ]);

                // Create loan payment record
                LoanPayment::create([
                    'member_id' => $member->id,
                    'loan_forecast_id' => $forecast->id,
                    'withdrawal_amount' => $withdrawalAmount,
                    'amount' => $paymentAmount,
                    'payment_date' => $validated['payment_date'],
                    'reference_number' => $validated['payment_reference'],
                    'notes' => $validated['notes']
                ]);
            }

            // Add remaining amount to Regular Savings if available
            $savingsAccountNumber = null;
            if ($remainingToSavings > 0) {
                // Debug: Log all savings accounts for this member
                $allSavings = $member->savings;
                Log::info("Member {$member->id} has {$allSavings->count()} savings accounts:");
                foreach ($allSavings as $saving) {
                    Log::info("- Account: {$saving->account_number}, Product: {$saving->product_name}, Product Code: {$saving->product_code}");
                }

                // First try to find Regular Savings
                $regularSavings = $member->savings()
                    ->where('product_name', 'Regular Savings')
                    ->first();

                // If not found, try to find any savings account
                if (!$regularSavings) {
                    $regularSavings = $member->savings()->first();
                    Log::info("Member {$member->id} has no Regular Savings, using first available savings account");
                }

                if ($regularSavings) {
                    // Update the amount_to_deduct field in savings
                    $regularSavings->update([
                        'amount_to_deduct' => $remainingToSavings
                    ]);

                    $savingsAccountNumber = $regularSavings->account_number;
                    Log::info("Added remaining amount {$remainingToSavings} to savings account {$savingsAccountNumber} for member {$member->id}");
                    Log::info("Savings account details: Product: {$regularSavings->product_name}, Account: {$regularSavings->account_number}");
                } else {
                    Log::warning("Member {$member->id} has no savings accounts at all for remaining amount {$remainingToSavings}");
                }
            } else {
                Log::info("No remaining amount to allocate to savings for member {$member->id}");
            }

            // Create ATM payment record to track the complete transaction
            $atmPayment = AtmPayment::create([
                'member_id' => $member->id,
                'withdrawal_amount' => $withdrawalAmount,
                'total_loan_payment' => $totalLoanPayment,
                'savings_allocation' => $remainingToSavings,
                'savings_account_number' => $savingsAccountNumber,
                'payment_date' => $validated['payment_date'],
                'reference_number' => $validated['payment_reference'],
                'notes' => $validated['notes']
            ]);

            Log::info("Created ATM payment record: ID {$atmPayment->id}, Savings allocation: {$atmPayment->savings_allocation}, Account: {$atmPayment->savings_account_number}");

            // Recalculate and update member's total loan balance
            $totalLoanBalance = $member->loanForecasts ? $member->loanForecasts->sum('total_due') : 0;
            $member->update(['loan_balance' => $totalLoanBalance]);

            Log::info("Updated member {$member->id} total loan balance to: {$totalLoanBalance}");
            Log::info("Processed withdrawal: {$withdrawalAmount}, Loan payments: {$totalLoanPayment}, Remaining to savings: {$remainingToSavings}");

            DB::commit();

            $message = "Payment processed successfully. ";
            $message .= "Withdrawal: ₱" . number_format($withdrawalAmount, 2) . ", ";
            $message .= "Loan payments: ₱" . number_format($totalLoanPayment, 2);

            if ($remainingToSavings > 0) {
                $message .= ", Remaining to savings: ₱" . number_format($remainingToSavings, 2);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing payment: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to process payment: ' . $e->getMessage());
        }
    }

    public function exportPostedPayments()
    {
        try {
            // Get branch_id from authenticated user
            $branch_id = Auth::user()->branch_id;

            // Get all ATM payments for today from this branch
            $atmPayments = AtmPayment::with(['member.branch'])
                ->whereHas('member', function($query) use ($branch_id) {
                    $query->where('branch_id', $branch_id);
                })
                ->whereDate('payment_date', now()->toDateString())
                ->get();

            Log::info("Found {$atmPayments->count()} ATM payments for today from branch {$branch_id}");

            if ($atmPayments->isEmpty()) {
                return redirect()->back()->with('error', 'No posted payments found for today.');
            }

            // Log details of each ATM payment
            foreach ($atmPayments as $atmPayment) {
                Log::info("ATM Payment ID: {$atmPayment->id}, Member: {$atmPayment->member_id}, Withdrawal: {$atmPayment->withdrawal_amount}, Loan Payment: {$atmPayment->total_loan_payment}, Savings: {$atmPayment->savings_allocation}, Account: {$atmPayment->savings_account_number}");
            }

            $filename = 'branch_posted_payments_' . now()->format('Y-m-d') . '.csv';

            Excel::store(
                new PostedPaymentsExport($atmPayments),
                $filename,
                'public'
            );

            return response()->download(storage_path('app/public/' . $filename))->deleteFileAfterSend();
        } catch (\Exception $e) {
            Log::error('Error in exportPostedPayments: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error generating export: ' . $e->getMessage());
        }
    }
}
