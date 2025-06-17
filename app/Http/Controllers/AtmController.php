<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Exports\ListOfProfileExport;
use Maatwebsite\Excel\Facades\Excel;

class AtmController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::with(['branch', 'savings', 'shares', 'loanForecasts'])
            ->select('members.*');

        // Apply search filters
        if ($request->filled('name')) {
            $query->where(function($q) use ($request) {
                $q->where('fname', 'like', '%' . $request->name . '%')
                  ->orWhere('lname', 'like', '%' . $request->name . '%');
            });
        }

        if ($request->filled('emp_id')) {
            $query->where('emp_id', 'like', '%' . $request->emp_id . '%');
        }

        if ($request->filled('cid')) {
            $query->where('cid', 'like', '%' . $request->cid . '%');
        }

        $members = $query->paginate(10);

        return view('components.admin.atm.atm', compact('members'));
    }

    public function updateBalance(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'savings' => 'array',
            'savings.*.account_number' => 'required|string',
            'savings.*.balance' => 'required|numeric|min:0',
            'shares' => 'array',
            'shares.*.account_number' => 'required|string',
            'shares.*.balance' => 'required|numeric|min:0',
            'loans' => 'array',
            'loans.*.account_number' => 'required|string',
            'loans.*.balance' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $member = Member::findOrFail($request->member_id);

            // Update savings balances
            if ($request->has('savings')) {
                foreach ($request->savings as $saving) {
                    $member->savings()
                        ->where('account_number', $saving['account_number'])
                        ->update(['current_balance' => $saving['balance']]);
                }
            }

            // Update share balances
            if ($request->has('shares')) {
                foreach ($request->shares as $share) {
                    $member->shares()
                        ->where('account_number', $share['account_number'])
                        ->update(['current_balance' => $share['balance']]);
                }
            }

            // Update loan balances
            if ($request->has('loans')) {
                foreach ($request->loans as $loan) {
                    $member->loanForecasts()
                        ->where('loan_acct_no', $loan['account_number'])
                        ->update(['total_due' => $loan['balance']]);
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Account balances updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating account balances: ' . $e->getMessage());
        }
    }

    public function generateSummaryReport()
    {
        $summary = Member::select(
            DB::raw('SUM(savings_balance) as total_savings'),
            DB::raw('SUM(share_balance) as total_shares'),
            DB::raw('SUM(loan_balance) as total_loans')
        )->first();

        $branchSummary = Member::select(
            'branches.name as branch_name',
            DB::raw('SUM(members.savings_balance) as total_savings'),
            DB::raw('SUM(members.share_balance) as total_shares'),
            DB::raw('SUM(members.loan_balance) as total_loans')
        )
        ->join('branches', 'members.branch_id', '=', 'branches.id')
        ->groupBy('branches.id', 'branches.name')
        ->get();

        return view('components.admin.atm.summary-report', compact('summary', 'branchSummary'));
    }

    public function generateBranchReport()
    {
        $branches = Member::with(['branch', 'savings', 'shares', 'loanForecasts'])
            ->select('members.*')
            ->join('branches', 'members.branch_id', '=', 'branches.id')
            ->orderBy('branches.name')
            ->get()
            ->groupBy('branch.name');

        return view('components.admin.atm.branch-report', compact('branches'));
    }

    public function exportListOfProfile()
    {
        return Excel::download(new ListOfProfileExport, 'List_of_Profile_' . now()->format('Y-m-d') . '.xlsx');
    }
}
