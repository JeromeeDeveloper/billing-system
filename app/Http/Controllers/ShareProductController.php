<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\ShareProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShareProductController extends Controller
{
    public function index()
    {
        $shareProducts = ShareProduct::with('members')->get();
        return view('components.admin.shares.share_products', compact('shareProducts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string',
            'product_code' => 'required|string|unique:share_products',
            'amount_to_deduct' => 'nullable|numeric|min:0',
            'prioritization' => 'nullable|integer'
        ]);

        $shareProduct = ShareProduct::create($request->all());

        // If amount_to_deduct is set, update all related member share accounts
        if ($request->filled('amount_to_deduct')) {
            \App\Models\Shares::where('product_code', $shareProduct->product_code)
                ->update([
                    'deduction_amount' => $request->amount_to_deduct,
                    'account_status' => 'deduction',
                ]);
        }

        return redirect()->back()->with('success', 'Share product created successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'product_name' => 'required|string',
            'product_code' => 'required|string|unique:share_products,product_code,' . $id,
            'amount_to_deduct' => 'nullable|numeric|min:0',
            'prioritization' => 'nullable|integer'
        ]);

        $shareProduct = ShareProduct::findOrFail($id);
        $oldAmountToDeduct = $shareProduct->amount_to_deduct;
        $shareProduct->update($request->all());

        // If amount_to_deduct is changed, update all related member share accounts
        if ($request->has('amount_to_deduct') && $request->amount_to_deduct != $oldAmountToDeduct) {
            $updatedCount = \App\Models\Shares::where('product_code', $shareProduct->product_code)
                ->update([
                    'deduction_amount' => $request->amount_to_deduct,
                    'account_status' => 'deduction',
                ]);
            $message = 'Share product updated successfully';
            if ($updatedCount > 0) {
                $message .= " and deduction amount updated for {$updatedCount} share account(s)";
            }
            return redirect()->back()->with('success', $message);
        }

        return redirect()->back()->with('success', 'Share product updated successfully');
    }

    public function destroy($id)
    {
        ShareProduct::destroy($id);
        return redirect()->back()->with('success', 'Share product deleted successfully');
    }

    public function assignMember(Request $request, $id)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'account_number' => 'required|string|unique:share_product_member',
            'current_balance' => 'required|numeric|min:0',
            'available_balance' => 'nullable|numeric|min:0',
            'interest' => 'nullable|numeric',
            'open_date' => 'required|date'
        ]);

        $shareProduct = ShareProduct::findOrFail($id);
        $shareProduct->members()->attach($request->member_id, [
            'account_number' => $request->account_number,
            'current_balance' => $request->current_balance,
            'available_balance' => $request->available_balance ?? $request->current_balance,
            'interest' => $request->interest ?? 0,
            'open_date' => $request->open_date
        ]);

        return redirect()->back()->with('success', 'Member assigned to share product successfully');
    }
}
