<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\LoanForecast;
use App\Models\MasterList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get current billing period
        $billingPeriod = Auth::user()->billing_period;

        // Check if billing_period prompt was shown this session
        if (!$request->session()->has('billing_prompt_shown')) {
            $request->session()->put('billing_prompt_shown', true);
            $showPrompt = true;
        } else {
            $showPrompt = false;
        }

        // Get total members
        $totalMembers = Member::count();

        // Get total active loans
        $totalActiveLoans = LoanForecast::where('billing_period', $billingPeriod)
            ->where('maturity_date', '>=', now())
            ->count();

        // Get total loan amount due
        $totalLoanAmount = LoanForecast::where('billing_period', $billingPeriod)
            ->sum('total_due');

        // Get total savings balance
        $totalSavings = Member::sum('savings_balance');

        // Get monthly loan statistics for the chart
        $monthlyStats = LoanForecast::where('billing_period', $billingPeriod)
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total_due) as total_amount'),
                DB::raw('COUNT(*) as loan_count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Format data for the chart
        $months = [];
        $loanAmounts = [];
        $loanCounts = [];

        foreach ($monthlyStats as $stat) {
            $months[] = Carbon::create()->month($stat->month)->format('M');
            $loanAmounts[] = $stat->total_amount;
            $loanCounts[] = $stat->loan_count;
        }

        // Get member status distribution
        $memberStatusStats = MasterList::where('billing_period', $billingPeriod)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // Calculate percentages
        $totalStatusCount = $memberStatusStats->sum('count');
        $deductionPercentage = 0;
        $nonDeductionPercentage = 0;

        foreach ($memberStatusStats as $stat) {
            if ($stat->status === 'deduction') {
                $deductionPercentage = round(($stat->count / $totalStatusCount) * 100);
            } else if ($stat->status === 'non-deduction') {
                $nonDeductionPercentage = round(($stat->count / $totalStatusCount) * 100);
            }
        }

        return view('components.admin.dashboard.dashboard', compact(
            'showPrompt',
            'totalMembers',
            'totalActiveLoans',
            'totalLoanAmount',
            'totalSavings',
            'months',
            'loanAmounts',
            'loanCounts',
            'deductionPercentage',
            'nonDeductionPercentage'
        ));
    }

    public function index_branch(Request $request)
    {
        // Similar logic for branch dashboard
        if (!$request->session()->has('billing_prompt_shown')) {
            $request->session()->put('billing_prompt_shown', true);
            $showPrompt = true;
        } else {
            $showPrompt = false;
        }

        $billingPeriod = Auth::user()->billing_period;
        $branchId = Auth::user()->branch_id;

        // Get branch-specific statistics
        $totalMembers = Member::where('branch_id', $branchId)->count();

        $totalActiveLoans = LoanForecast::whereHas('member', function($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })
        ->where('billing_period', $billingPeriod)
        ->where('maturity_date', '>=', now())
        ->count();

        $totalLoanAmount = LoanForecast::whereHas('member', function($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })
        ->where('billing_period', $billingPeriod)
        ->sum('total_due');

        $totalSavings = Member::where('branch_id', $branchId)
            ->sum('savings_balance');

        // Get monthly loan statistics for the branch
        $monthlyStats = LoanForecast::whereHas('member', function($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })
        ->where('billing_period', $billingPeriod)
        ->select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(total_due) as total_amount'),
            DB::raw('COUNT(*) as loan_count')
        )
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        // Format data for the chart
        $months = [];
        $loanAmounts = [];
        $loanCounts = [];

        foreach ($monthlyStats as $stat) {
            $months[] = Carbon::create()->month($stat->month)->format('M');
            $loanAmounts[] = $stat->total_amount;
            $loanCounts[] = $stat->loan_count;
        }

        // Get member status distribution for the branch
        $memberStatusStats = MasterList::whereHas('member', function($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })
        ->where('billing_period', $billingPeriod)
        ->select('status', DB::raw('count(*) as count'))
        ->groupBy('status')
        ->get();

        // Calculate percentages
        $totalStatusCount = $memberStatusStats->sum('count');
        $deductionPercentage = 0;
        $nonDeductionPercentage = 0;

        foreach ($memberStatusStats as $stat) {
            if ($stat->status === 'deduction') {
                $deductionPercentage = round(($stat->count / $totalStatusCount) * 100);
            } else if ($stat->status === 'non-deduction') {
                $nonDeductionPercentage = round(($stat->count / $totalStatusCount) * 100);
            }
        }

        return view('components.branch.dashboard.dashboard', compact(
            'showPrompt',
            'totalMembers',
            'totalActiveLoans',
            'totalLoanAmount',
            'totalSavings',
            'months',
            'loanAmounts',
            'loanCounts',
            'deductionPercentage',
            'nonDeductionPercentage'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'billing_period' => ['required', 'date_format:Y-m'],
        ]);

        $user = Auth::user();
        $user->billing_period = $request->billing_period . '-01'; // Save as full date
        $user->save();

        return response()->json(['message' => 'Billing period saved.']);
    }

    public function store_branch(Request $request)
    {
        $request->validate([
            'billing_period' => ['required', 'date_format:Y-m'],
        ]);

        $user = Auth::user();
        $user->billing_period = $request->billing_period . '-01'; // Save as full date
        $user->save();

        return response()->json(['message' => 'Billing period saved.']);
    }
}
