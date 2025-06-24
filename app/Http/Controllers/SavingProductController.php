<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\SavingProduct;
use App\Models\Savings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavingProductController extends Controller
{
    public function index()
    {
        $savingProducts = SavingProduct::with('members')->get();
        return view('components.admin.savings.saving_products', compact('savingProducts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string',
            'product_code' => 'required|string|unique:saving_products',
            'amount_to_deduct' => 'nullable|numeric|min:0',
            'prioritization' => 'nullable|integer|min:1'
        ]);

        SavingProduct::create($request->all());
        return redirect()->back()->with('success', 'Saving product created successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'product_name' => 'required|string',
            'product_code' => 'required|string|unique:saving_products,product_code,' . $id,
            'amount_to_deduct' => 'nullable|numeric|min:0',
            'prioritization' => 'nullable|integer|min:1'
        ]);

        $savingProduct = SavingProduct::findOrFail($id);
        $oldAmountToDeduct = $savingProduct->amount_to_deduct;

        $savingProduct->update($request->all());

        // Update deduction_amount in all related Savings records
        if ($request->has('amount_to_deduct') && $request->amount_to_deduct != $oldAmountToDeduct) {
            $updatedCount = Savings::where('product_code', $savingProduct->product_code)
                ->update(['deduction_amount' => $request->amount_to_deduct]);

            $message = 'Saving product updated successfully';
            if ($updatedCount > 0) {
                $message .= " and deduction amount updated for {$updatedCount} savings account(s)";
            }

            return redirect()->back()->with('success', $message);
        }

        return redirect()->back()->with('success', 'Saving product updated successfully');
    }

    public function destroy($id)
    {
        SavingProduct::destroy($id);
        return redirect()->back()->with('success', 'Saving product deleted successfully');
    }

    public function assignMember(Request $request, $id)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'account_number' => 'required|string|unique:saving_product_member',
            'current_balance' => 'required|numeric|min:0',
            'available_balance' => 'nullable|numeric|min:0',
            'interest' => 'nullable|numeric',
            'open_date' => 'required|date'
        ]);

        $savingProduct = SavingProduct::findOrFail($id);
        $savingProduct->members()->attach($request->member_id, [
            'account_number' => $request->account_number,
            'current_balance' => $request->current_balance,
            'available_balance' => $request->available_balance ?? $request->current_balance,
            'interest' => $request->interest ?? 0,
            'open_date' => $request->open_date
        ]);

        return redirect()->back()->with('success', 'Member assigned to saving product successfully');
    }
}
