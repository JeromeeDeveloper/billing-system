<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Exception;

class MemberController extends Controller
{
  public function index(Request $request)
{
    $search = $request->input('search');

    $members = Member::with('branch')
        ->when($search, function ($query, $search) {
            $query->where('cid', 'like', "%{$search}%")
                  ->orWhere('lname', 'like', "%{$search}%")
                  ->orWhere('fname', 'like', "%{$search}%");
        })
        ->paginate(25)
        ->appends(['search' => $search]); // keep search query in pagination links

    $branches = Branch::all();

    return view('components.admin.members.member', compact('members', 'branches'));
}


    public function view($id)
    {
        // Find the member by ID with related branch data
        $member = Member::with('branch')->findOrFail($id);

        // Return the view for viewing the member details
        return view('components.admin.members.view', compact('member'));
    }

    public function edit($id)
    {
        // Find the member by ID with related branch data
        $member = Member::with('branch')->findOrFail($id);

        // Fetch all branches to populate the branch dropdown in the form
        $branches = Branch::all();

        return view('components.admin.members.edit', compact('member', 'branches'));
    }

    public function update(Request $request, $id)
    {
        // Validate incoming request data
        $request->validate([
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'cid' => 'required|numeric',
            'branch_id' => 'required|exists:branches,id',
            'expiry_date' => 'nullable|date',
        ]);

        try {
            // Find the member to update
            $member = Member::findOrFail($id);
            $member->fname = $request->input('fname');
            $member->lname = $request->input('lname');
            $member->cid = $request->input('cid');
            $member->branch_id = $request->input('branch_id');
            $member->expiry_date = $request->input('expiry_date') ? substr($request->input('expiry_date'), 0, 7) : null;
            $member->save();

            return redirect()->route('member')->with('success', 'Member updated successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to update member: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            // Find and delete the member
            $member = Member::findOrFail($id);
            $member->delete();

            return redirect()->route('member')->with('success', 'Member deleted successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete member: ' . $e->getMessage());
        }
    }
}
