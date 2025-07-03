<?php

namespace App\Http\Controllers;

use Exception;
use App\Imports\CifImport;
use App\Imports\LoanImport;
use Illuminate\Http\Request;
use App\Imports\SharesImport;
use App\Imports\SavingsImport;
use App\Models\DocumentUpload;
use App\Imports\LoanForecastImport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\NotificationController;
use Illuminate\Database\Eloquent\Collection;
use App\Models\User;

class DocumentUploadController extends Controller
{
    // Maximum number of files to keep per document type
    private const MAX_FILES_PER_TYPE = 12;

    /**
     * Clean up old files for a specific document type
     */
    private function cleanupOldFiles($documentType, $billingPeriod = null)
    {
        $query = DocumentUpload::where('document_type', $documentType);

        if ($billingPeriod) {
            $query->where('billing_period', $billingPeriod);
        }

        $files = $query->orderBy('upload_date', 'desc')->get();

        // If we have more than the maximum allowed files, delete the oldest ones
        if ($files->count() > self::MAX_FILES_PER_TYPE) {
            $filesToDelete = $files->slice(self::MAX_FILES_PER_TYPE);

            foreach ($filesToDelete as $file) {
                // Delete physical file from storage
                if (Storage::disk('public')->exists($file->filepath)) {
                    Storage::disk('public')->delete($file->filepath);
                }

                // Delete database record
                $file->delete();
            }

            Log::info("Cleaned up " . $filesToDelete->count() . " old files for document type: {$documentType}");
        }
    }

   public function store(Request $request)
{
    ini_set('max_execution_time', 8000);
    ini_set('memory_limit', '1G'); // Increase memory limit to 1GB

    // Check if user is approved
    $user = Auth::user();

    // For admin users, check if there are any branch users in approved status
    if ($user->role === 'admin') {
        $hasApprovedBranches = User::where('role', 'branch')
            ->where('status', 'approved')
            ->count() > 0;
        if ($hasApprovedBranches) {
            return redirect()->back()->with('error', 'File upload is disabled because one or more branch users have been approved.');
        }
    } else {
        // For branch users, check their own status
        if ($user->status !== 'pending') {
            return redirect()->back()->with('error', 'Your account is approved. Upload is disabled for approved accounts.');
        }
    }

    $request->validate([
        'file'           => 'nullable|file',
        'savings_file'   => 'nullable|file',
        'shares_file'    => 'nullable|file',
        'cif_file'       => 'nullable|file',
        'loan_file'      => 'nullable|file',
    ]);

    try {
        $billingPeriod = $user->billing_period ?? null;

        $uploadMappings = [
            'file'         => ['type' => 'Installment File', 'import' => LoanForecastImport::class],
            'savings_file' => ['type' => 'Savings', 'import' => SavingsImport::class],
            'shares_file'  => ['type' => 'Shares', 'import' => SharesImport::class],
            'cif_file'     => ['type' => 'CIF', 'import' => CifImport::class],
            'loan_file'    => ['type' => 'Loan', 'import' => LoanImport::class],
        ];

        foreach ($uploadMappings as $field => $options) {
            if ($request->hasFile($field)) {
                // Clean up old files for this document type before uploading new one
                $this->cleanupOldFiles($options['type'], $billingPeriod);

                $file = $request->file($field);
                $newFileName = time() . '-' . $file->getClientOriginalName();
                $path = $file->storeAs('uploads/documents', $newFileName, 'public');

                $documentUpload = DocumentUpload::create([
                    'document_type'  => $options['type'],
                    'filename'       => $newFileName,
                    'filepath'       => $path,
                    'mime_type'      => $file->getClientMimeType(),
                    'uploaded_by'    => Auth::id(),
                    'upload_date'    => now(),
                    'billing_period' => $billingPeriod,
                ]);

                // Add notification
                NotificationController::createNotification('document_upload', Auth::id(), $documentUpload->id);

                if (in_array($options['type'], ['CIF', 'Installment File', 'Savings', 'Shares', 'Loan'])) {
                    $importClass = new $options['import']($billingPeriod);
                } else {
                    $importClass = new $options['import']();
                }
                Excel::import($importClass, $file);
            }
        }

        return redirect()->back()->with('success', 'Files uploaded and data imported successfully!');
    } catch (Exception $e) {
        return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
    }
}

