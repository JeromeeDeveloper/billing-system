<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Member;
use App\Models\User;
use App\Models\BillingExport;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Exports\BillingExport as BillingExcelExport;
use App\Exports\MembersNoBranchExport;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\NotificationController;

class BillingController extends Controller
{
    public function index(Request $request)
    {

        $billingPeriod = auth()->user()->billing_period;
        $search = $request->input('search');
        $perPage = $request->input('perPage', 10);

        // Validate perPage input - allow only these options
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

         $allBranchApproved = User::where('role', 'branch')
        ->where('status', '!=', 'approved')
        ->doesntExist(); // true if all are approved

        // Query with eager loading branch to avoid N+1 query problem
        $query = Member::with('branch')
            ->where('billing_period', $billingPeriod);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('emp_id', 'like', "%{$search}%")
                    ->orWhere('fname', 'like', "%{$search}%")
                    ->orWhere('lname', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%")
                    ->orWhereHas('branch', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $billing = $query->paginate($perPage)->appends([
            'search' => $search,
            'perPage' => $perPage,
        ]);

        return view('components.admin.billing.billing', compact('billing', 'search', 'perPage', 'allBranchApproved'));
    }

    public function index_branch(Request $request)
    {
        $billingPeriod = Auth::user()->billing_period;
        $userBranchId = Auth::user()->branch_id;
        $search = $request->input('search');
        $perPage = $request->input('perPage', 10);

        // Validate perPage input - allow only these options
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

         $allBranchApproved = User::where('role', 'branch')
        ->where('status', '!=', 'approved')
        ->doesntExist(); // true if all are approved

        // Query with eager loading branch to avoid N+1 query problem
        $query = Member::with('branch')
            ->where('billing_period', $billingPeriod)
            ->where('branch_id', $userBranchId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('emp_id', 'like', "%{$search}%")
                    ->orWhere('fname', 'like', "%{$search}%")
                    ->orWhere('lname', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%")
                    ->orWhereHas('branch', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $billing = $query->paginate($perPage)->appends([
            'search' => $search,
            'perPage' => $perPage,
        ]);

        return view('components.branch.billing.billing', compact('billing', 'search', 'perPage', 'allBranchApproved'));
    }

    public function export(Request $request)
    {
        $billingPeriod = Auth::user()->billing_period ?? now()->format('Y-m-01');

        // Generate the Excel file
        $export = new BillingExcelExport($billingPeriod);
        $filename = 'billing_export_' . \Carbon\Carbon::parse($billingPeriod)->format('Y-m') . '.xlsx';

        // Store the file
        Excel::store($export, 'exports/' . $filename, 'public');

        // Save export record
        $billingExport = BillingExport::create([
            'billing_period' => $billingPeriod,
            'filename' => $filename,
            'filepath' => 'exports/' . $filename,
            'generated_by' => Auth::id()
        ]);

        // Add notification
        NotificationController::createNotification('billing_report', Auth::id(), $billingExport->id);

        // Download the file
        return Excel::download($export, $filename);
    }

    public function viewExports(Request $request)
    {
        $query = BillingExport::with('user')
            ->orderBy('billing_period', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->has('billing_period')) {
            $billingPeriod = $request->billing_period;
            // Convert YYYY-MM to YYYY-MM-01 format
            $formattedPeriod = $billingPeriod . '-01';
            $query->where('billing_period', $formattedPeriod);
        }

        $exports = $query->paginate(10)->withQueryString();

        return view('components.admin.billing.exports', compact('exports'));
    }


    public function downloadExport($id)
    {
        try {
            Log::info('Downloading export with ID: ' . $id);

            $export = BillingExport::findOrFail($id);
            Log::info('Found export:', $export->toArray());

            $filePath = $export->filepath;
            $fileName = $export->filename;

            Log::info('Checking file existence:', [
                'filepath' => $filePath,
                'storage_path' => Storage::disk('public')->path($filePath),
                'exists' => Storage::disk('public')->exists($filePath)
            ]);

            if (Storage::disk('public')->exists($filePath)) {
                Log::info('File exists, downloading...');
                return Storage::disk('public')->download($filePath, $fileName);
            }

            Log::error('Export file not found at path: ' . $filePath);
            return back()->with('error', 'Export file not found.');
        } catch (\Exception $e) {
            Log::error('Error downloading export: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return back()->with('error', 'Failed to download export: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Member $member)
    {
        $request->validate([
            'emp_id' => 'nullable|string|max:255',
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'loan_balance' => 'nullable|numeric',
            'principal' => 'nullable|numeric',
        ]);

        $member->update($request->only(['emp_id', 'fname', 'lname', 'loan_balance', 'principal']));

        return redirect()->back()->with('success', 'Member updated successfully!');
    }

    public function update_branch(Request $request, Member $member)
    {
        $request->validate([
            'emp_id' => 'nullable|string|max:255',
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'loan_balance' => 'nullable|numeric',
            'principal' => 'nullable|numeric',
        ]);

        $member->update($request->only(['emp_id', 'fname', 'lname', 'loan_balance', 'principal']));

        return redirect()->back()->with('success', 'Member updated successfully!');
    }

    public function destroy(Member $member)
    {
        $member->delete();
        return redirect()->back()->with('success', 'Member deleted successfully!');
    }

    public function destroy_branch(Member $member)
    {
        $member->delete();
        return redirect()->back()->with('success', 'Member deleted successfully!');
    }

    public function approve()
    {
        $user = Auth::user();

        if ($user->status === 'approved') {
            return back()->with('info', 'You are already approved.');
        }

        User::where('id', $user->id)->update(['status' => 'approved']);

        // Create notification about approval
        \App\Models\Notification::create([
            'type' => 'billing_approval',
            'user_id' => $user->id,
            'related_id' => $user->id,
            'message' => 'You have approved your branch billing for ' . \Carbon\Carbon::parse($user->billing_period)->format('F Y'),
            'billing_period' => $user->billing_period
        ]);

        return back()->with('success', 'Billing approved successfully.');
    }

    public function cancelApproval()
    {
        $user = Auth::user();

        if ($user->status === 'pending') {
            return back()->with('info', 'You are already in pending status.');
        }

        User::where('id', $user->id)->update(['status' => 'pending']);

        // Create notification about approval cancellation
        \App\Models\Notification::create([
            'type' => 'billing_approval_cancelled',
            'user_id' => $user->id,
            'related_id' => $user->id,
            'message' => 'You have cancelled your billing approval for ' . \Carbon\Carbon::parse($user->billing_period)->format('F Y'),
            'billing_period' => $user->billing_period
        ]);

        return back()->with('success', 'Approval cancelled successfully. Your status has been set back to pending.');
    }

    public function getExportsData(Request $request)
    {
        try {
            Log::info('Getting exports data');

            // Get all exports without any filtering
            $exports = BillingExport::with('user')
                ->orderBy('billing_period', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info('Found exports:', [
                'count' => $exports->count(),
                'data' => $exports->toArray()
            ]);

            if ($request->ajax()) {
                $response = [
                    'data' => $exports,
                    'total' => $exports->count()
                ];

                Log::info('Sending response:', $response);
                return response()->json($response);
            }

            return $exports;
        } catch (\Exception $e) {
            Log::error('Error in getExportsData: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            if ($request->ajax()) {
                return response()->json([
                    'error' => 'Failed to load export history',
                    'details' => $e->getMessage()
                ], 500);
            }
            throw $e;
        }
    }

    public function viewExports_branch(Request $request)
    {
        $query = BillingExport::with('user')
            ->orderBy('billing_period', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->has('billing_period')) {
            $billingPeriod = $request->billing_period;
            // Convert YYYY-MM to YYYY-MM-01 format
            $formattedPeriod = $billingPeriod . '-01';
            $query->where('billing_period', $formattedPeriod);
        }

        $exports = $query->paginate(10)->withQueryString();

        return view('components.branch.billing.exports', compact('exports'));
    }

    public function downloadExport_branch($id)
    {
        try {
            Log::info('Downloading export with ID: ' . $id);

            $export = BillingExport::findOrFail($id);

            Log::info('Found export:', $export->toArray());

            $filePath = $export->filepath;
            $fileName = $export->filename;

            Log::info('Checking file existence:', [
                'filepath' => $filePath,
                'storage_path' => Storage::disk('public')->path($filePath),
                'exists' => Storage::disk('public')->exists($filePath)
            ]);

            if (Storage::disk('public')->exists($filePath)) {
                Log::info('File exists, downloading...');
                return Storage::disk('public')->download($filePath, $fileName);
            }

            Log::error('Export file not found at path: ' . $filePath);
            return back()->with('error', 'Export file not found.');
        } catch (\Exception $e) {
            Log::error('Error downloading export: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return back()->with('error', 'Failed to download export: ' . $e->getMessage());
        }
    }

    public function export_branch(Request $request)
    {
        $billingPeriod = Auth::user()->billing_period ?? now()->format('Y-m-01');
        $branchId = Auth::user()->branch_id;

        // Generate the Excel file
        $export = new \App\Exports\BranchBillingExport($billingPeriod, $branchId);
        $filename = 'branch_billing_export_' . \Carbon\Carbon::parse($billingPeriod)->format('Y-m') . '.xlsx';

        // Store the file
        Excel::store($export, 'exports/' . $filename, 'public');

        // Save export record
        $billingExport = BillingExport::create([
            'billing_period' => $billingPeriod,
            'filename' => $filename,
            'filepath' => 'exports/' . $filename,
            'generated_by' => Auth::id()
        ]);

        // Add notification
        NotificationController::createNotification('billing_report', Auth::id(), $billingExport->id);

        // Download the file
        return Excel::download($export, $filename);
    }

    public function exportLoanReport(Request $request)
    {
        try {
            $billingPeriod = Auth::user()->billing_period ?? now()->format('Y-m');

            // Generate the Excel file
            $export = new \App\Exports\LoanReportExport($billingPeriod);
            $filename = 'loan_report_' . $billingPeriod . '_' . now()->format('Y-m-d') . '.xlsx';

            // Store the file
            Excel::store($export, 'exports/' . $filename, 'public');

            // Save export record
            $billingExport = BillingExport::create([
                'billing_period' => $billingPeriod,
                'filename' => $filename,
                'filepath' => 'exports/' . $filename,
                'generated_by' => Auth::id()
            ]);

            // Add notification
            NotificationController::createNotification('billing_report', Auth::id(), $billingExport->id);

            // Download the file
            return Excel::download($export, $filename);

        } catch (\Exception $e) {
            Log::error('Error generating loan report: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error generating loan report: ' . $e->getMessage());
        }
    }

    public function exportBranchLoanReport(Request $request)
    {
        try {
            $billingPeriod = Auth::user()->billing_period ?? now()->format('Y-m');
            $branchId = Auth::user()->branch_id;

            // Generate the Excel file
            $export = new \App\Exports\BranchLoanReportExport($billingPeriod, $branchId);
            $filename = 'branch_loan_report_' . $billingPeriod . '_' . now()->format('Y-m-d') . '.xlsx';

            // Store the file
            Excel::store($export, 'exports/' . $filename, 'public');

            // Save export record
            $billingExport = BillingExport::create([
                'billing_period' => $billingPeriod,
                'filename' => $filename,
                'filepath' => 'exports/' . $filename,
                'generated_by' => Auth::id()
            ]);

            // Add notification
            NotificationController::createNotification('billing_report', Auth::id(), $billingExport->id);

            // Download the file
            return Excel::download($export, $filename);

        } catch (\Exception $e) {
            Log::error('Error generating branch loan report: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error generating branch loan report: ' . $e->getMessage());
        }
    }

    public function exportMembersNoBranch(Request $request)
    {
        try {
            // Generate the Excel file
            $export = new MembersNoBranchExport();
            $filename = 'members_no_branch_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            // Store the file
            Excel::store($export, 'exports/' . $filename, 'public');

            // Save export record
            $billingExport = BillingExport::create([
                'billing_period' => now()->format('Y-m'),
                'filename' => $filename,
                'filepath' => 'exports/' . $filename,
                'generated_by' => Auth::id()
            ]);

            // Add notification
            NotificationController::createNotification('billing_report', Auth::id(), $billingExport->id);

            // Download the file
            return Excel::download($export, $filename);

        } catch (\Exception $e) {
            Log::error('Error generating members no branch report: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error generating members no branch report: ' . $e->getMessage());
        }
    }

    public function testBillingPeriod()
    {
        $userBillingPeriod = Auth::user()->billing_period;
        $extractedPeriod = \Carbon\Carbon::parse($userBillingPeriod)->format('Y-m');
        
        // Get a sample member with loan forecasts
        $member = \App\Models\Member::with(['loanForecasts', 'loanProductMembers'])
            ->where('loan_balance', '>', 0)
            ->first();
            
        if (!$member) {
            return response()->json(['error' => 'No members found with loan balance > 0']);
        }
        
        $loanForecasts = $member->loanForecasts->map(function($lf) use ($extractedPeriod) {
            $dueDateMonth = $lf->amortization_due_date ? \Carbon\Carbon::parse($lf->amortization_due_date)->format('Y-m') : null;
            return [
                'id' => $lf->id,
                'loan_acct_no' => $lf->loan_acct_no,
                'billing_period' => $lf->billing_period,
                'amortization_due_date' => $lf->amortization_due_date,
                'due_date_month' => $dueDateMonth,
                'extracted_period' => $extractedPeriod,
                'matches' => $dueDateMonth === $extractedPeriod
            ];
        });
        
        return response()->json([
            'user_billing_period' => $userBillingPeriod,
            'extracted_period' => $extractedPeriod,
            'member' => [
                'id' => $member->id,
                'name' => $member->fname . ' ' . $member->lname,
                'loan_balance' => $member->loan_balance,
                'has_loan_products' => $member->loanProductMembers->count() > 0
            ],
            'loan_forecasts' => $loanForecasts
        ]);
    }
}
