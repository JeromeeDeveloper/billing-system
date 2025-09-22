<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Branch;
use App\Models\AtmPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Exports\ListOfProfileExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RemittanceReportConsolidatedExport;
use App\Models\LoanPayment;
use Illuminate\Support\Facades\Log;
use App\Exports\PostedPaymentsExport;
use App\Exports\PostedPaymentsExportwithDescription;
use App\Models\SavingsPayment;
use Barryvdh\DomPDF\Facade\Pdf;

class AtmController extends Controller
{
    public function index(Request $request)
    {
        $billingPeriod = Auth::user()->billing_period;

        $query = Member::query()
            ->with(['branch', 'savings', 'shares', 'loanForecasts', 'loanPayments'])
            ->when($billingPeriod, function ($query, $billingPeriod) {
                $query->where('billing_period', 'like', $billingPeriod . '%');
            })
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

        // Only filter by atmPayments if no search is being performed
        if (!$request->filled('name') && !$request->filled('emp_id') && !$request->filled('cid')) {
            $query->whereHas('atmPayments');
        }

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
        $billingPeriod = Auth::user()->billing_period;

        $summary = Member::select(
            DB::raw('SUM(savings_balance) as total_savings'),
            DB::raw('SUM(share_balance) as total_shares'),
            DB::raw('SUM(loan_balance) as total_loans')
        )
            ->when($billingPeriod, function ($query, $billingPeriod) {
                $query->where('billing_period', 'like', $billingPeriod . '%');
            })
            ->first();

        $branchSummary = Member::select(
            'branches.name as branch_name',
            DB::raw('SUM(members.savings_balance) as total_savings'),
            DB::raw('SUM(members.share_balance) as total_shares'),
            DB::raw('SUM(members.loan_balance) as total_loans')
        )
            ->join('branches', 'members.branch_id', '=', 'branches.id')
            ->when($billingPeriod, function ($query, $billingPeriod) {
                $query->where('members.billing_period', 'like', $billingPeriod . '%');
            })
            ->groupBy('branches.id', 'branches.name')
            ->get();

        return view('components.admin.atm.summary-report', compact('summary', 'branchSummary'));
    }

    public function generateBranchReport()
    {
        $billingPeriod = Auth::user()->billing_period;

        $branches = Member::with(['branch', 'savings', 'shares', 'loanForecasts'])
            ->select('members.*')
            ->join('branches', 'members.branch_id', '=', 'branches.id')
            ->when($billingPeriod, function ($query, $billingPeriod) {
                $query->where('members.billing_period', 'like', $billingPeriod . '%');
            })
            ->orderBy('branches.name')
            ->get()
            ->groupBy('branch.name');

        return view('components.admin.atm.branch-report', compact('branches'));
    }

    public function exportListOfProfile()
    {
        return Excel::download(new ListOfProfileExport, 'List_of_Profile_' . now()->format('Y-m-d') . '.csv');
    }

    public function exportRemittanceReportConsolidated()
    {
        return Excel::download(new RemittanceReportConsolidatedExport, 'Remittance_Report_Consolidated_' . now()->format('Y-m-d') . '.csv');
    }

    public function postPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'member_id' => 'required|exists:members,id',
                'withdrawal_amount' => 'required|numeric|min:0',
                'loan_amounts' => 'nullable|array',
                'payment_date' => 'required|date',
                'payment_reference' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            $member = Member::with(['loanForecasts', 'savings'])->findOrFail($validated['member_id']);
            $withdrawalAmount = $validated['withdrawal_amount'];
            $loanAmounts = $validated['loan_amounts'] ?? [];
            $savingsAmounts = $request->input('savings_amounts', []); // New: get savings payments from modal

            // Check if there are any positive loan amounts or savings amounts
            $hasPositiveLoans = false;
            $hasPositiveSavings = false;

            foreach ($loanAmounts as $amount) {
                // Skip empty or zero amounts
                if (empty($amount) || $amount === '' || $amount === '0' || $amount === 0) {
                    continue;
                }

                if (is_numeric($amount) && $amount > 0) {
                    $hasPositiveLoans = true;
                    break;
                }
            }

