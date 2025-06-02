<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Shares;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SharesController extends Controller
{
    public function index(Request $request)
    {
        $billingPeriod = Auth::user()->billing_period;
        $search = $request->input('search');

        $shares = Shares::with(['member'])
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

        // Create dummy share products data based on shares table structure
        $share_products = collect([
            (object)[
                'id' => 1,
                'product_name' => 'Regular Share',
                'product_code' => 'SHR-REG',
                'account_number' => 'SHR-001',
                'current_balance' => 1000.00,
                'available_balance' => 1000.00,
                'interest' => 0.00,
                'open_date' => '2024-01-01'
            ],
            (object)[
                'id' => 2,
                'product_name' => 'Premium Share',
                'product_code' => 'SHR-PRE',
                'account_number' => 'SHR-002',
                'current_balance' => 5000.00,
                'available_balance' => 5000.00,
                'interest' => 0.00,
                'open_date' => '2024-01-01'
            ]
        ]);

        return view('components.admin.shares.shares_datatable', compact('shares', 'members', 'share_products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'account_number' => 'required|string|unique:shares',
            'product_code' => 'nullable|string',
            'product_name' => 'nullable|string',
            'open_date' => 'required|date',
            'current_balance' => 'required|numeric|min:0',
            'available_balance' => 'nullable|numeric|min:0',
            'interest' => 'nullable|numeric',
        ]);

        Shares::create($request->all());

        return redirect()->back()->with('success', 'Share account created successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'account_number' => 'required|string|unique:shares,account_number,' . $id,
            'product_code' => 'nullable|string',
            'product_name' => 'nullable|string',
            'open_date' => 'required|date',
            'current_balance' => 'required|numeric|min:0',
            'available_balance' => 'nullable|numeric|min:0',
            'interest' => 'nullable|numeric',
        ]);

        $share = Shares::findOrFail($id);
        $share->update($request->all());

        return redirect()->back()->with('success', 'Share account updated successfully');
    }

    public function destroy($id)
    {
        Shares::destroy($id);
        return redirect()->back()->with('success', 'Share account deleted successfully');
    }

    public function index_branch(Request $request)
    {
        $billingPeriod = Auth::user()->billing_period;
        $branchId = Auth::user()->branch_id;
        $search = $request->input('search');

        $shares = Shares::with(['member'])
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

        return view('components.branch.shares.shares_datatable', compact('shares'));
    }
}
