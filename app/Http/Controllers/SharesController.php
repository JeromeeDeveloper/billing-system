<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Shares;
use App\Models\ShareProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SharesController extends Controller
{
    public function index(Request $request)
    {
        $billingPeriod = Auth::user()->billing_period;
        $search = $request->input('search');

        // Get unique product codes with their member counts
        $productCounts = Shares::select('product_code')
            ->selectRaw('COUNT(*) as member_count')
            ->groupBy('product_code')
            ->pluck('member_count', 'product_code');

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
            ->get()
            ->map(function ($share) use ($productCounts) {
                $share->member_count = $productCounts[$share->product_code] ?? 0;
                return $share;
            });

        $members = Member::where('billing_period', $billingPeriod)->get();

        return view('components.admin.shares.shares_datatable', compact('shares', 'members'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'account_number' => 'required|string|unique:shares',
            'product_code' => 'required|exists:share_products,product_code',
            'product_name' => 'required|string',
            'open_date' => 'required|date',
            'current_balance' => 'required|numeric|min:0',
            'available_balance' => 'nullable|numeric|min:0',
            'interest' => 'nullable|numeric',
        ]);

        // Get product details
        $product = ShareProduct::where('product_code', $request->product_code)->first();

        // Create share with product details
        $data = $request->all();
        $data['product_name'] = $product->product_name;
        $data['interest'] = $product->interest;

        Shares::create($data);

        return redirect()->back()->with('success', 'Share account created successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'account_number' => 'required|string|unique:shares,account_number,' . $id,
            'product_code' => 'required|exists:share_products,product_code',
            'product_name' => 'required|string',
            'open_date' => 'required|date',
            'current_balance' => 'required|numeric|min:0',
            'available_balance' => 'nullable|numeric|min:0',
            'interest' => 'nullable|numeric',
        ]);

        $share = Shares::findOrFail($id);

        // Get product details
        $product = ShareProduct::where('product_code', $request->product_code)->first();

        // Update share with product details
        $data = $request->all();
        $data['product_name'] = $product->product_name;
        $data['interest'] = $product->interest;

        $share->update($data);

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
