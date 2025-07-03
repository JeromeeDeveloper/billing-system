<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\LoanForecast;
use App\Models\MasterList;
use App\Models\User;
use App\Models\Notification;
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

        // Only show billing period prompt if user doesn't have a billing period set
        // and the prompt hasn't been shown this session
        if (!$billingPeriod && !$request->session()->has('billing_prompt_shown')) {
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
        // Get current billing period
        $billingPeriod = Auth::user()->billing_period;

        // Only show billing period prompt if user doesn't have a billing period set
        // and the prompt hasn't been shown this session
        if (!$billingPeriod && !$request->session()->has('billing_prompt_shown')) {
            $request->session()->put('billing_prompt_shown', true);
            $showPrompt = true;
        } else {
            $showPrompt = false;
        }

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
        $oldBillingPeriod = $user->billing_period;
        $newBillingPeriod = $request->billing_period . '-01'; // Save as full date

        $statusChanged = false;

        // If billing period changed and user is a branch user, set status to pending
        if ($user->role === 'branch' && $oldBillingPeriod !== $newBillingPeriod) {
            User::where('id', $user->id)->update(['status' => 'pending']);
            $statusChanged = true;
        }

        User::where('id', $user->id)->update(['billing_period' => $newBillingPeriod]);

        // Create notification about billing period change
        if ($oldBillingPeriod !== $newBillingPeriod) {
            \App\Models\Notification::create([
                'type' => 'billing_period_update',
                'user_id' => $user->id,
                'related_id' => $user->id,
                'message' => 'Your billing period has been manually updated to ' . \Carbon\Carbon::parse($newBillingPeriod)->format('F Y'),
                'billing_period' => $newBillingPeriod
            ]);
        }

        $message = 'Billing period saved.';
        if ($statusChanged) {
            $message .= ' Your status has been reset to pending due to billing period change.';
            // Add session message for the next page load
            session()->flash('status_change_notice', 'Your account status has been reset to pending due to billing period change.');
        }

        return response()->json(['message' => $message, 'status_changed' => $statusChanged]);
    }

    public function store_branch(Request $request)
    {
        $request->validate([
            'billing_period' => ['required', 'date_format:Y-m'],
        ]);

        $user = Auth::user();
        $oldBillingPeriod = $user->billing_period;
        $newBillingPeriod = $request->billing_period . '-01'; // Save as full date

        $statusChanged = false;

        // If billing period changed and user is a branch user, set status to pending
        if ($user->role === 'branch' && $oldBillingPeriod !== $newBillingPeriod) {
            User::where('id', $user->id)->update(['status' => 'pending']);
            $statusChanged = true;
        }

        User::where('id', $user->id)->update(['billing_period' => $newBillingPeriod]);

        // Create notification about billing period change
        if ($oldBillingPeriod !== $newBillingPeriod) {
            \App\Models\Notification::create([
                'type' => 'billing_period_update',
                'user_id' => $user->id,
                'related_id' => $user->id,
                'message' => 'Your billing period has been manually updated to ' . \Carbon\Carbon::parse($newBillingPeriod)->format('F Y'),
                'billing_period' => $newBillingPeriod
            ]);
        }

        $message = 'Billing period saved.';
        if ($statusChanged) {
            $message .= ' Your status has been reset to pending due to billing period change.';
            // Add session message for the next page load
            session()->flash('status_change_notice', 'Your account status has been reset to pending due to billing period change.');
        }

        return response()->json(['message' => $message, 'status_changed' => $statusChanged]);
    }
}