 public function store_branch(Request $request)
{
    ini_set('max_execution_time', 600);
    ini_set('memory_limit', '1G'); // Increase memory limit to 1GB

    // Check if user is approved
    $user = Auth::user();
    if ($user->status !== 'pending') {
        return redirect()->back()->with('error', 'Your account is approved. Upload is disabled for approved accounts.');
    }

    $request->validate([
        'file'           => 'nullable|file',
        'savings_file'   => 'nullable|file',
        'shares_file'    => 'nullable|file',
        'cif_file'       => 'nullable|file',
        'loan_file'      => 'nullable|file',
    ]);

    try {
        $billingPeriod = $user->billing_period ?? null;

        $uploadMappings = [
            'cif_file'     => ['type' => 'CIF', 'import' => CifImport::class],
            'file'         => ['type' => 'Installment File', 'import' => LoanForecastImport::class],
            'savings_file' => ['type' => 'Savings', 'import' => SavingsImport::class],
            'shares_file'  => ['type' => 'Shares', 'import' => SharesImport::class],
            'loan_file'    => ['type' => 'Loan', 'import' => LoanImport::class],
        ];

        foreach ($uploadMappings as $field => $options) {
            if ($request->hasFile($field)) {
                // Clean up old files for this document type before uploading new one
                $this->cleanupOldFiles($options['type'], $billingPeriod);

                $file = $request->file($field);
                $newFileName = time() . '-' . $file->getClientOriginalName();
                $path = $file->storeAs('uploads/documents', $newFileName, 'public');

                $documentUpload = DocumentUpload::create([
                    'document_type'  => $options['type'],
                    'filename'       => $newFileName,
                    'filepath'       => $path,
                    'mime_type'      => $file->getClientMimeType(),
                    'uploaded_by'    => Auth::id(),
                    'upload_date'    => now(),
                    'billing_period' => $billingPeriod,
                ]);

                // Add notification
                NotificationController::createNotification('document_upload', Auth::id(), $documentUpload->id);

                if (in_array($options['type'], ['CIF', 'Installment File', 'Savings', 'Shares', 'Loan'])) {
                    $importClass = new $options['import']($billingPeriod);
                } else {
                    $importClass = new $options['import']();
                }
                Excel::import($importClass, $file);
            }
        }

        return redirect()->back()->with('success', 'Files uploaded and data imported successfully!');
    } catch (Exception $e) {
        return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
    }
}


    public function index()
    {
        $user = Auth::user();
        $billingPeriod = $user->billing_period; // e.g. '2025-05'

        // For admin users, check if there are any branch users in approved status
        if ($user->role === 'admin') {
            $hasApprovedBranches = User::where('role', 'branch')
                ->where('status', 'approved')
                ->count() > 0;
            $isApproved = !$hasApprovedBranches; // Upload is enabled only if NO branch users are approved
        } else {
            // For branch users, check their own status
            $isApproved = $user->status === 'pending';
        }

        // Get only the latest 5 files total across all document types
        $documents = DocumentUpload::where('billing_period', $billingPeriod)
            ->orderBy('upload_date', 'desc')
            ->limit(5)
            ->get();

        return view('components.admin.files.file_datatable', compact('documents', 'isApproved'));
    }

    public function index_branch()
    {
        $user = Auth::user();
        $billingPeriod = $user->billing_period; // e.g. '2025-05'

        // Check if user is pending (enabled) or approved (disabled)
        $isApproved = $user->status === 'pending';

        // Get only the latest 5 files total across all document types
        $documents = DocumentUpload::where('billing_period', $billingPeriod)
            ->orderBy('upload_date', 'desc')
            ->limit(5)
            ->get();

        return view('components.branch.files.file_datatable', compact('documents', 'isApproved'));
    }

    /**
     * Clean up all old files across all document types
     * This can be called manually or via a scheduled command
     */
    public function cleanupAllOldFiles()
    {
        $documentTypes = ['Installment File', 'Savings', 'Shares', 'CIF', 'Loan'];
        $totalDeleted = 0;

        foreach ($documentTypes as $type) {
            $query = DocumentUpload::where('document_type', $type);
            $files = $query->orderBy('upload_date', 'desc')->get();

            if ($files->count() > self::MAX_FILES_PER_TYPE) {
                $filesToDelete = $files->slice(self::MAX_FILES_PER_TYPE);

                foreach ($filesToDelete as $file) {
                    if (Storage::disk('public')->exists($file->filepath)) {
                        Storage::disk('public')->delete($file->filepath);
                    }
                    $file->delete();
                }

                $totalDeleted += $filesToDelete->count();
                Log::info("Cleaned up " . $filesToDelete->count() . " old files for document type: {$type}");
            }
        }

        return $totalDeleted;
    }

    /**
     * Get storage statistics for monitoring
     */
    public function getStorageStats()
    {
        $stats = [];
        $documentTypes = ['Installment File', 'Savings', 'Shares', 'CIF', 'Loan'];

        foreach ($documentTypes as $type) {
            $files = DocumentUpload::where('document_type', $type)->get();
            $totalSize = 0;

            foreach ($files as $file) {
                if (Storage::disk('public')->exists($file->filepath)) {
                    $totalSize += Storage::disk('public')->size($file->filepath);
                }
            }

            $stats[$type] = [
                'count' => $files->count(),
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'oldest_file' => $files->min('upload_date'),
                'newest_file' => $files->max('upload_date'),
            ];
        }

        return $stats;
    }
}
