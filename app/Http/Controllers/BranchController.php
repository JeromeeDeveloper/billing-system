<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Member;
use App\Models\User;
use Exception;

class BranchController extends Controller
{
    public function index()
    {
        // Fetch all branches with related members and users data
        $branches = Branch::with(['members', 'users'])->get()->map(function($branch) {
            $branchUser = User::where('branch_id', $branch->id)->first();
            $branch->status = $branchUser ? $branchUser->status : 'N/A';
            return $branch;
        });

        return view('components.admin.branch.branch', compact('branches'));
    }

    public function view($id)
    {
        // Find the branch by ID with related members data
        $branch = Branch::with('members')->findOrFail($id);

        // Return the view for viewing the branch details
        return view('components.admin.branch.view', compact('branch'));
    }

    public function edit($id)
    {
        // Find the branch by ID
        $branch = Branch::findOrFail($id);

        return view('components.admin.branch.edit', compact('branch'));
    }

    public function update(Request $request, $id)
    {
        // Validate incoming request data
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
        ]);

        try {
            // Find the branch to update
            $branch = Branch::findOrFail($id);
            $branch->name = $request->input('name');
            $branch->code = $request->input('code');
            $branch->save();

            return redirect()->route('branch')->with('success', 'Branch updated successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to update branch: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:branches,code',
        ]);

        try {
            $branch = new Branch();
            $branch->name = $request->input('name');
            $branch->code = $request->input('code');
            $branch->save();

            return redirect()->route('branch')->with('success', 'Branch added successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to add branch: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            // Find and delete the branch
            $branch = Branch::findOrFail($id);
            $branch->delete();

            return redirect()->route('branch')->with('success', 'Branch deleted successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete branch: ' . $e->getMessage());
        }
    }

    public function assignMember(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'member_id' => 'required|exists:members,id',
        ]);

        try {
            $member = \App\Models\Member::findOrFail($request->input('member_id'));
            $member->branch_id = $request->input('branch_id');
            $member->save();

            return redirect()->route('branch')->with('success', 'Member assigned to branch successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to assign member: ' . $e->getMessage());
        }
    }
}
