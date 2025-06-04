<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Member;
use App\Models\MasterList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MasterController extends Controller
{

    public function index(Request $request)
    {

        $search = $request->input('search');

        $masterlists = MasterList::with([
            'member.loanForecasts',
            'member.savings',
            'member.shares',
            'branch'
        ])

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

        $branches = Branch::all();

        return view('components.admin.master.master', compact('masterlists', 'branches'));
    }

   public function store(Request $request)
{
    $request->validate([
        'cid' => 'required|string|unique:members|max:255',
        'emp_id' => 'nullable|string|unique:members|max:255',
        'fname' => 'required|string|max:255',
        'lname' => 'required|string|max:255',
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
        'branch_id' => 'required|exists:branches,id',
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
        'branches_id' => $member->branch_id,
        'billing_period' => $billingPeriod,
        'status' => 'deduction'
    ]);

    return redirect()->back()->with('success', 'Member successfully added.');
}


     public function index_branch(Request $request)
    {
        $billingPeriod = Auth::user()->billing_period;
        $search = $request->input('search');

        $masterlists = MasterList::with([
            'member.loanForecasts',
            'member.savings',
            'member.shares',
            'branch'
        ])

            ->where('billing_period', $billingPeriod)
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
            ->appends(['search' => $search]);



        $branches = Branch::all();

        return view('components.branch.master.master', compact('masterlists', 'branches'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            // Member validation
            'cid' => 'required|string|max:255',
            'emp_id' => 'nullable|string|max:255',
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'address' => 'nullable|string',
            'savings_balance' => 'nullable|numeric',
            'share_balance' => 'nullable|numeric',
            'loan_balance' => 'nullable|numeric',
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

            // LoanForecast validation example (optional, adjust keys as per your form input)
            'loan_forecasts' => 'nullable|array',
            'loan_forecasts.*.loan_acct_no' => 'required|string',
            'loan_forecasts.*.amount_due' => 'required|numeric',
            'loan_forecasts.*.open_date' => 'required|date',
            'loan_forecasts.*.maturity_date' => 'required|date',
            'loan_forecasts.*.amortization_due_date' => 'required|date',
            'loan_forecasts.*.total_due' => 'required|numeric',
            'loan_forecasts.*.principal_due' => 'required|numeric',
            'loan_forecasts.*.interest_due' => 'required|numeric',
            'loan_forecasts.*.penalty_due' => 'required|numeric',
            'loan_forecasts.*.billing_period' => 'nullable|string',
            'loan_forecasts.*.start_hold' => 'nullable|date',
            'loan_forecasts.*.expiry_date' => 'nullable|date',
            'loan_forecasts.*.account_status' => 'required|in:deduction,non-deduction',
            'loan_forecasts.*.approval_no' => 'nullable|string',
        ]);

        $member = Member::findOrFail($id);

        // Update member fields EXCEPT balances (remove 'savings_balance' and 'share_balance')
        $member->update($request->except(['savings_balance', 'share_balance']));

        // Update savings balance if provided
        if ($request->filled('savings_balance')) {
            $savings = $member->savings()->first();
            if ($savings) {
                $savings->update(['current_balance' => $request->input('savings_balance')]);
            }
        }

        // Update shares balance if provided
        if ($request->filled('share_balance')) {
            $shares = $member->shares()->first();
            if ($shares) {
                $shares->update(['current_balance' => $request->input('share_balance')]);
            }
        }

        // Update or create loan forecasts if present
        if ($request->has('loan_forecasts')) {
            \Log::info('Updating loan forecasts for member: ' . $member->id);

            foreach ($request->input('loan_forecasts') as $loanData) {
                \Log::info('Processing loan: ' . json_encode($loanData));

                // Check if loan should switch back to deduction based on expiry date
                if (isset($loanData['account_status']) &&
                    $loanData['account_status'] === 'non-deduction' &&
                    !empty($loanData['expiry_date']) &&
                    strtotime($loanData['expiry_date']) < time()) {
                    $loanData['account_status'] = 'deduction';
                    \Log::info('Loan switched to deduction due to expired date');
                }

                // Ensure all fields are included in the update
                $loanForecastData = [
                    'loan_acct_no' => $loanData['loan_acct_no'],
                    'amount_due' => $loanData['amount_due'],
                    'open_date' => $loanData['open_date'],
                    'maturity_date' => $loanData['maturity_date'],
                    'amortization_due_date' => $loanData['amortization_due_date'],
                    'total_due' => $loanData['total_due'],
                    'principal_due' => $loanData['principal_due'],
                    'interest_due' => $loanData['interest_due'],
                    'penalty_due' => $loanData['penalty_due'],
                    'billing_period' => $loanData['billing_period'] ?? Auth::user()->billing_period,
                    'start_hold' => $loanData['start_hold'] ?? null,
                    'expiry_date' => $loanData['expiry_date'] ?? null,
                    'account_status' => $loanData['account_status'],
                    'approval_no' => $loanData['approval_no'] ?? null,
                ];

                \Log::info('Updating loan forecast with data: ' . json_encode($loanForecastData));

                try {
                    $result = $member->loanForecasts()->updateOrCreate(
                        [
                            'member_id' => $member->id,
                            'loan_acct_no' => $loanData['loan_acct_no'],
                        ],
                        $loanForecastData
                    );
                    \Log::info('Loan forecast updated successfully: ' . $result->id);
                } catch (\Exception $e) {
                    \Log::error('Error updating loan forecast: ' . $e->getMessage());
                    return redirect()->back()->with('error', 'Error updating loan: ' . $e->getMessage());
                }
            }

            // Recalculate loan balance excluding non-deduction loans within hold period
            $now = now();
            $loanBalance = $member->loanForecasts()
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

            \Log::info('Recalculated loan balance: ' . $loanBalance);
            $member->update(['loan_balance' => $loanBalance]);
        }

        return redirect()->back()->with('success', 'Member and loan forecast(s) updated successfully!');
    }

     public function update_branch(Request $request, $id)
    {
        $request->validate([
            // Member validation
            'cid' => 'required|string|max:255',
            'emp_id' => 'nullable|string|max:255',
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'address' => 'nullable|string',
            'savings_balance' => 'nullable|numeric',
            'share_balance' => 'nullable|numeric',
            'loan_balance' => 'nullable|numeric',
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

            // LoanForecast validation example (optional, adjust keys as per your form input)
            'loan_forecasts' => 'nullable|array',
            'loan_forecasts.*.loan_acct_no' => 'required|string',
            'loan_forecasts.*.amount_due' => 'required|numeric',
            'loan_forecasts.*.open_date' => 'required|date',
            'loan_forecasts.*.maturity_date' => 'required|date',
            'loan_forecasts.*.amortization_due_date' => 'required|date',
            'loan_forecasts.*.total_due' => 'required|numeric',
            'loan_forecasts.*.principal_due' => 'required|numeric',
            'loan_forecasts.*.interest_due' => 'required|numeric',
            'loan_forecasts.*.penalty_due' => 'required|numeric',
            'loan_forecasts.*.billing_period' => 'nullable|string',
            'loan_forecasts.*.start_hold' => 'nullable|date',
            'loan_forecasts.*.expiry_date' => 'nullable|date',
            'loan_forecasts.*.account_status' => 'required|in:deduction,non-deduction',
            'loan_forecasts.*.approval_no' => 'nullable|string',
        ]);

        $member = Member::findOrFail($id);

        // Update member fields EXCEPT balances (remove 'savings_balance' and 'share_balance')
        $member->update($request->except(['savings_balance', 'share_balance']));

        // Update savings balance if provided
        if ($request->filled('savings_balance')) {
            $savings = $member->savings()->first();
            if ($savings) {
                $savings->update(['current_balance' => $request->input('savings_balance')]);
            }
        }

        // Update shares balance if provided
        if ($request->filled('share_balance')) {
            $shares = $member->shares()->first();
            if ($shares) {
                $shares->update(['current_balance' => $request->input('share_balance')]);
            }
        }

        // Update or create loan forecasts if present
        if ($request->has('loan_forecasts')) {
            \Log::info('Updating loan forecasts for member: ' . $member->id);

            foreach ($request->input('loan_forecasts') as $loanData) {
                \Log::info('Processing loan: ' . json_encode($loanData));

                // Check if loan should switch back to deduction based on expiry date
                if (isset($loanData['account_status']) &&
                    $loanData['account_status'] === 'non-deduction' &&
                    !empty($loanData['expiry_date']) &&
                    strtotime($loanData['expiry_date']) < time()) {
                    $loanData['account_status'] = 'deduction';
                    \Log::info('Loan switched to deduction due to expired date');
                }

                // Ensure all fields are included in the update
                $loanForecastData = [
                    'loan_acct_no' => $loanData['loan_acct_no'],
                    'amount_due' => $loanData['amount_due'],
                    'open_date' => $loanData['open_date'],
                    'maturity_date' => $loanData['maturity_date'],
                    'amortization_due_date' => $loanData['amortization_due_date'],
                    'total_due' => $loanData['total_due'],
                    'principal_due' => $loanData['principal_due'],
                    'interest_due' => $loanData['interest_due'],
                    'penalty_due' => $loanData['penalty_due'],
                    'billing_period' => $loanData['billing_period'] ?? Auth::user()->billing_period,
                    'start_hold' => $loanData['start_hold'] ?? null,
                    'expiry_date' => $loanData['expiry_date'] ?? null,
                    'account_status' => $loanData['account_status'],
                    'approval_no' => $loanData['approval_no'] ?? null,
                ];

                \Log::info('Updating loan forecast with data: ' . json_encode($loanForecastData));

                try {
                    $result = $member->loanForecasts()->updateOrCreate(
                        [
                            'member_id' => $member->id,
                            'loan_acct_no' => $loanData['loan_acct_no'],
                        ],
                        $loanForecastData
                    );
                    \Log::info('Loan forecast updated successfully: ' . $result->id);
                } catch (\Exception $e) {
                    \Log::error('Error updating loan forecast: ' . $e->getMessage());
                    return redirect()->back()->with('error', 'Error updating loan: ' . $e->getMessage());
                }
            }

            // Recalculate loan balance excluding non-deduction loans within hold period
            $now = now();
            $loanBalance = $member->loanForecasts()
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

            \Log::info('Recalculated loan balance: ' . $loanBalance);
            $member->update(['loan_balance' => $loanBalance]);
        }

        return redirect()->back()->with('success', 'Member and loan forecast(s) updated successfully!');
    }

    public function destroy($id)
    {
        Member::destroy($id);
        return redirect()->back()->with('success', 'Member deleted');
    }

     public function destroy_branch($id)
    {
        Member::destroy($id);
        return redirect()->back()->with('success', 'Member deleted');
    }
}
