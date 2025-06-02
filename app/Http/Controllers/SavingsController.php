<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Saving;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavingsController extends Controller
{
    public function index(Request $request)
    {
        $billingPeriod = Auth::user()->billing_period;
        $search = $request->input('search');

        $savings = Saving::with(['member'])
            ->whereHas('member', function($query) use ($billingPeriod) {
                $query->where('billing_period', $billingPeriod);
            })
            ->when($search, function($query, $search) {
                $query->where('account_number', 'like', "%{$search}%")
                    ->orWhereHas('member', function($q) use ($search) {
                        $q->where('cid', 'like', "%{$search}%")
                            ->orWhere('fname', 'like', "%{$search}%")
                            ->orWhere('lname', 'like', "%{$search}%");
                    });
            })
            ->paginate(25)
            ->appends(['search' => $search]);

        $members = Member::where('billing_period', $billingPeriod)->get();

        // Create dummy savings products data based on savings table structure
        $savings_products = collect([
            (object)[
                'id' => 1,
                'product_name' => 'Regular Savings',
                'product_code' => 'SAV-REG',
                'account_number' => 'SAV-001',
                'current_balance' => 500.00,
                'available_balance' => 500.00,
                'interest' => 2.50,
                'open_date' => '2024-01-01'
            ],
            (object)[
                'id' => 2,
                'product_name' => 'Time Deposit',
                'product_code' => 'SAV-TD',
                'account_number' => 'SAV-002',
                'current_balance' => 10000.00,
                'available_balance' => 10000.00,
                'interest' => 4.00,
                'open_date' => '2024-01-01'
            ]
        ]);

        return view('components.admin.savings.savings_datatable', compact('savings', 'members', 'savings_products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'account_number' => 'required|string|unique:savings',
            'product_code' => 'nullable|string',
            'product_name' => 'nullable|string',
            'open_date' => 'required|date',
            'current_balance' => 'required|numeric|min:0',
            'available_balance' => 'nullable|numeric|min:0',
            'interest' => 'nullable|numeric',
        ]);

        Saving::create($request->all());

        return redirect()->back()->with('success', 'Savings account created successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'account_number' => 'required|string|unique:savings,account_number,' . $id,
            'product_code' => 'nullable|string',
            'product_name' => 'nullable|string',
            'open_date' => 'required|date',
            'current_balance' => 'required|numeric|min:0',
            'available_balance' => 'nullable|numeric|min:0',
            'interest' => 'nullable|numeric',
        ]);

        $saving = Saving::findOrFail($id);
        $saving->update($request->all());

        return redirect()->back()->with('success', 'Savings account updated successfully');
    }

    public function destroy($id)
    {
        Saving::destroy($id);
        return redirect()->back()->with('success', 'Savings account deleted successfully');
    }

    public function index_branch(Request $request)
    {
        $billingPeriod = Auth::user()->billing_period;
        $branchId = Auth::user()->branch_id;
        $search = $request->input('search');

        $savings = Saving::with(['member'])
            ->whereHas('member', function($query) use ($billingPeriod, $branchId) {
                $query->where('billing_period', $billingPeriod)
                      ->where('branch_id', $branchId);
            })
            ->when($search, function($query, $search) {
                $query->where('account_number', 'like', "%{$search}%")
                    ->orWhereHas('member', function($q) use ($search) {
                        $q->where('cid', 'like', "%{$search}%")
                            ->orWhere('fname', 'like', "%{$search}%")
                            ->orWhere('lname', 'like', "%{$search}%");
                    });
            })
            ->paginate(25)
            ->appends(['search' => $search]);

        return view('components.branch.savings.savings_datatable', compact('savings'));
    }
}
