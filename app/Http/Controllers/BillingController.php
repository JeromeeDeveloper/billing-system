<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Member;
use App\Models\User;
use App\Models\BillingExport;
use App\Models\Notification;
use App\Models\RemittanceBatch;
use App\Models\RemittanceReport;
use App\Models\RemittancePreview;
use App\Models\RemittanceUploadCount;
use App\Models\LoanPayment;
use App\Models\SpecialBilling;
use App\Models\LoanRemittance;
use App\Models\AtmPayment;
use App\Models\LoanForecast;
use App\Models\Saving;
use App\Models\Shares;
use Illuminate\Http\Request;
use App\Exports\BillingExport as BillingExcelExport;
use App\Exports\MembersNoBranchExport;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

         $allUsersApproved = User::whereIn('role', ['admin', 'branch'])
        ->where('billing_approval_status', '!=', 'approved')
        ->doesntExist(); // true if all are approved

        // Query with eager loading branch to avoid N+1 query problem
        $query = Member::with('branch')
            ->where(function ($query) {
                $query->where('account_status', 'deduction')
                    ->orWhere(function ($query) {
                        $query->where('account_status', 'non-deduction')
                            ->where(function ($q) {
                                $q->whereRaw("STR_TO_DATE(start_hold, '%Y-%m') > ?", [now()->format('Y-m-01')])
                                    ->orWhereRaw("STR_TO_DATE(expiry_date, '%Y-%m') <= ?", [now()->format('Y-m-01')]);
                            });
                    });
            })
            ->whereHas('loanForecasts', function ($query) use ($billingPeriod) {
                $query->where(function($q) use ($billingPeriod) {
                    $q->whereNull('amortization_due_date')
                      ->orWhereRaw("amortization_due_date <= ?", [\Carbon\Carbon::parse($billingPeriod . '-01')->endOfMonth()->toDateString()]);
                });
            })
            ->whereHas('loanProductMembers')
            ->where('loan_balance', '>', 0);

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

        $hasAnyMemberNoBranch = Member::whereNull('branch_id')->exists();

        // Check if there's any billing export record for this billing period (to disable cancel approval)
        $hasBillingExportForPeriod = \App\Models\BillingExport::where('billing_period', $billingPeriod)->exists();

        return view('components.admin.billing.billing', compact('billing', 'search', 'perPage', 'allUsersApproved', 'hasAnyMemberNoBranch', 'hasBillingExportForPeriod'));
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

        $allUsersApproved = User::whereIn('role', ['admin', 'branch'])
            ->where('billing_approval_status', '!=', 'approved')
            ->doesntExist(); // true if all are approved

        // Query with eager loading branch to avoid N+1 query problem
        $query = Member::with('branch')
            ->where('branch_id', $userBranchId)
            ->whereHas('loanForecasts', function ($query) use ($billingPeriod) {
                $query->where(function($q) use ($billingPeriod) {
                    $q->whereNull('amortization_due_date')
                      ->orWhereRaw("amortization_due_date <= ?", [\Carbon\Carbon::parse($billingPeriod . '-01')->endOfMonth()->toDateString()]);
                });
            })
            ->whereHas('loanProductMembers', function ($query) {
                $query->whereHas('loanProduct', function ($q) {
                    $q->whereNotIn('billing_type', ['special', 'not_billed']);
                });
            })
            ->where('loan_balance', '>', 0);

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

        // Check if user has already generated billing for this month
        $userId = Auth::id();
        $alreadyExported = \App\Models\BillingExport::where('billing_period', $billingPeriod)
            ->where('generated_by', $userId)
            ->exists();

        // Check if there's any billing export record for this billing period (to disable cancel approval)
        $hasBillingExportForPeriod = \App\Models\BillingExport::where('billing_period', $billingPeriod)->exists();

        return view('components.branch.billing.billing', compact('billing', 'search', 'perPage', 'allUsersApproved', 'alreadyExported', 'hasBillingExportForPeriod'));
    }

    public function export(Request $request)
    {
        set_time_limit(600); // Allow up to 10 minutes for export

        $billingPeriod = Auth::user()->billing_period ?? now()->format('Y-m-01');
        $userId = Auth::id();

        // Validation: Only allow one export per user per month
        $alreadyExported = BillingExport::where('billing_period', $billingPeriod)
            ->where('generated_by', $userId)
            ->exists();
        if ($alreadyExported) {
            return back()->with('error', 'You have already generated billing for this month.');
        }

        // Generate the Excel file
        $export = new BillingExcelExport($billingPeriod);
        $filename = 'billing_export_' . \Carbon\Carbon::parse($billingPeriod)->format('Y-m') . '.xlsx';

        // Store the file
        \Maatwebsite\Excel\Facades\Excel::store($export, 'exports/' . $filename, 'public');

        // Save export record
        $billingExport = BillingExport::create([
            'billing_period' => $billingPeriod,
            'filename' => $filename,
            'filepath' => 'exports/' . $filename,
            'generated_by' => $userId
        ]);

        // Add notification
        NotificationController::createNotification('billing_report', $userId, $billingExport->id);

        // Download the file
        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }

    public function viewExports(Request $request)
    {
        try {
            // Test database connection
            try {
                \DB::connection()->getPdo();
                Log::info('Database connection successful');
            } catch (\Exception $e) {
                throw new \Exception('Database connection failed: ' . $e->getMessage());
            }

            // Check if BillingExport model exists and is accessible
            if (!class_exists('App\Models\BillingExport')) {
                throw new \Exception('BillingExport model not found');
            }

            // Check if User model exists and is accessible
            if (!class_exists('App\Models\User')) {
                throw new \Exception('User model not found');
            }

            // Test if billing_exports table exists
            try {
                $tableExists = \Schema::hasTable('billing_exports');
                Log::info('Billing exports table exists: ' . ($tableExists ? 'yes' : 'no'));
                if (!$tableExists) {
                    throw new \Exception('Billing exports table does not exist');
                }
            } catch (\Exception $e) {
                throw new \Exception('Error checking table: ' . $e->getMessage());
            }

            // Test basic query without relationships
            try {
                $basicQuery = BillingExport::query();
                $count = $basicQuery->count();
                Log::info('Basic BillingExport count: ' . $count);
            } catch (\Exception $e) {
                throw new \Exception('Error in basic query: ' . $e->getMessage());
            }

            // Simple query without any Excel processing
            $query = BillingExport::select('id', 'billing_period', 'filename', 'filepath', 'generated_by', 'created_at')
                ->orderBy('billing_period', 'desc')
                ->orderBy('created_at', 'desc');

            if ($request->has('billing_period')) {
                $billingPeriod = $request->billing_period;
                // Convert YYYY-MM to YYYY-MM-01 format
                $formattedPeriod = $billingPeriod . '-01';
                $query->where('billing_period', $formattedPeriod);
            }

            // Get all exports first
            $allExports = $query->get();

            // Filter out invalid files
            $validExports = $allExports->filter(function ($export) {
                $filePath = $export->filepath;

                // Check if file exists
                if (!Storage::disk('public')->exists($filePath)) {
                    Log::info('Filtering out non-existent file: ' . $filePath);
                    return false;
                }

                // Check if file is not empty
                $fileSize = Storage::disk('public')->size($filePath);
                if ($fileSize === 0) {
                    Log::info('Filtering out empty file: ' . $filePath . ' (size: ' . $fileSize . ' bytes)');
                    return false;
                }

                // Check if file has minimum size (Excel files should be at least 1KB)
                if ($fileSize < 1024) {
                    Log::info('Filtering out suspiciously small file: ' . $filePath . ' (size: ' . $fileSize . ' bytes)');
                    return false;
                }

                return true;
            });

            Log::info('Filtered exports: ' . $validExports->count() . ' valid out of ' . $allExports->count() . ' total');

            // Create pagination manually for filtered results
            $page = $request->get('page', 1);
            $perPage = 10;
            $offset = ($page - 1) * $perPage;

            $exports = new \Illuminate\Pagination\LengthAwarePaginator(
                $validExports->slice($offset, $perPage),
                $validExports->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            // Log the results for debugging
            Log::info('BillingExport results:', [
                'count' => $exports->count(),
                'total' => $exports->total(),
                'first_item' => $exports->firstItem(),
                'last_item' => $exports->lastItem()
            ]);

            return view('components.admin.billing.exports', compact('exports'));
        } catch (\Exception $e) {
            Log::error('Error in viewExports: ' . $e->getMessage());
            Log::error('Error trace: ' . $e->getTraceAsString());
            Log::error('Error file: ' . $e->getFile() . ':' . $e->getLine());

            // Return empty exports with error message
            $exports = collect([])->paginate(10);
            return view('components.admin.billing.exports', compact('exports'))
                ->with('error', 'Unable to load export history. Error: ' . $e->getMessage());
        }
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

                // Check file size to ensure it's not empty
                $fileSize = Storage::disk('public')->size($filePath);
                Log::info('File size: ' . $fileSize . ' bytes');

                if ($fileSize === 0) {
                    Log::error('Export file is empty: ' . $filePath);
                    return back()->with('error', 'Export file is empty or corrupted. Please regenerate the export.');
                }

                // Try to download the file
                try {
                    return Storage::disk('public')->download($filePath, $fileName);
                } catch (\PhpOffice\PhpSpreadsheet\Exception\InvalidFormatException $e) {
                    Log::error('Excel format error: ' . $e->getMessage());
                    return back()->with('error', 'The export file is corrupted or has invalid format. Please regenerate the export.');
                } catch (\Exception $e) {
                    Log::error('Download error: ' . $e->getMessage());
                    return back()->with('error', 'Failed to download file: ' . $e->getMessage());
                }
            }

            Log::error('Export file not found at path: ' . $filePath);
            return back()->with('error', 'Export file not found. Please regenerate the export.');
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

        if ($user->billing_approval_status === 'approved') {
            return back()->with('info', 'You are already approved.');
        }

        User::where('id', $user->id)->update(['billing_approval_status' => 'approved']);

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

        if ($user->billing_approval_status === 'pending') {
            return back()->with('info', 'You are already in pending status.');
        }

        User::where('id', $user->id)->update(['billing_approval_status' => 'pending']);

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
        try {
            // Check if BillingExport model exists and is accessible
            if (!class_exists('App\Models\BillingExport')) {
                throw new \Exception('BillingExport model not found');
            }

            // Check if User model exists and is accessible
            if (!class_exists('App\Models\User')) {
                throw new \Exception('User model not found');
            }

            // Get the current user's branch ID
            $userBranchId = Auth::user()->branch_id;

            // Simple query without any Excel processing - filter by branch
            $query = BillingExport::select('id', 'billing_period', 'filename', 'filepath', 'generated_by', 'created_at')
                ->whereHas('user', function($query) use ($userBranchId) {
                    $query->where('branch_id', $userBranchId);
                })
                ->orderBy('billing_period', 'desc')
                ->orderBy('created_at', 'desc');

            if ($request->has('billing_period')) {
                $billingPeriod = $request->billing_period;
                // Convert YYYY-MM to YYYY-MM-01 format
                $formattedPeriod = $billingPeriod . '-01';
                $query->where('billing_period', $formattedPeriod);
            }

            // Get all exports for this branch first
            $allExports = $query->get();

            // Filter out invalid files
            $validExports = $allExports->filter(function ($export) {
                $filePath = $export->filepath;

                // Check if file exists
                if (!Storage::disk('public')->exists($filePath)) {
                    Log::info('Filtering out non-existent file: ' . $filePath);
                    return false;
                }

                // Check if file is not empty
                $fileSize = Storage::disk('public')->size($filePath);
                if ($fileSize === 0) {
                    Log::info('Filtering out empty file: ' . $filePath . ' (size: ' . $fileSize . ' bytes)');
                    return false;
                }

                // Check if file has minimum size (Excel files should be at least 1KB)
                if ($fileSize < 1024) {
                    Log::info('Filtering out suspiciously small file: ' . $filePath . ' (size: ' . $fileSize . ' bytes)');
                    return false;
                }

                return true;
            });

            Log::info('Filtered exports (branch): ' . $validExports->count() . ' valid out of ' . $allExports->count() . ' total');

            // Create pagination manually for filtered results
            $page = $request->get('page', 1);
            $perPage = 10;
            $offset = ($page - 1) * $perPage;

            $exports = new \Illuminate\Pagination\LengthAwarePaginator(
                $validExports->slice($offset, $perPage),
                $validExports->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            // Log the results for debugging
            Log::info('BillingExport branch results:', [
                'count' => $exports->count(),
                'total' => $exports->total(),
                'first_item' => $exports->firstItem(),
                'last_item' => $exports->lastItem()
            ]);

            return view('components.branch.billing.exports', compact('exports'));
        } catch (\Exception $e) {
            Log::error('Error in viewExports_branch: ' . $e->getMessage());
            Log::error('Error trace: ' . $e->getTraceAsString());
            Log::error('Error file: ' . $e->getFile() . ':' . $e->getLine());

            // Return empty exports with error message
            $exports = collect([])->paginate(10);
            return view('components.branch.billing.exports', compact('exports'))
                ->with('error', 'Unable to load export history. Error: ' . $e->getMessage());
        }
    }

    public function downloadExport_branch($id)
    {
        try {
            Log::info('Downloading export with ID: ' . $id);

            // Get the current user's branch ID
            $userBranchId = Auth::user()->branch_id;

            // Find export and ensure it belongs to the user's branch
            $export = BillingExport::whereHas('user', function($query) use ($userBranchId) {
                $query->where('branch_id', $userBranchId);
            })->findOrFail($id);

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

                // Check file size to ensure it's not empty
                $fileSize = Storage::disk('public')->size($filePath);
                Log::info('File size: ' . $fileSize . ' bytes');

                if ($fileSize === 0) {
                    Log::error('Export file is empty: ' . $filePath);
                    return back()->with('error', 'Export file is empty or corrupted. Please regenerate the export.');
                }

                // Try to download the file
                try {
                    return Storage::disk('public')->download($filePath, $fileName);
                } catch (\PhpOffice\PhpSpreadsheet\Exception\InvalidFormatException $e) {
                    Log::error('Excel format error: ' . $e->getMessage());
                    return back()->with('error', 'The export file is corrupted or has invalid format. Please regenerate the export.');
                } catch (\Exception $e) {
                    Log::error('Download error: ' . $e->getMessage());
                    return back()->with('error', 'Failed to download file: ' . $e->getMessage());
                }
            }

            Log::error('Export file not found at path: ' . $filePath);
            return back()->with('error', 'Export file not found. Please regenerate the export.');
        } catch (\Exception $e) {
            Log::error('Error downloading export: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return back()->with('error', 'Failed to download export: ' . $e->getMessage());
        }
    }

    public function export_branch(Request $request)
    {
        set_time_limit(600); // Allow up to 10 minutes for export

        $billingPeriod = Auth::user()->billing_period ?? now()->format('Y-m-01');
        $branchId = Auth::user()->branch_id;
        $userId = Auth::id();

        // Validation: Only allow one export per user per month
        $alreadyExported = BillingExport::where('billing_period', $billingPeriod)
            ->where('generated_by', $userId)
            ->exists();
        if ($alreadyExported) {
            return back()->with('error', 'You have already generated billing for this month.');
        }

        // Generate the Excel file
        $export = new \App\Exports\BranchBillingExport($billingPeriod, $branchId);
        $filename = 'branch_billing_export_' . \Carbon\Carbon::parse($billingPeriod)->format('Y-m') . '.xlsx';

        // Store the file
        \Maatwebsite\Excel\Facades\Excel::store($export, 'exports/' . $filename, 'public');

        // Save export record
        $billingExport = BillingExport::create([
            'billing_period' => $billingPeriod,
            'filename' => $filename,
            'filepath' => 'exports/' . $filename,
            'generated_by' => $userId
        ]);

        // Add notification
        NotificationController::createNotification('billing_report', $userId, $billingExport->id);

        // Download the file
        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
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

    public function closeBillingPeriod(Request $request)
    {
        try {
            // Only allow admin and admin-msp
            if (!Auth::user() || !in_array(Auth::user()->role, ['admin', 'admin-msp'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only administrators can close billing periods.'
                ], 403);
            }

        $billingPeriod = Auth::user()->billing_period;

        // Check if we should retain dues
        $retainDues = \App\Models\BillingSetting::getBoolean('retain_dues_on_billing_close', false);

        Log::info('Closing billing period', [
            'billing_period' => $billingPeriod,
            'retain_dues' => $retainDues,
            'user_id' => Auth::id()
        ]);

        // LoanForecast reset
        $updateData = [
            'amount_due' => 0,
            'open_date' => null,
            'maturity_date' => null,
            'amortization_due_date' => null,
            'principal' => null,
            'interest' => null,
            'principal_due_status' => 'unpaid',
            'interest_due_status' => 'unpaid',
            'total_due_status' => 'unpaid',
            'total_due_after_remittance' => 0,
            'total_billed' => null,
        ];

        // Only reset dues if retain_dues is false
        if (!$retainDues) {
            $updateData['total_due'] = 0;
            $updateData['original_total_due'] = 0;
            $updateData['principal_due'] = 0;
            $updateData['interest_due'] = 0;
            $updateData['original_principal_due'] = 0;
            $updateData['original_interest_due'] = 0;
        }

        \App\Models\LoanForecast::where('billing_period', $billingPeriod)
            ->update($updateData);

        // Savings reset (keep only specified fields)
        \App\Models\Saving::query()->update([
            'open_date' => null,
            'current_balance' => null,
            'available_balance' => null,
            'interest' => null,
            'remittance_amount' => null,
        ]);

        // Shares reset (keep only specified fields)
        \App\Models\Shares::query()->update([
            'open_date' => null,
            'current_balance' => null,
            'available_balance' => null,
            'interest' => null,
        ]);

        // Clear remittance batches for the new billing period
        \App\Models\RemittanceBatch::where('billing_period', $billingPeriod)->delete();
        \App\Models\RemittanceReport::where('period', $billingPeriod)->delete();
        \App\Models\RemittancePreview::where('billing_period', $billingPeriod)->delete();
        \App\Models\RemittanceUploadCount::where('billing_period', $billingPeriod)->delete();
        \App\Models\LoanPayment::truncate();

        // Clear special billings for the new billing period
        try {
            \App\Models\SpecialBilling::query()->delete();
        } catch (\Exception $e) {
            Log::warning('Could not clear SpecialBilling table: ' . $e->getMessage());
        }

        try {
            \App\Models\LoanRemittance::query()->delete();
        } catch (\Exception $e) {
            Log::warning('Could not clear LoanRemittance table: ' . $e->getMessage());
        }

        try {
            \App\Models\AtmPayment::query()->delete();
        } catch (\Exception $e) {
            Log::warning('Could not clear AtmPayment table: ' . $e->getMessage());
        }

        try {
            \App\Models\Remittance::query()->delete();
        } catch (\Exception $e) {
            Log::warning('Could not clear AtmPayment table: ' . $e->getMessage());
        }

        // Clear billing exports for the closed billing period
        try {
            \App\Models\BillingExport::where('billing_period', $billingPeriod)->delete();
        } catch (\Exception $e) {
            Log::warning('Could not clear BillingExport table: ' . $e->getMessage());
        }


        // Members reset (keep only cid and member_tagging)
        \App\Models\Member::query()->update([
            'savings_balance' => 0,
            'share_balance' => 0,
            'loan_balance' => 0,
            'principal' => 0,
            'regular_principal' => 0,
            'special_principal' => 0,
            'start_date' => null,
            'end_date' => null,
            'status' => 'active',
            'approval_no' => null,
            'start_hold' => null,
            'expiry_date' => null,
            'account_status' => 'deduction',
        ]);

        // Update member_tagging from "New" to "PGB" for all members
        \App\Models\Member::where('member_tagging', 'New')->update(['member_tagging' => 'PGB']);

        // Get the current max billing period among users
        $currentPeriod = \App\Models\User::max('billing_period');
        $current = \Carbon\Carbon::parse($currentPeriod);
        $next = $current->copy()->addMonth()->format('Y-m-01');

        // Update all users' billing_period
        \App\Models\User::query()->update(['billing_period' => $next]);
        // Set all branch and admin users' approval statuses to pending
        \App\Models\User::whereIn('role', ['branch', 'admin'])->update([
            'billing_approval_status' => 'pending',
            'special_billing_approval_status' => 'pending'
        ]);

        // Notify all users
        $userIds = \App\Models\User::pluck('id');
        foreach ($userIds as $id) {
            \App\Models\Notification::create([
                'type' => 'billing_period_closed',
                'user_id' => $id,
                'related_id' => $id,
                'message' => 'The billing period has been closed. Please check your records for the new period.',
                'billing_period' => $next
            ]);
        }

        // Re-enable all edit buttons for the closed billing period
        \App\Models\ExportStatus::reEnableAllEdits($billingPeriod);

        // Logout the current user
        Auth::logout();

        return response()->json([
            'success' => true,
            'message' => 'Billing period closed and records reset for new period. You will be logged out.',
            'logout' => true
        ]);

        } catch (\Exception $e) {
            Log::error('Error closing billing period: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'billing_period' => Auth::user()->billing_period ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while closing the billing period. Please try again or contact support.'
            ], 500);
        }
    }

    public function checkExportStatus(Request $request)
    {
        $billingPeriod = Auth::user()->billing_period ?? now()->format('Y-m-01');

        // Check if there's any billing export record for this billing period
        $hasExport = \App\Models\BillingExport::where('billing_period', $billingPeriod)->exists();

        return response()->json([
            'hasExport' => $hasExport,
            'billingPeriod' => $billingPeriod
        ]);
    }

    public function toggleRetainDues(Request $request)
    {
        try {
            // Only allow admin and admin-msp users to toggle this setting
            if (!Auth::user() || !in_array(Auth::user()->role, ['admin', 'admin-msp'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only administrators can modify this setting.'
                ], 403);
            }

            // Get current setting
            $currentValue = \App\Models\BillingSetting::getBoolean('retain_dues_on_billing_close', false);

            // Toggle the setting
            $newValue = !$currentValue;
            \App\Models\BillingSetting::setBoolean(
                'retain_dues_on_billing_close',
                $newValue,
                'Whether to retain total_due, principal_due, interest_due and their original values when closing billing period'
            );

            return response()->json([
                'success' => true,
                'retain_dues' => $newValue,
                'message' => $newValue ?
                    'All dues (total, principal, interest) will be retained when closing billing period' :
                    'All dues (total, principal, interest) will be reset when closing billing period'
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling retain dues setting: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the setting. Please try again.'
            ], 500);
        }
    }

    public function exportMemberDeductionDetails()
    {
        $billingPeriod = Auth::user()->billing_period;

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MemberDeductionDetailsExport($billingPeriod),
            'Member_Deduction_Details_' . $billingPeriod . '_' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