            foreach ($savingsAmounts as $amount) {
                // Skip empty or zero amounts
                if (empty($amount) || $amount === '' || $amount === '0' || $amount === 0) {
                    continue;
                }

                if (is_numeric($amount) && $amount > 0) {
                    $hasPositiveSavings = true;
                    break;
                }
            }

            if (!$hasPositiveLoans && !$hasPositiveSavings) {
                return redirect()->back()->with('error', 'Please enter a positive amount for at least one loan or savings account.');
            }

            // Validate loan amounts for loans with positive amounts
            foreach ($loanAmounts as $loanAcctNo => $amount) {
                // Skip empty or zero amounts
                if (empty($amount) || $amount === '' || $amount === '0' || $amount === 0) {
                    continue;
                }

                if (!is_numeric($amount) || $amount < 0) {
                    return redirect()->back()->with('error', "Invalid payment amount for loan {$loanAcctNo}");
                }
            }

            // Calculate total loan payment
            $totalLoanPayment = 0;
            foreach ($loanAmounts as $loanAcctNo => $amount) {
                // Skip empty or zero amounts
                if (empty($amount) || $amount === '' || $amount === '0' || $amount === 0) {
                    continue;
                }

                if (is_numeric($amount) && $amount > 0) {
                    $totalLoanPayment += $amount;
                }
            }

            // Calculate total savings payment (from modal)
            $totalSavingsPayment = 0;
            foreach ($savingsAmounts as $acctNo => $amount) {
                if (is_numeric($amount) && $amount > 0) {
                    $totalSavingsPayment += $amount;
                }
            }

            // Calculate remaining amount for regular savings
            $remainingToSavings = $withdrawalAmount - ($totalLoanPayment + $totalSavingsPayment);

            // Validate that total loan + savings payment doesn't exceed withdrawal amount
            if ($totalLoanPayment + $totalSavingsPayment > $withdrawalAmount) {
                return redirect()->back()->with('error', 'Total loan and savings payment amount cannot exceed withdrawal amount');
            }

            DB::beginTransaction();

            // Process loan payments with distribution logic (interest first, then principal, then penalty)
            foreach ($loanAmounts as $loanAcctNo => $amount) {
                // Skip empty or zero amounts
                if (empty($amount) || $amount === '' || $amount === '0' || $amount === 0) {
                    continue;
                }

                if (!is_numeric($amount) || $amount <= 0) {
                    continue;
                }

                $paymentAmount = $amount;

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

                // Distribute payment: interest first, then principal (penalty is static/blank)
                $remainingPayment = $paymentAmount;
                $appliedToInterest = 0;
                $appliedToPrincipal = 0;
                $appliedToPenalty = 0; // Static/blank

                // Get current due amounts
                $interestDue = $forecast->interest_due ?? 0;
                $principalDue = $forecast->principal_due ?? 0;

                // Apply to interest first
                if ($interestDue > 0 && $remainingPayment > 0) {
                    $deduct = min($remainingPayment, $interestDue);
                    $appliedToInterest = $deduct;
                    $interestDue -= $deduct;
                    $remainingPayment -= $deduct;
                }

                // Then apply to principal
                if ($principalDue > 0 && $remainingPayment > 0) {
                    $deduct = min($remainingPayment, $principalDue);
                    $appliedToPrincipal = $deduct;
                    $principalDue -= $deduct;
                    $remainingPayment -= $deduct;
                }

                // Update the forecast with new due amounts (penalty remains unchanged)
                $forecast->update([
                    'interest_due' => max(0, $interestDue),
                    'principal_due' => max(0, $principalDue),
                    'total_due' => max(0, $interestDue + $principalDue)
                ]);

                // Create loan payment record with distribution details
                LoanPayment::create([
                    'member_id' => $member->id,
                    'loan_forecast_id' => $forecast->id,
                    'withdrawal_amount' => $withdrawalAmount,
                    'amount' => $paymentAmount,
                    'applied_to_interest' => $appliedToInterest,
                    'applied_to_principal' => $appliedToPrincipal,
                    'penalty' => $appliedToPenalty, // Static/blank
                    'payment_date' => $validated['payment_date'],
                    'reference_number' => $validated['payment_reference'],
                    'notes' => $validated['notes']
                ]);

                Log::info("Created loan payment with distribution: Interest: {$appliedToInterest}, Principal: {$appliedToPrincipal}, Penalty: {$appliedToPenalty} (static)");
            }

