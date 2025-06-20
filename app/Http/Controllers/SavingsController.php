<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Saving;
use Illuminate\Http\Request;
use App\Models\SavingProduct;
use Illuminate\Support\Facades\Auth;

class SavingsController extends Controller
{
    public function index(Request $request)
    {
        $billingPeriod = Auth::user()->billing_period;
        $search = $request->input('search');

        // Get unique product codes with their member counts
        $productCounts = Saving::select('product_code')
            ->selectRaw('COUNT(*) as member_count')
            ->groupBy('product_code')
            ->pluck('member_count', 'product_code');

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
            ->get()
            ->map(function ($saving) use ($productCounts) {
                $saving->member_count = $productCounts[$saving->product_code] ?? 0;
                return $saving;
            });

        $members = Member::where('billing_period', $billingPeriod)->get();

        return view('components.admin.savings.savings_datatable', compact('savings', 'members'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'account_number' => 'required|string|unique:savings',
            'product_code' => 'required|exists:saving_products,product_code',
            'product_name' => 'required|string',
            'open_date' => 'required|date',
            'current_balance' => 'required|numeric|min:0',
            'available_balance' => 'nullable|numeric|min:0',
            'interest' => 'nullable|numeric',
            'amount_to_deduct' => 'nullable|numeric',
            'priotization' => 'nullable|string',
            'deduction_type' => 'nullable|string',
        ]);

        // Get product details
        $product = SavingProduct::where('product_code', $request->product_code)->first();

        // Create saving with product details
        $data = $request->all();
        $data['product_name'] = $product->product_name;
        $data['interest'] = $product->interest;

        Saving::create($data);

        return redirect()->back()->with('success', 'Savings account created successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'account_number' => 'required|string|unique:savings,account_number,' . $id,
            'product_code' => 'required|exists:saving_products,product_code',
            'product_name' => 'required|string',
            'open_date' => 'required|date',
            'current_balance' => 'required|numeric|min:0',
            'available_balance' => 'nullable|numeric|min:0',
            'interest' => 'nullable|numeric',
            'amount_to_deduct' => 'nullable|numeric',
            'priotization' => 'nullable|string',
            'deduction_type' => 'nullable|string',
        ]);

        $saving = Saving::findOrFail($id);

        // Get product details
        $product = SavingProduct::where('product_code', $request->product_code)->first();

        // Update saving with product details
        $data = $request->all();
        $data['product_name'] = $product->product_name;
        $data['interest'] = $product->interest;

        $saving->update($data);

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
