<?php

namespace App\Http\Controllers;

use App\Models\LoanProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exports\LoanProductsExport;
use Maatwebsite\Excel\Facades\Excel;

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
            'billing_type' => 'nullable|string|in:regular,special,not_billed',
        ]);

        $loan->update($request->only(['product', 'prioritization', 'product_code', 'billing_type']));

        // Recalculate loan_balance for all members with this product
        $members = $loan->members;
        foreach ($members as $member) {
            $loanForecasts = $member->loanForecasts;
            $productMap = [
                $loan->product_code => $loan->billing_type
            ];
            $loan_balance = $loanForecasts->filter(function($forecast) use ($productMap) {
                $segments = explode('-', $forecast->loan_acct_no);
                $productCode = $segments[2] ?? null;
                return isset($productMap[$productCode]) && $productMap[$productCode] === 'regular';
            })->sum('total_due');
            $member->update(['loan_balance' => $loan_balance]);
        }

        return redirect()->route('loans')->with('success', 'Loan updated successfully. Loan balances recalculated.');
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
            'billing_type' => 'nullable|string|in:regular,special,not_billed',
        ]);

        LoanProduct::create($request->only(['product', 'prioritization', 'product_code', 'billing_type']));

        return redirect()->route('loans')->with('success', 'Loan added successfully.');
    }

    public function export()
    {
        return Excel::download(new LoanProductsExport, 'loans_datatable.xlsx');
    }

}