            // Find regular savings account
            $regularSavings = $member->savings()
                ->whereHas('savingProduct', function ($q) {
                    $q->where('product_type', 'atm');
                })
                ->first();

            if (!$regularSavings) {
                $regularSavings = $member->savings()
                    ->whereHas('savingProduct', function ($q) {
                        $q->where('product_type', 'regular');
                    })
                    ->first();
            }

            // Determine savings allocation details for the main ATM payment record
            $savingsAllocation = 0;
            $savingsAccountNumber = null;
            if ($remainingToSavings > 0 && $regularSavings) {
                $savingsAllocation = $remainingToSavings;
                $savingsAccountNumber = $regularSavings->account_number;
            }

            // Create ATM payment record to track the complete transaction
            $atmPayment = AtmPayment::create([
                'member_id' => $member->id,
                'user_id' => Auth::id(),
                'withdrawal_amount' => $withdrawalAmount,
                'total_loan_payment' => $totalLoanPayment,
                'savings_allocation' => $savingsAllocation,
                'savings_account_number' => $savingsAccountNumber,
                'payment_date' => $validated['payment_date'],
                'reference_number' => $validated['payment_reference'],
                'notes' => $validated['notes']
            ]);
            Log::info("Created ATM payment record: ID {$atmPayment->id}");

            // Process manual savings payments from the modal
            foreach ($savingsAmounts as $acctNo => $amount) {
                if (!is_numeric($amount) || $amount <= 0) continue;
                $saving = $member->savings()->where('account_number', $acctNo)->first();
                if ($saving) {
                    SavingsPayment::create([
                        'member_id' => $member->id,
                        'savings_id' => $saving->id,
                        'atm_payment_id' => $atmPayment->id,
                        'account_number' => $saving->account_number,
                        'amount' => $amount,
                        'payment_date' => $validated['payment_date'],
                        'reference_number' => $validated['payment_reference'],
                    ]);
                    Log::info("Created SavingsPayment for manual deposit: {$amount} to {$acctNo}");
                }
            }

            // Create a savings payment record for the remaining amount
            if ($savingsAllocation > 0 && $regularSavings) {
                SavingsPayment::create([
                    'member_id' => $member->id,
                    'savings_id' => $regularSavings->id,
                    'atm_payment_id' => $atmPayment->id,
                    'account_number' => $regularSavings->account_number,
                    'amount' => $savingsAllocation,
                    'payment_date' => $validated['payment_date'],
                    'reference_number' => $validated['payment_reference'],
                ]);
                Log::info("Created SavingsPayment for remaining allocation: {$savingsAllocation} to Regular Savings");
            }

            $totalSavingsDeposit = $totalSavingsPayment + $savingsAllocation;

            Log::info("Processed withdrawal: {$withdrawalAmount}, Loan payments: {$totalLoanPayment}, Total savings deposit: {$totalSavingsDeposit}");

            DB::commit();

            $message = "Payment processed successfully. ";
            $message .= "Withdrawal: ₱" . number_format($withdrawalAmount, 2) . ", ";
            $message .= "Loan payments: ₱" . number_format($totalLoanPayment, 2);

