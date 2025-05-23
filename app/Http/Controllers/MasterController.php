<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Member;
use App\Models\MasterList;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    public function index()
    {

        $masterlists = MasterList::with(['member', 'branch'])->get();
        $branches = Branch::all();

        return view('components.master.master', compact('masterlists', 'branches'));
    }

    public function update(Request $request, $id)
    {
        $member = Member::findOrFail($id);

        $member->update($request->only([
            'cid',
            'emp_id',
            'fname',
            'lname',
            'address',
            'savings_balance',
            'share_balance',
            'loan_balance',
            'birth_date',
            'date_registered',
            'gender',
            'customer_type',
            'customer_classification',
            'occupation',
            'industry',
            'area_officer',
            'area',
            'status',
            'additional_address',
            'account_status',
            'branch_id'
        ]));

        return redirect()->back()->with('success', 'Member updated successfully!');
    }


    public function destroy($id)
    {
        Member::destroy($id);
        return redirect()->back()->with('success', 'Member deleted');
    }
}
