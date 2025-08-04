<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\LoanForecast;
use App\Models\MasterList;
use App\Models\User;
use App\Models\Notification;
use App\Models\Branch;
use App\Models\LoanProduct;
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

        // Get total branches
        $totalBranches = Branch::count();

        // Get total active loans
        $totalActiveLoans = LoanForecast::where('billing_period', $billingPeriod)
            ->where('maturity_date', '>=', now())
            ->count();

        // Get total loan products count
        $totalLoanProducts = LoanProduct::count();

        // Get branch-based member statistics for the chart
        $branchStats = Branch::withCount('members')->get();

        // Format data for the chart
        $branches = [];
        $memberCounts = [];

        foreach ($branchStats as $branch) {
            $branches[] = $branch->name;
            $memberCounts[] = $branch->members_count;
        }

        // Debug: Log the data being generated for admin dashboard
        \Illuminate\Support\Facades\Log::info('Admin Dashboard Data - Branches:', $branches);
        \Illuminate\Support\Facades\Log::info('Admin Dashboard Data - Member Counts:', $memberCounts);
        \Illuminate\Support\Facades\Log::info('Admin Dashboard Data - Branch Stats Count:', ['count' => count($branchStats)]);
        \Illuminate\Support\Facades\Log::info('Admin Dashboard Data - Total Branches:', ['count' => $totalBranches]);

        // Get member tagging distribution (PGB vs New)
        $memberTaggingStats = Member::select('member_tagging', DB::raw('count(*) as count'))
            ->whereNotNull('member_tagging')
            ->groupBy('member_tagging')
            ->get();

        // Calculate percentages for PGB and New
        $totalTaggedMembers = $memberTaggingStats->sum('count');
        $pgbPercentage = 0;
        $newPercentage = 0;

        foreach ($memberTaggingStats as $stat) {
            if ($stat->member_tagging === 'PGB') {
                $pgbPercentage = round(($stat->count / $totalTaggedMembers) * 100);
            } else if ($stat->member_tagging === 'New') {
                $newPercentage = round(($stat->count / $totalTaggedMembers) * 100);
            }
        }

        return view('components.admin.dashboard.dashboard', compact(
            'showPrompt',
            'totalMembers',
            'totalBranches',
            'totalActiveLoans',
            'totalLoanProducts',
            'branches',
            'memberCounts',
            'pgbPercentage',
            'newPercentage'
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

        // Get total branches
        $totalBranches = Branch::count();

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

        // Get special product type count from LoanProduct model
        $specialProductTypeCount = LoanProduct::where('billing_type', 'special')->count();

        // Get approved branches data
        $approvedBranches = Branch::whereHas('users', function($query) {
            $query->where('status', 'approved');
        })->pluck('name')->toArray();

        // Get all branches with member counts for status display
        $allBranches = Branch::withCount('members')->get();

        // Get branch-based member statistics for the chart (for branch dashboard, show all branches)
        $branchStats = Branch::withCount('members')->get();

        // Format data for the chart
        $branches = [];
        $memberCounts = [];

        foreach ($branchStats as $branch) {
            $branches[] = $branch->name;
            $memberCounts[] = $branch->members_count;
        }

        // Debug: Log the data being generated for branch dashboard
        \Illuminate\Support\Facades\Log::info('Branch Dashboard Data - Branches:', $branches);
        \Illuminate\Support\Facades\Log::info('Branch Dashboard Data - Member Counts:', $memberCounts);
        \Illuminate\Support\Facades\Log::info('Branch Dashboard Data - Branch Stats Count:', ['count' => count($branchStats)]);
        \Illuminate\Support\Facades\Log::info('Branch Dashboard Data - Total Branches:', ['count' => $totalBranches]);

        // Get member tagging distribution for the branch (PGB vs New)
        $memberTaggingStats = Member::where('branch_id', $branchId)
            ->select('member_tagging', DB::raw('count(*) as count'))
            ->whereNotNull('member_tagging')
            ->groupBy('member_tagging')
            ->get();

        // Calculate percentages for PGB and New
        $totalTaggedMembers = $memberTaggingStats->sum('count');
        $pgbPercentage = 0;
        $newPercentage = 0;

        foreach ($memberTaggingStats as $stat) {
            if ($stat->member_tagging === 'PGB') {
                $pgbPercentage = round(($stat->count / $totalTaggedMembers) * 100);
            } else if ($stat->member_tagging === 'New') {
                $newPercentage = round(($stat->count / $totalTaggedMembers) * 100);
            }
        }

        return view('components.branch.dashboard.dashboard', compact(
            'showPrompt',
            'totalMembers',
            'totalBranches',
            'totalActiveLoans',
            'totalLoanAmount',
            'totalSavings',
            'specialProductTypeCount',
            'approvedBranches',
            'allBranches',
            'branches',
            'memberCounts',
            'pgbPercentage',
            'newPercentage'
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
