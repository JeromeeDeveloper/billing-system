<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SpecialBilling;
use App\Exports\BranchSpecialBillingExport;
use Maatwebsite\Excel\Facades\Excel;

class BranchSpecialBillingController extends Controller
{
    public function index(Request $request)
    {
        $branch_id = Auth::user()->branch_id;
        $billingPeriod = Auth::user()->billing_period;

        $query = SpecialBilling::query()
            ->with('member');

        // Filter by member's branch_id
        $query->whereHas('member', function($q) use ($branch_id) {
            $q->where('branch_id', $branch_id);
        });

        // Filter by billing_period
        $query->whereHas('member', function($q) use ($billingPeriod) {
            $q->where('billing_period', 'like', $billingPeriod . '%');
        });

        // Add search if needed
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('employee_id', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%");
            });
        }
        $specialBillings = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('components.branch.special_billing', compact('specialBillings'));
    }

    public function export()
    {
        $branch_id = Auth::user()->branch_id;
        return Excel::download(new BranchSpecialBillingExport($branch_id), 'special_billing_branch_export_' . now()->format('Y-m-d') . '.csv');
    }
}
