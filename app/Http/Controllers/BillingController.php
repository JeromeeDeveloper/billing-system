<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use App\Exports\BillingExport;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function index(Request $request)
    {

        $billingPeriod = auth()->user()->billing_period;
        $search = $request->input('search');
        $perPage = $request->input('perPage', 10);

        // Validate perPage input - allow only these options
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

         $allBranchApproved = User::where('role', 'branch')
        ->where('status', '!=', 'approved')
        ->doesntExist(); // true if all are approved

        // Query with eager loading branch to avoid N+1 query problem
        $query = Member::with('branch')
            ->where('billing_period', $billingPeriod);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('emp_id', 'like', "%{$search}%")
                    ->orWhere('fname', 'like', "%{$search}%")
                    ->orWhere('lname', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%")
                    ->orWhereHas('branch', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $billing = $query->paginate($perPage)->appends([
            'search' => $search,
            'perPage' => $perPage,
        ]);

        return view('components.admin.billing.billing', compact('billing', 'search', 'perPage', 'allBranchApproved'));
    }

    public function index_branch(Request $request)
    {
        $billingPeriod = Auth::user()->billing_period;
        $userBranchId = Auth::user()->branch_id;
        $search = $request->input('search');
        $perPage = $request->input('perPage', 10);

        // Validate perPage input - allow only these options
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

         $allBranchApproved = User::where('role', 'branch')
        ->where('status', '!=', 'approved')
        ->doesntExist(); // true if all are approved

        // Query with eager loading branch to avoid N+1 query problem
        $query = Member::with('branch')
            ->where('billing_period', $billingPeriod)
            ->where('branch_id', $userBranchId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('emp_id', 'like', "%{$search}%")
                    ->orWhere('fname', 'like', "%{$search}%")
                    ->orWhere('lname', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%")
                    ->orWhereHas('branch', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $billing = $query->paginate($perPage)->appends([
            'search' => $search,
            'perPage' => $perPage,
        ]);

        return view('components.branch.billing.billing', compact('billing', 'search', 'perPage', 'allBranchApproved'));
    }

    public function export(Request $request)
    {
        $billingPeriod = $request->input('billing_period', now()->format('Y-m'));
        return Excel::download(new BillingExport($billingPeriod), 'billing_export.xlsx');
    }

    public function export_branch(Request $request)
    {
        $billingPeriod = $request->input('billing_period', now()->format('Y-m'));
        return Excel::download(new BillingExport($billingPeriod), 'billing_export.xlsx');
    }

    public function update(Request $request, Member $member)
    {
        $request->validate([
            'emp_id' => 'nullable|string|max:255',
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'loan_balance' => 'nullable|numeric',
            'principal' => 'nullable|numeric',
        ]);

        $member->update($request->only(['emp_id', 'fname', 'lname', 'loan_balance', 'principal']));

        return redirect()->back()->with('success', 'Member updated successfully!');
    }

    public function update_branch(Request $request, Member $member)
    {
        $request->validate([
            'emp_id' => 'nullable|string|max:255',
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'loan_balance' => 'nullable|numeric',
            'principal' => 'nullable|numeric',
        ]);

        $member->update($request->only(['emp_id', 'fname', 'lname', 'loan_balance', 'principal']));

        return redirect()->back()->with('success', 'Member updated successfully!');
    }

    public function destroy(Member $member)
    {
        $member->delete();
        return redirect()->back()->with('success', 'Member deleted successfully!');
    }

    public function destroy_branch(Member $member)
    {
        $member->delete();
        return redirect()->back()->with('success', 'Member deleted successfully!');
    }

    public function approve()
    {
        $user = Auth::user();

        if ($user->status === 'approved') {
            return back()->with('info', 'You are already approved.');
        }

        $user->status = 'approved';
        $user->save();

        return back()->with('success', 'Billing approved successfully.');
    }
}
