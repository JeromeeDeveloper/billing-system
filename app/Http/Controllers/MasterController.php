<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Member;
use App\Models\MasterList;
use Illuminate\Http\Request;

class MasterController extends Controller
{

    public function index(Request $request)
    {
        $billingPeriod = auth()->user()->billing_period;
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

        return view('components.admin.master.master', compact('masterlists', 'branches'));
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
            foreach ($request->input('loan_forecasts') as $loanData) {
                $member->loanForecasts()->updateOrCreate(
                    [
                        'member_id' => $member->id,
                        'loan_acct_no' => $loanData['loan_acct_no'],
                    ],
                    $loanData
                );
            }
        }

        return redirect()->back()->with('success', 'Member and loan forecast(s) updated successfully!');
    }




    public function destroy($id)
    {
        Member::destroy($id);
        return redirect()->back()->with('success', 'Member deleted');
    }
}
