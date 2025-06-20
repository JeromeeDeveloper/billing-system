<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Member;
use App\Models\MasterList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MasterController extends Controller
{

    public function index(Request $request)
    {
        $search = $request->input('search');
        $billingPeriod = $request->input('billing_period');

        $masterlists = MasterList::with([
            'member.loanForecasts',
            'member.savings',
            'member.shares',
            'member.branch',
            'branch'
        ])
            ->when($billingPeriod, function ($query, $billingPeriod) {
                $query->where('billing_period', 'like', $billingPeriod . '%');
            })
            ->when($search, function ($query, $search) {
                $query->whereHas('member', function ($q) use ($search) {
                    $q->where('cid', 'like', "%{$search}%")
                        ->orWhere('lname', 'like', "%{$search}%")
                        ->orWhere('fname', 'like', "%{$search}%");
                })
                ->orWhereHas('branch', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->paginate(25)
            ->appends(['search' => $search, 'billing_period' => $billingPeriod]);

        // Format the dates for JSON serialization
        $masterlists->getCollection()->transform(function ($item) {
            $member = $item->member;

            // Format savings dates
            $member->savings->transform(function ($saving) {
                $saving->open_date = $saving->open_date ? $saving->open_date->format('Y-m-d') : null;
                return $saving;
            });

            // Format shares dates
            $member->shares->transform(function ($share) {
                $share->open_date = $share->open_date ? $share->open_date->format('Y-m-d') : null;
                return $share;
            });

            return $item;
        });

        $branches = Branch::all();

        // Load mortuary products for JavaScript access
        $mortuaryProducts = \App\Models\SavingProduct::where('product_name', 'like', '%mortuary%')
            ->orWhere('product_name', 'like', '%Mortuary%')
            ->get();

        return view('components.admin.master.master', compact('masterlists', 'branches', 'mortuaryProducts'));
    }

   public function store(Request $request)
{
    $request->validate([
        'cid' => 'required|string|unique:members|max:255',
        'emp_id' => 'nullable|string|unique:members|max:255',
        'fname' => 'nullable|string|max:255',
        'lname' => 'nullable|string|max:255',
        'address' => 'nullable|string',
        'savings_balance' => 'nullable|numeric',
        'share_balance' => 'nullable|numeric',
        'loan_balance' => 'nullable|numeric',
        'birth_date' => 'nullable|date',
        'expiry_date' => 'nullable|date',
        'date_registered' => 'nullable|date',
        'gender' => 'nullable|in:male,female,other',
        'customer_type' => 'nullable|string',
        'customer_classification' => 'nullable|string',
        'occupation' => 'nullable|string',
        'industry' => 'nullable|string',
        'area_officer' => 'nullable|string',
        'area' => 'nullable|string',
        'status' => 'nullable|in:active,merged',
        'approval_no' => 'nullable|string',
        'start_hold' => 'nullable|date',
        'account_status' => 'nullable|in:deduction,non-deduction',
        'branch_id' => 'nullable|exists:branches,id',
    ]);

    // Get current billing period from authenticated user
    $billingPeriod = Auth::user()->billing_period;

    if (!$billingPeriod) {
        return redirect()->back()->with('error', 'Please set a billing period in the dashboard first.');
    }

    // Merge the billing period into the request data
    $memberData = array_merge(
        $request->except(['savings_balance', 'share_balance']),
        ['billing_period' => $billingPeriod]
    );

    // Create the member with billing period
    $member = Member::create($memberData);

    if ($request->filled('savings_balance')) {
        $member->savings()->create([
            'current_balance' => $request->savings_balance,
        ]);
    }

    if ($request->filled('share_balance')) {
        $member->shares()->create([
            'current_balance' => $request->share_balance,
        ]);
    }

    // Create master list entry for the new member
    MasterList::create([
        'member_id' => $member->id,
        'branches_id' => $member->branch_id ?? null,
        'billing_period' => $billingPeriod,
        'status' => 'deduction'
    ]);

    return redirect()->back()->with('success', 'Member successfully added.');
}

    public function update(Request $request, $id)
    {
        try {
            Log::info('Update request received for member ' . $id, [
                'request_data' => $request->all(),
                'content_type' => $request->header('Content-Type'),
                'accept' => $request->header('Accept')
            ]);

            $request->validate([
                // Member validation
                'cid' => 'nullable|string|max:255',
                'emp_id' => 'nullable|string|max:255',
                'fname' => 'nullable|string|max:255',
                'lname' => 'nullable|string|max:255',
                'address' => 'nullable|string',
                'birth_date' => 'nullable|date',
                'expiry_date' => 'nullable|date',
                'date_registered' => 'nullable|date',
                'gender' => 'nullable|string|in:male,female,other',
                'customer_type' => 'nullable|string|max:255',
                'customer_classification' => 'nullable|string|max:255',
                'occupation' => 'nullable|string|max:255',
                'industry' => 'nullable|string|max:255',
                'area_officer' => 'nullable|string|max:255',
                'area' => 'nullable|string|max:255',
                'status' => 'nullable|string|in:active,merged',
                'additional_address' => 'nullable|string',
                'account_status' => 'nullable|string|in:deduction,non-deduction',
                'branch_id' => 'nullable|exists:branches,id',

                // Savings validation
                'savings' => 'nullable|array',
                'savings.*.account_number' => 'required|string',
                'savings.*.current_balance' => 'required|numeric',
                'savings.*.approval_no' => 'nullable|string',
                'savings.*.start_hold' => 'nullable|date',
                'savings.*.expiry_date' => 'nullable|date',
                'savings.*.account_status' => 'nullable|in:deduction,non-deduction',
                'savings.*.deduction_amount' => 'nullable|numeric',
                'savings.*.remarks' => 'nullable|string',

                // Shares validation
                'shares' => 'nullable|array',
                'shares.*.account_number' => 'required|string',
                'shares.*.current_balance' => 'required|numeric',
                'shares.*.approval_no' => 'nullable|string',
                'shares.*.start_hold' => 'nullable|date',
                'shares.*.expiry_date' => 'nullable|date',
                'shares.*.account_status' => 'required|in:deduction,non-deduction',
                'shares.*.deduction_amount' => 'nullable|numeric',
                'shares.*.remarks' => 'nullable|string',

                // LoanForecast validation
                'loan_forecasts' => 'nullable|array',
                'loan_forecasts.*.loan_acct_no' => 'required|string',
                'loan_forecasts.*.total_due' => 'required|numeric',
                'loan_forecasts.*.billing_period' => 'nullable|string',
                'loan_forecasts.*.start_hold' => 'nullable|date',
                'loan_forecasts.*.expiry_date' => 'nullable|date',
                'loan_forecasts.*.account_status' => 'required|in:deduction,non-deduction',
                'loan_forecasts.*.approval_no' => 'nullable|string',
                'loan_forecasts.*.remarks' => 'nullable|string',
            ]);

            $member = Member::findOrFail($id);

            DB::beginTransaction();
            try {
                // Update member fields if they exist in the request
                if ($request->except(['savings', 'shares', 'loan_forecasts'])) {
                    $member->update($request->except(['savings', 'shares', 'loan_forecasts']));
                }

                // Update savings accounts
                if ($request->has('savings')) {
                    Log::info('Processing savings updates for member: ' . $member->id);
                    foreach ($request->input('savings') as $index => $savingsData) {
                        Log::info('Processing savings account: ' . json_encode($savingsData));

                        try {
                            $saving = $member->savings()
                                ->where('account_number', $savingsData['account_number'])
                                ->first();

                            if ($saving) {
                                $updateData = [
                                    'current_balance' => $savingsData['current_balance'],
                                    'approval_no' => $savingsData['approval_no'],
                                    'start_hold' => $savingsData['start_hold'],
                                    'expiry_date' => $savingsData['expiry_date'],
                                    'account_status' => $savingsData['account_status'],
                                    'deduction_amount' => $savingsData['deduction_amount'],
                                    'remarks' => $savingsData['remarks'],
                                ];

                                Log::info('Updating savings account with data: ' . json_encode($updateData));
                                $saving->update($updateData);
                                Log::info('Successfully updated savings account: ' . $saving->id);
                            } else {
                                Log::error('Savings account not found: ' . $savingsData['account_number']);
                                throw new \Exception('Savings account not found: ' . $savingsData['account_number']);
                            }
                        } catch (\Exception $e) {
                            Log::error('Error updating savings account: ' . $e->getMessage());
                            throw $e;
                        }
                    }
                }

                // Update shares accounts
                if ($request->has('shares')) {
                    Log::info('Processing shares updates for member: ' . $member->id);
                    foreach ($request->input('shares') as $index => $sharesData) {
                        Log::info('Processing shares account: ' . json_encode($sharesData));

                        try {
                            $share = $member->shares()
                                ->where('account_number', $sharesData['account_number'])
                                ->first();

                            if ($share) {
                                $updateData = [
                                    'current_balance' => $sharesData['current_balance'],
                                    'approval_no' => $sharesData['approval_no'],
                                    'start_hold' => $sharesData['start_hold'],
                                    'expiry_date' => $sharesData['expiry_date'],
                                    'account_status' => $sharesData['account_status'],
                                    'deduction_amount' => $sharesData['deduction_amount'],
                                    'remarks' => $sharesData['remarks'],
                                ];

                                Log::info('Updating shares account with data: ' . json_encode($updateData));
                                $share->update($updateData);
                                Log::info('Successfully updated shares account: ' . $share->id);
                            } else {
                                Log::error('Shares account not found: ' . $sharesData['account_number']);
                                throw new \Exception('Shares account not found: ' . $sharesData['account_number']);
                            }
                        } catch (\Exception $e) {
                            Log::error('Error updating shares account: ' . $e->getMessage());
                            throw $e;
                        }
                    }
                }

                // Update loan forecasts
                if ($request->has('loan_forecasts')) {
                    Log::info('Processing loan forecasts for member: ' . $member->id);
                    foreach ($request->input('loan_forecasts') as $loanData) {
                        Log::info('Processing loan: ' . json_encode($loanData));

                        try {
                            $loan = $member->loanForecasts()
                                ->where('loan_acct_no', $loanData['loan_acct_no'])
                                ->first();

                            if ($loan) {
                                $updateData = [
                                    'total_due' => $loanData['total_due'],
                                    'billing_period' => $loanData['billing_period'] ?? Auth::user()->billing_period,
                                    'start_hold' => $loanData['start_hold'],
                                    'expiry_date' => $loanData['expiry_date'],
                                    'account_status' => $loanData['account_status'],
                                    'approval_no' => $loanData['approval_no'],
                                    'remarks' => $loanData['remarks'],
                                ];

                                Log::info('Updating loan forecast with data: ' . json_encode($updateData));
                                $loan->update($updateData);
                                Log::info('Successfully updated loan forecast: ' . $loan->id);
                            } else {
                                Log::error('Loan forecast not found: ' . $loanData['loan_acct_no']);
                                throw new \Exception('Loan forecast not found: ' . $loanData['loan_acct_no']);
                            }
                        } catch (\Exception $e) {
                            Log::error('Error updating loan forecast: ' . $e->getMessage());
                            throw $e;
                        }
                    }

                    // Recalculate loan balance
                    $now = now();
                    $totalLoanBalance = $member->loanForecasts()
                        ->where(function ($query) use ($now) {
                            $query->where('account_status', 'deduction')
                                ->orWhere(function ($q) use ($now) {
                                    $q->where('account_status', 'non-deduction')
                                        ->where(function ($sq) use ($now) {
                                            $sq->whereNull('start_hold')
                                                ->orWhere('start_hold', '>', $now)
                                                ->orWhere('expiry_date', '<', $now);
                                        });
                                });
                        })
                        ->sum('total_due');

                    Log::info('Recalculated loan balance for member ' . $member->id . ': ' . $totalLoanBalance);
                    $member->update(['loan_balance' => $totalLoanBalance]);
                }

                DB::commit();

                // Return JSON response for AJAX requests
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Member, savings, shares and loan forecast(s) updated successfully!',
                        'member' => $member->fresh()->load(['savings', 'shares', 'loanForecasts'])
                    ]);
                }

                return redirect()->back()->with('success', 'Member, savings, shares and loan forecast(s) updated successfully!');

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Transaction failed: ' . $e->getMessage());

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error updating member data: ' . $e->getMessage()
                    ], 422);
                }

                return redirect()->back()
                    ->with('error', 'Error updating member data: ' . $e->getMessage())
                    ->withInput();
            }
        } catch (\Exception $e) {
            Log::error('Validation or other error: ' . $e->getMessage());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 422);
            }

            return redirect()->back()
                ->with('error', 'Error: ' . $e->getMessage())
                ->withInput();
        }
    }



    public function destroy($id)
    {
        Member::destroy($id);
        return redirect()->back()->with('success', 'Member deleted');
    }

    //branch function

    public function index_branch(Request $request)
    {
        $search = $request->input('search');
        $userBranchId = Auth::user()->branch_id;

        $masterlists = MasterList::with([
            'member.loanForecasts',
            'member.savings',
            'member.shares',
            'member.branch',
            'branch'
        ])
        ->whereHas('member', function($query) use ($userBranchId) {
            $query->where('branch_id', $userBranchId);
        })
        ->when($search, function ($query, $search) {
            $query->whereHas('member', function ($q) use ($search) {
                $q->where('cid', 'like', "%{$search}%")
                    ->orWhere('lname', 'like', "%{$search}%")
                    ->orWhere('fname', 'like', "%{$search}%");
            })
            ->orWhereHas('member.branch', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        })
        ->paginate(25)
        ->appends(['search' => $search]);

        // Format the dates for JSON serialization
        $masterlists->getCollection()->transform(function ($item) {
            $member = $item->member;

            // Format savings dates
            $member->savings->transform(function ($saving) {
                $saving->open_date = $saving->open_date ? $saving->open_date->format('Y-m-d') : null;
                return $saving;
            });

            // Format shares dates
            $member->shares->transform(function ($share) {
                $share->open_date = $share->open_date ? $share->open_date->format('Y-m-d') : null;
                return $share;
            });

            return $item;
        });

        // Only get the branch of the logged-in user
        $branches = Branch::where('id', $userBranchId)->get();

        // Load mortuary products for JavaScript access
        $mortuaryProducts = \App\Models\SavingProduct::where('product_name', 'like', '%mortuary%')
            ->orWhere('product_name', 'like', '%Mortuary%')
            ->get();

        return view('components.branch.master.master', compact('masterlists', 'branches', 'mortuaryProducts'));
    }

   public function store_branch(Request $request)
{
    $request->validate([
        'cid' => 'required|string|unique:members|max:255',
        'emp_id' => 'nullable|string|unique:members|max:255',
        'fname' => 'nullable|string|max:255',
        'lname' => 'nullable|string|max:255',
        'address' => 'nullable|string',
        'savings_balance' => 'nullable|numeric',
        'share_balance' => 'nullable|numeric',
        'loan_balance' => 'nullable|numeric',
        'birth_date' => 'nullable|date',
        'expiry_date' => 'nullable|date',
        'date_registered' => 'nullable|date',
        'gender' => 'nullable|in:male,female,other',
        'customer_type' => 'nullable|string',
        'customer_classification' => 'nullable|string',
        'occupation' => 'nullable|string',
        'industry' => 'nullable|string',
        'area_officer' => 'nullable|string',
        'area' => 'nullable|string',
        'status' => 'nullable|in:active,merged',
        'approval_no' => 'nullable|string',
        'start_hold' => 'nullable|date',
        'account_status' => 'nullable|in:deduction,non-deduction',
        'branch_id' => 'nullable|exists:branches,id',
    ]);

    // Get current billing period from authenticated user
    $billingPeriod = Auth::user()->billing_period;

    if (!$billingPeriod) {
        return redirect()->back()->with('error', 'Please set a billing period in the dashboard first.');
    }

    // Merge the billing period into the request data
    $memberData = array_merge(
        $request->except(['savings_balance', 'share_balance']),
        ['billing_period' => $billingPeriod]
    );

    // Create the member with billing period
    $member = Member::create($memberData);

    if ($request->filled('savings_balance')) {
        $member->savings()->create([
            'account_number' => 'SAV-' . $member->id,
            'open_date' => now(),
            'current_balance' => $request->savings_balance,
        ]);
    }

    if ($request->filled('share_balance')) {
        $member->shares()->create([
            'account_number' => 'SHR-' . $member->id,
            'open_date' => now(),
            'current_balance' => $request->share_balance,
        ]);
    }

    // Create master list entry for the new member
    MasterList::create([
        'member_id' => $member->id,
        'branches_id' => $member->branch_id ?? null,
        'billing_period' => $billingPeriod,
        'status' => 'deduction'
    ]);

    return redirect()->back()->with('success', 'Member successfully added.');
}

    public function update_branch(Request $request, $id)
    {
        try {
            Log::info('Update request received for member ' . $id, [
                'request_data' => $request->all(),
                'content_type' => $request->header('Content-Type'),
                'accept' => $request->header('Accept')
            ]);

            $request->validate([
                // Member validation
                'cid' => 'nullable|string|max:255',
                'emp_id' => 'nullable|string|max:255',
                'fname' => 'nullable|string|max:255',
                'lname' => 'nullable|string|max:255',
                'address' => 'nullable|string',
                'birth_date' => 'nullable|date',
                'expiry_date' => 'nullable|date',
                'date_registered' => 'nullable|date',
                'gender' => 'nullable|string|in:male,female,other',
                'customer_type' => 'nullable|string|max:255',
                'customer_classification' => 'nullable|string|max:255',
                'occupation' => 'nullable|string|max:255',
                'industry' => 'nullable|string|max:255',
                'area_officer' => 'nullable|string|max:255',
                'area' => 'nullable|string|max:255',
                'status' => 'nullable|string|in:active,merged',
                'additional_address' => 'nullable|string',
                'account_status' => 'nullable|string|in:deduction,non-deduction',
                'branch_id' => 'nullable|exists:branches,id',

                // Savings validation
                'savings' => 'nullable|array',
                'savings.*.account_number' => 'required|string',
                'savings.*.current_balance' => 'required|numeric',
                'savings.*.approval_no' => 'nullable|string',
                'savings.*.start_hold' => 'nullable|date',
                'savings.*.expiry_date' => 'nullable|date',
                'savings.*.account_status' => 'nullable|in:deduction,non-deduction',
                'savings.*.deduction_amount' => 'nullable|numeric',
                'savings.*.remarks' => 'nullable|string',

                // Shares validation
                'shares' => 'nullable|array',
                'shares.*.account_number' => 'required|string',
                'shares.*.current_balance' => 'required|numeric',
                'shares.*.approval_no' => 'nullable|string',
                'shares.*.start_hold' => 'nullable|date',
                'shares.*.expiry_date' => 'nullable|date',
                'shares.*.account_status' => 'required|in:deduction,non-deduction',
                'shares.*.deduction_amount' => 'nullable|numeric',
                'shares.*.remarks' => 'nullable|string',

                // LoanForecast validation
                'loan_forecasts' => 'nullable|array',
                'loan_forecasts.*.loan_acct_no' => 'required|string',
                'loan_forecasts.*.amount_due' => 'nullable|numeric',
                'loan_forecasts.*.open_date' => 'nullable|date',
                'loan_forecasts.*.maturity_date' => 'nullable|date',
                'loan_forecasts.*.amortization_due_date' => 'nullable|date',
                'loan_forecasts.*.total_due' => 'required|numeric',
                'loan_forecasts.*.principal_due' => 'nullable|numeric',
                'loan_forecasts.*.interest_due' => 'nullable|numeric',
                'loan_forecasts.*.penalty_due' => 'nullable|numeric',
                'loan_forecasts.*.billing_period' => 'nullable|string',
                'loan_forecasts.*.start_hold' => 'nullable|date',
                'loan_forecasts.*.expiry_date' => 'nullable|date',
                'loan_forecasts.*.account_status' => 'required|in:deduction,non-deduction',
                'loan_forecasts.*.approval_no' => 'nullable|string',
            ]);

            $member = Member::findOrFail($id);

            DB::beginTransaction();
            try {
                // Update member fields if they exist in the request
                if ($request->except(['savings', 'shares', 'loan_forecasts'])) {
                    $member->update($request->except(['savings', 'shares', 'loan_forecasts']));
                }

                // Update savings accounts
                if ($request->has('savings')) {
                    Log::info('Processing savings updates for member: ' . $member->id);
                    foreach ($request->input('savings') as $index => $savingsData) {
                        Log::info('Processing savings account: ' . json_encode($savingsData));

                        try {
                            $saving = $member->savings()
                                ->where('account_number', $savingsData['account_number'])
                                ->first();

                            if ($saving) {
                                $updateData = [
                                    'current_balance' => $savingsData['current_balance'],
                                    'approval_no' => $savingsData['approval_no'],
                                    'start_hold' => $savingsData['start_hold'],
                                    'expiry_date' => $savingsData['expiry_date'],
                                    'account_status' => $savingsData['account_status'],
                                    'deduction_amount' => $savingsData['deduction_amount']
                                ];

                                Log::info('Updating savings account with data: ' . json_encode($updateData));
                                $saving->update($updateData);
                                Log::info('Successfully updated savings account: ' . $saving->id);
                            } else {
                                Log::error('Savings account not found: ' . $savingsData['account_number']);
                                throw new \Exception('Savings account not found: ' . $savingsData['account_number']);
                            }
                        } catch (\Exception $e) {
                            Log::error('Error updating savings account: ' . $e->getMessage());
                            throw $e;
                        }
                    }
                }

                // Update shares accounts
                if ($request->has('shares')) {
                    Log::info('Processing shares updates for member: ' . $member->id);
                    foreach ($request->input('shares') as $index => $sharesData) {
                        Log::info('Processing shares account: ' . json_encode($sharesData));

                        try {
                            $share = $member->shares()
                                ->where('account_number', $sharesData['account_number'])
                                ->first();

                            if ($share) {
                                $updateData = [
                                    'current_balance' => $sharesData['current_balance'],
                                    'approval_no' => $sharesData['approval_no'],
                                    'start_hold' => $sharesData['start_hold'],
                                    'expiry_date' => $sharesData['expiry_date'],
                                    'account_status' => $sharesData['account_status'],
                                    'deduction_amount' => $sharesData['deduction_amount']
                                ];

                                Log::info('Updating shares account with data: ' . json_encode($updateData));
                                $share->update($updateData);
                                Log::info('Successfully updated shares account: ' . $share->id);
                            } else {
                                Log::error('Shares account not found: ' . $sharesData['account_number']);
                                throw new \Exception('Shares account not found: ' . $sharesData['account_number']);
                            }
                        } catch (\Exception $e) {
                            Log::error('Error updating shares account: ' . $e->getMessage());
                            throw $e;
                        }
                    }
                }

                // Update loan forecasts
                if ($request->has('loan_forecasts')) {
                    Log::info('Processing loan forecasts for member: ' . $member->id);
                    foreach ($request->input('loan_forecasts') as $loanData) {
                        Log::info('Processing loan: ' . json_encode($loanData));

                        try {
                            $loan = $member->loanForecasts()
                                ->where('loan_acct_no', $loanData['loan_acct_no'])
                                ->first();

                            if ($loan) {
                                $updateData = [
                                    'amount_due' => $loanData['amount_due'],
                                    'open_date' => $loanData['open_date'],
                                    'maturity_date' => $loanData['maturity_date'],
                                    'amortization_due_date' => $loanData['amortization_due_date'],
                                    'total_due' => $loanData['total_due'],
                                    'principal_due' => $loanData['principal_due'],
                                    'interest_due' => $loanData['interest_due'],
                                    'penalty_due' => $loanData['penalty_due'],
                                    'billing_period' => $loanData['billing_period'] ?? Auth::user()->billing_period,
                                    'start_hold' => $loanData['start_hold'],
                                    'expiry_date' => $loanData['expiry_date'],
                                    'account_status' => $loanData['account_status'],
                                    'approval_no' => $loanData['approval_no']
                                ];

                                Log::info('Updating loan forecast with data: ' . json_encode($updateData));
                                $loan->update($updateData);
                                Log::info('Successfully updated loan forecast: ' . $loan->id);
                            } else {
                                Log::error('Loan forecast not found: ' . $loanData['loan_acct_no']);
                                throw new \Exception('Loan forecast not found: ' . $loanData['loan_acct_no']);
                            }
                        } catch (\Exception $e) {
                            Log::error('Error updating loan forecast: ' . $e->getMessage());
                            throw $e;
                        }
                    }

                    // Recalculate loan balance
                    $now = now();
                    $totalLoanBalance = $member->loanForecasts()
                        ->where(function ($query) use ($now) {
                            $query->where('account_status', 'deduction')
                                ->orWhere(function ($q) use ($now) {
                                    $q->where('account_status', 'non-deduction')
                                        ->where(function ($sq) use ($now) {
                                            $sq->whereNull('start_hold')
                                                ->orWhere('start_hold', '>', $now)
                                                ->orWhere('expiry_date', '<', $now);
                                        });
                                });
                        })
                        ->sum('total_due');

                    Log::info('Recalculated loan balance for member ' . $member->id . ': ' . $totalLoanBalance);
                    $member->update(['loan_balance' => $totalLoanBalance]);
                }

                DB::commit();

                // Return JSON response for AJAX requests
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Member, savings, shares and loan forecast(s) updated successfully!',
                        'member' => $member->fresh()->load(['savings', 'shares', 'loanForecasts'])
                    ]);
                }

                return redirect()->back()->with('success', 'Member, savings, shares and loan forecast(s) updated successfully!');

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Transaction failed: ' . $e->getMessage());

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error updating member data: ' . $e->getMessage()
                    ], 422);
                }

                return redirect()->back()
                    ->with('error', 'Error updating member data: ' . $e->getMessage())
                    ->withInput();
            }
        } catch (\Exception $e) {
            Log::error('Validation or other error: ' . $e->getMessage());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 422);
            }

            return redirect()->back()
                ->with('error', 'Error: ' . $e->getMessage())
                ->withInput();
        }
    }



    public function destroy_branch($id)
    {
        Member::destroy($id);
        return redirect()->back()->with('success', 'Member deleted');
    }

}