            if ($totalSavingsDeposit > 0) {
                $message .= ", Total savings deposit: ₱" . number_format($totalSavingsDeposit, 2);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing payment: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to process payment: ' . $e->getMessage());
        }
    }

    public function exportPostedPayments(Request $request)
    {
        try {
            $allDates = $request->input('all_dates');
            $date = $request->input('date', now()->toDateString());
            $userId = $request->input('user_id');

            $atmPaymentsQuery = AtmPayment::with(['member.branch', 'user']);
            if (!$allDates) {
                $atmPaymentsQuery->whereDate('payment_date', $date);
            }
            if ($userId) {
                $atmPaymentsQuery->where('user_id', $userId);
            }
            $atmPayments = $atmPaymentsQuery->get();

            $logDate = $allDates ? 'ALL DATES' : $date;
            Log::info("Found {$atmPayments->count()} ATM payments for export on {$logDate}");

            if ($atmPayments->isEmpty()) {
                return redirect()->back()->with('error', 'No posted payments found for the selected date(s).');
            }

            $userSuffix = $userId ? '_user_' . $userId : '';
            $filename = 'posted_payments_' . ($allDates ? 'all' : $date) . $userSuffix . '.csv';

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

    public function exportPostedPaymentsWithDescription(Request $request)
    {
        try {
            $allDates = $request->input('all_dates');
            $date = $request->input('date', now()->toDateString());
            $userId = $request->input('user_id');

            $atmPaymentsQuery = AtmPayment::with(['member.branch', 'user']);
            if (!$allDates) {
                $atmPaymentsQuery->whereDate('payment_date', $date);
            }
            if ($userId) {
                $atmPaymentsQuery->where('user_id', $userId);
            }
            $atmPayments = $atmPaymentsQuery->get();

            $logDate = $allDates ? 'ALL DATES' : $date;
            \Log::info("Found {$atmPayments->count()} ATM payments for export with description on {$logDate}");

            if ($atmPayments->isEmpty()) {
                return redirect()->back()->with('error', 'No posted payments found for the selected date(s).');
            }

            $userSuffix = $userId ? '_user_' . $userId : '';
            $filename = 'posted_payments_with_description_' . ($allDates ? 'all' : $date) . $userSuffix . '.csv';

            Excel::store(
                new PostedPaymentsExportwithDescription($atmPayments),
                $filename,
                'public'
            );

            return response()->download(storage_path('app/public/' . $filename))->deleteFileAfterSend();
        } catch (\Exception $e) {
            \Log::error('Error in exportPostedPaymentsWithDescription: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error generating export: ' . $e->getMessage());
        }
    }

    public function exportPostedPaymentsDetailed(Request $request)
    {
        try {
            $allDates = $request->input('all_dates');
            $date = $request->input('date', now()->toDateString());

            $atmPaymentsQuery = AtmPayment::with(['member.branch']);
            if (!$allDates) {
                $atmPaymentsQuery->whereDate('payment_date', $date);
            }
            $atmPayments = $atmPaymentsQuery->get();

            $logDate = $allDates ? 'ALL DATES' : $date;
            Log::info("Found {$atmPayments->count()} ATM payments for detailed export on {$logDate}");

            if ($atmPayments->isEmpty()) {
                return redirect()->back()->with('error', 'No posted payments found for the selected date(s).');
            }

            $filename = 'posted_payments_detailed_' . ($allDates ? 'all' : $date) . '.csv';

            Excel::store(
                new PostedPaymentsExport($atmPayments),
                $filename,
                'public'
            );

            return response()->download(storage_path('app/public/' . $filename))->deleteFileAfterSend();
        } catch (\Exception $e) {
            Log::error('Error in exportPostedPaymentsDetailed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error generating detailed export: ' . $e->getMessage());
        }
    }

    public function exportRemittanceReportPerBranch()
    {
        $billingPeriod = Auth::user()->billing_period ?? now()->format('Y-m');
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\RemittanceReportPerBranchExport($billingPeriod),
            'Remittance_Report_Per_Branch_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportRemittanceReportPerBranchMember()
    {
        $billingPeriod = Auth::user()->billing_period ?? now()->format('Y-m');
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\RemittanceReportPerBranchMemberExport($billingPeriod),
            'Remittance_Report_Per_Branch_Member_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

        public function generateAtmBatchReport(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        $allDates = $request->input('all_dates', false);

        // Get ATM payments based on date filter and current user
        $query = AtmPayment::with(['member.branch', 'user'])
            ->where('user_id', Auth::id())
            ->when(!$allDates, function ($query) use ($date) {
                $query->whereDate('payment_date', $date);
            });

        $atmPayments = $query->get();

        // Check if any ATM payments found
        if ($atmPayments->isEmpty()) {
            return redirect()->back()->with('error', 'No ATM payments found for the selected criteria. Please create some ATM payments first.');
        }

        // Prepare data for the report
        $reportData = [];
        foreach ($atmPayments as $payment) {
            $member = $payment->member;

            // Get loan payments for this ATM payment
            $loanPayments = LoanPayment::where('member_id', $member->id)
                ->where('payment_date', $payment->payment_date)
                ->where('withdrawal_amount', $payment->withdrawal_amount)
                ->get();

            // Get savings payments for this ATM payment
            $savingsPayments = SavingsPayment::where('atm_payment_id', $payment->id)->get();

            // Calculate components based on your criteria
            $totalAmount = $payment->withdrawal_amount;
            $posCharge = 0; // Empty as requested
            $caAmount = 0; // CA (Pinoy Coop) - ATM savings
            $loansAmount = 0; // LOANS - total loan payments
            $othersAmount = 0; // Others - regular savings remaining
            $remarks = '';

            // Calculate LOANS (total_due from loan forecasts)
            $loansAmount = $member->loanForecasts->sum('total_due');

            // Get remarks from loan payments
            foreach ($loanPayments as $loanPayment) {
                $remarks .= $loanPayment->notes ? $loanPayment->notes . '; ' : '';
            }

            // Calculate remaining amount (same logic as postPayment)
            $totalSavingsPayment = $savingsPayments->sum('amount');
            $remainingToSavings = $totalAmount - ($loansAmount + $totalSavingsPayment);

            // Calculate CA (Pinoy Coop) - ATM savings (priority)
            foreach ($savingsPayments as $savingsPayment) {
                $saving = $member->savings()->where('account_number', $savingsPayment->account_number)->first();
                if ($saving && $saving->savingProduct && $saving->savingProduct->product_type === 'atm') {
                    $caAmount += $savingsPayment->amount;
                }
            }

            // Calculate Others (regular savings remaining)
            foreach ($savingsPayments as $savingsPayment) {
                $saving = $member->savings()->where('account_number', $savingsPayment->account_number)->first();
                if ($saving && $saving->savingProduct && $saving->savingProduct->product_type === 'regular') {
                    $othersAmount += $savingsPayment->amount;
                }
            }

            // If no specific savings payments, use the remaining amount logic
            if ($totalSavingsPayment == 0 && $remainingToSavings > 0) {
                // Check for ATM savings first, then regular savings (same as postPayment logic)
                $atmSavings = $member->savings()
                    ->whereHas('savingProduct', function ($q) {
                        $q->where('product_type', 'atm');
                    })
                    ->first();

                if ($atmSavings) {
                    $caAmount = $remainingToSavings;
                } else {
                    $regularSavings = $member->savings()
                        ->whereHas('savingProduct', function ($q) {
                            $q->where('product_type', 'regular');
                        })
                        ->first();
                    if ($regularSavings) {
                        $othersAmount = $remainingToSavings;
                    }
                }
            }

            // Calculate net amount due
            $netAmountDue = $totalAmount - $loansAmount;

            $reportData[] = [
                'member' => $member->fname . ' ' . $member->lname,
                'amount' => $totalAmount,
                'pos_charge' => $posCharge,
                'ca_amount' => $caAmount,
                'loans' => $loansAmount,
                'others' => $othersAmount,
                'net_amount_due' => $netAmountDue,
                'remarks' => trim($remarks, '; ')
            ];
        }

        // Get branch name for header
        $branchName = 'Head Office'; // Default for admin

        // Prepare images for PDF
        $picture1Path = public_path('images/Picture1.png');
        $picture2Path = public_path('images/Picture2.png');

        $picture1Base64 = '';
        $picture2Base64 = '';

        if (file_exists($picture1Path)) {
            $picture1Base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($picture1Path));
        }

        if (file_exists($picture2Path)) {
            $picture2Base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($picture2Path));
        }

        // Generate PDF
        $pdf = Pdf::loadView('reports.atm-batch', [
            'reportData' => $reportData,
            'branchName' => $branchName,
            'date' => $date,
            'allDates' => $allDates,
            'picture1Base64' => $picture1Base64,
            'picture2Base64' => $picture2Base64
        ]);

        $filename = 'ATM_Batch_Report_' . $branchName . '_' . $date . '.pdf';
        return $pdf->download($filename);
    }
}
