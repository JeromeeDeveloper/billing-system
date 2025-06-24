<?php

namespace App\Http\Controllers;

use App\Models\LoanProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoansController extends Controller
{
    public function index(Request $request)
    {
        $loans = LoanProduct::all();
        return view('components.admin.loans.loans_datatable', compact('loans'));
    }


    public function list()
    {
        // Eager load members for each loan product
        $list = LoanProduct::with('members')->get();

        return view('components.admin.loans.list_loan_member', compact('list'));
    }

    public function update(Request $request, LoanProduct $loan)
    {
        $request->validate([
            'product' => 'required|string|max:255',
            'product_code' => 'required|string|max:255',
            'prioritization' => 'required|string|max:255',
            'billing_type' => 'nullable|string|in:regular,special',
        ]);

        $loan->update($request->only(['product', 'prioritization', 'product_code', 'billing_type']));

        return redirect()->route('loans')->with('success', 'Loan updated successfully.');
    }

    public function destroy(LoanProduct $loan)
    {
        $loan->delete();

        return redirect()->route('loans')->with('success', 'Loan deleted successfully.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'product' => 'required|string|max:255',
            'product_code' => 'required|string|max:255',
            'prioritization' => 'required|string|max:255',
            'billing_type' => 'nullable|string|in:regular,special',
        ]);

        LoanProduct::create($request->only(['product', 'prioritization', 'product_code', 'billing_type']));

        return redirect()->route('loans')->with('success', 'Loan added successfully.');
    }

}
