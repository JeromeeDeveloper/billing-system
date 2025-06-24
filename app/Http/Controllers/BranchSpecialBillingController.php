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
        $query = SpecialBilling::query();
        // If SpecialBilling has a member relationship, filter by member's branch_id
        $query->whereHas('member', function($q) use ($branch_id) {
            $q->where('branch_id', $branch_id);
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

    public function import(Request $request)
    {
        // You can implement branch-specific import logic here if needed
        return back()->with('error', 'Branch import not implemented yet.');
    }

    public function export()
    {
        $branch_id = Auth::user()->branch_id;
        return Excel::download(new BranchSpecialBillingExport($branch_id), 'special_billing_branch_export_' . now()->format('Y-m-d') . '.xlsx');
    }
}
