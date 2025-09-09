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
use App\Imports\BranchLoanForecastImport;
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
     * Clean up temporary files after import
     */
    private function cleanupTempFiles($originalTempPath, $importFilePath)
    {
        try {
            // Clean up original temp file
            if (file_exists($originalTempPath)) {
                unlink($originalTempPath);
            }

            // Clean up cleaned CSV file if it's different from original
            if ($importFilePath !== $originalTempPath && file_exists($importFilePath)) {
                unlink($importFilePath);
            }
        } catch (\Exception $e) {
            // Log error but don't throw - cleanup failure shouldn't break the import
            Log::warning('Failed to cleanup temporary files: ' . $e->getMessage());
        }
    }

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

    /**
     * Create the appropriate import class based on file type and request parameters
     */
    private function createImportClass($importClass, $billingPeriod, $request, $fileType)
    {
        // Handle branch-specific installment forecast import
        if ($fileType === 'Installment File' && $request->input('forecast_type') === 'branch') {
            $branchId = $request->input('branch_id');

            if ($importClass === \App\Imports\LoanForecastImport::class) {
                return new \App\Imports\BranchLoanForecastImport($billingPeriod, $branchId);
            }
        }

        // Default case - use the original import class
        if (in_array($fileType, ['CIF', 'Installment File', 'Savings', 'Shares', 'Loan'])) {
            return new $importClass($billingPeriod);
        } else {
            return new $importClass();
        }
    }

    public function store(Request $request)
    {
        ini_set('max_execution_time', 2000);
        ini_set('memory_limit', '2G'); // Increase memory limit to 1GB

        $user = Auth::user();
        $request->validate([
            'file'           => 'nullable|file',
            'savings_file'   => 'nullable|file',
            'shares_file'    => 'nullable|file',
            'cif_file'       => 'nullable|file',
            'loan_file'      => 'nullable|file',
            'forecast_type'  => 'nullable|in:consolidated,branch',
            'branch_id'      => 'nullable|required_if:forecast_type,branch|exists:branches,id',
        ]);

        try {
            // Ensure tmp_uploads directory exists
            $tmpUploadsDir = storage_path('app/tmp_uploads');
            if (!file_exists($tmpUploadsDir)) {
                mkdir($tmpUploadsDir, 0777, true);
            }

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
                    $file = $request->file($field);

                    // Validate file before processing
                    if (!$file->isValid()) {
                        throw new \Exception("Invalid file upload for {$options['type']}. Please try again.");
                    }

                    // Validate file extension
                    $extension = strtolower($file->getClientOriginalExtension());
                    $allowedExtensions = ['xlsx', 'xls', 'csv'];
                    if (!in_array($extension, $allowedExtensions)) {
                        throw new \Exception("Invalid file format for {$options['type']}. Please upload .xlsx, .xls, or .csv files only.");
                    }

                    // Save uploaded file to temp location
                    $tempPath = $file->storeAs('tmp_uploads', uniqid() . '-' . $file->getClientOriginalName(), 'local');
                    $fullTempPath = storage_path('app/' . $tempPath);

                    // Debug: Check if file was actually saved
                    if (!file_exists($fullTempPath)) {
                        throw new \Exception("File was not saved to expected location: {$fullTempPath}. Temp path: {$tempPath}");
                    }

                    $importFilePath = $fullTempPath;
                    // If CSV, clean and re-save as UTF-8 CSV
                    if ($extension === 'csv') {
                        $cleanPath = storage_path('app/tmp_uploads/cleaned_' . uniqid() . '.csv');

                        // Debug: Check if we can open the source file
                        if (!is_readable($fullTempPath)) {
                            throw new \Exception("Cannot read source file: {$fullTempPath}. File exists: " . (file_exists($fullTempPath) ? 'Yes' : 'No'));
                        }

                        $input = fopen($fullTempPath, 'r');
                        if (!$input) {
                            throw new \Exception("Failed to open source file for reading: {$fullTempPath}");
                        }

                        $output = fopen($cleanPath, 'w');
                        if (!$output) {
                            fclose($input);
                            throw new \Exception("Failed to create cleaned file: {$cleanPath}");
                        }

                        while (($row = fgetcsv($input)) !== false) {
                            if ($row && stripos(implode('', $row), '<html') === false) {
                                // Remove BOM from first cell if present
                                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
                                fputcsv($output, $row);
                            }
                        }
                        fclose($input);
                        fclose($output);
                        $importFilePath = $cleanPath;
                    }

                    // Try to process the file first before storing
                    try {
                        // Debug: Check what we're creating
                        $forecastType = $request->input('forecast_type');
                        $branchId = $request->input('branch_id');

                        if ($options['type'] === 'Installment File') {
                            Log::info("Creating import for Installment File - forecast_type: {$forecastType}, branch_id: {$branchId}");
                        }

                        $importClass = $this->createImportClass($options['import'], $billingPeriod, $request, $options['type']);
                        Log::info("Import class created: " . get_class($importClass));

                        // Use different import methods based on file type with better error handling
                        if ($extension === 'csv') {
                            Excel::import($importClass, $importFilePath, null, \Maatwebsite\Excel\Excel::CSV, [
                                'delimiter' => ',',
                                'enclosure' => '"',
                                'escape_character' => '\\',
                                'contiguous' => false,
                                'input_encoding' => 'UTF-8',
                            ]);
                        } else {
                            // For Excel files, try multiple approaches
                            try {
                                Excel::import($importClass, $file, null, \Maatwebsite\Excel\Excel::XLSX);
                            } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
                                // If XLSX fails, try XLS
                                if ($extension === 'xls') {
                                    Excel::import($importClass, $file, null, \Maatwebsite\Excel\Excel::XLS);
                                } else {
                                    throw $e;
                                }
                            }
                        }
                    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                        throw new \Exception("File validation failed for {$options['type']}: " . $e->getMessage());
                    } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
                        throw new \Exception("Invalid spreadsheet file for {$options['type']}. Please ensure the file is not corrupted and try saving it again as .xlsx format. Error: " . $e->getMessage());
                    } catch (\Exception $e) {
                        $errorMessage = $e->getMessage();
                        if (strpos($errorMessage, 'Invalid Spreadsheet file') !== false) {
                            throw new \Exception("The {$options['type']} file appears to be corrupted or in an unsupported format. Please try the following: 1) Open the file in Excel, 2) Save it as a new .xlsx file, 3) Upload the new file.");
                        } else {
                            throw new \Exception("Error processing {$options['type']} file: " . $errorMessage . '. Please try saving the file again in Excel and upload.');
                        }
                    }

                    // If processing was successful, clean up old files and store the new one
                    $this->cleanupOldFiles($options['type'], $billingPeriod);

                    $newFileName = time() . '-' . $file->getClientOriginalName();
                    $path = $file->storeAs('uploads/documents', $newFileName, 'public');

                    // Update document type for branch-specific installment forecast
                    $documentType = $options['type'];
                    if ($options['type'] === 'Installment File' && $request->input('forecast_type') === 'branch') {
                        $branch = \App\Models\Branch::find($request->input('branch_id'));
                        $documentType = 'Branch Forecast - ' . ($branch ? $branch->name : 'Branch');
                    }

                    $documentUpload = DocumentUpload::create([
                        'document_type'  => $documentType,
                        'filename'       => $newFileName,
                        'filepath'       => $path,
                        'mime_type'      => $file->getMimeType(),
                        'uploaded_by'    => Auth::id(),
                        'upload_date'    => now(),
                        'billing_period' => $billingPeriod,
                    ]);

                    // Add notification
                    NotificationController::createNotification('document_upload', Auth::id(), $documentUpload->id);

                    // Clean up temporary files
                    $this->cleanupTempFiles($fullTempPath, $importFilePath);
                }
            }

            return redirect()->back()->with('success', 'Files uploaded and data imported successfully!');
        } catch (Exception $e) {
            // Clean up temporary files even if import fails
            if (isset($fullTempPath) && isset($importFilePath)) {
                $this->cleanupTempFiles($fullTempPath, $importFilePath);
            }
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function store_branch(Request $request)
    {
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '1G'); // Increase memory limit to 1GB

        // Check if user is approved
        $user = Auth::user();
        // Check if ANY admin or branch user has approved (disables uploads for everyone)
        $hasApprovedUsers = User::whereIn('role', ['admin', 'branch'])
            ->where('billing_approval_status', 'approved')
            ->exists();

        if ($hasApprovedUsers) {
            return redirect()->back()->with('error', 'File uploads are disabled. One or more admin/branch users have approved billing.');
        }

        if ($user->role !== 'admin-msp' && $user->billing_approval_status !== 'pending') {
            return redirect()->back()->with('error', 'Your billing approval is approved. Upload is disabled for approved accounts.');
        }

        $request->validate([
            'file'           => 'nullable|file',
            'savings_file'   => 'nullable|file',
            'shares_file'    => 'nullable|file',
            'cif_file'       => 'nullable|file',
            'loan_file'      => 'nullable|file',
        ]);

        try {
            // Ensure tmp_uploads directory exists
            $tmpUploadsDir = storage_path('app/tmp_uploads');
            if (!file_exists($tmpUploadsDir)) {
                mkdir($tmpUploadsDir, 0777, true);
            }

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
                    $file = $request->file($field);

                    // Validate file before processing
                    if (!$file->isValid()) {
                        throw new \Exception("Invalid file upload for {$options['type']}. Please try again.");
                    }

                    // Validate file extension
                    $extension = strtolower($file->getClientOriginalExtension());
                    $allowedExtensions = ['xlsx', 'xls', 'csv'];
                    if (!in_array($extension, $allowedExtensions)) {
                        throw new \Exception("Invalid file format for {$options['type']}. Please upload .xlsx, .xls, or .csv files only.");
                    }

                    // Save uploaded file to temp location
                    $tempPath = $file->storeAs('tmp_uploads', uniqid() . '-' . $file->getClientOriginalName(), 'local');
                    $fullTempPath = storage_path('app/' . $tempPath);

                    // Debug: Check if file was actually saved
                    if (!file_exists($fullTempPath)) {
                        throw new \Exception("File was not saved to expected location: {$fullTempPath}. Temp path: {$tempPath}");
                    }

                    $importFilePath = $fullTempPath;
                    // If CSV, clean and re-save as UTF-8 CSV
                    if ($extension === 'csv') {
                        $cleanPath = storage_path('app/tmp_uploads/cleaned_' . uniqid() . '.csv');

                        // Debug: Check if we can open the source file
                        if (!is_readable($fullTempPath)) {
                            throw new \Exception("Cannot read source file: {$fullTempPath}. File exists: " . (file_exists($fullTempPath) ? 'Yes' : 'No'));
                        }

                        $input = fopen($fullTempPath, 'r');
                        if (!$input) {
                            throw new \Exception("Failed to open source file for reading: {$fullTempPath}");
                        }

                        $output = fopen($cleanPath, 'w');
                        if (!$output) {
                            fclose($input);
                            throw new \Exception("Failed to create cleaned file: {$cleanPath}");
                        }

                        while (($row = fgetcsv($input)) !== false) {
                            if ($row && stripos(implode('', $row), '<html') === false) {
                                // Remove BOM from first cell if present
                                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
                                fputcsv($output, $row);
                            }
                        }
                        fclose($input);
                        fclose($output);
                        $importFilePath = $cleanPath;
                    }

                    // Try to process the file first before storing
                    try {
                        if (in_array($options['type'], ['CIF', 'Installment File', 'Savings', 'Shares', 'Loan'])) {
                            $importClass = new $options['import']($billingPeriod);
                        } else {
                            $importClass = new $options['import']();
                        }

                        // Use different import methods based on file type with better error handling
                        if ($extension === 'csv') {
                            Excel::import($importClass, $importFilePath, null, \Maatwebsite\Excel\Excel::CSV, [
                                'delimiter' => ',',
                                'enclosure' => '"',
                                'escape_character' => '\\',
                                'contiguous' => false,
                                'input_encoding' => 'UTF-8',
                            ]);
                        } else {
                            // For Excel files, try multiple approaches
                            try {
                                Excel::import($importClass, $file, null, \Maatwebsite\Excel\Excel::XLSX);
                            } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
                                // If XLSX fails, try XLS
                                if ($extension === 'xls') {
                                    Excel::import($importClass, $file, null, \Maatwebsite\Excel\Excel::XLS);
                                } else {
                                    throw $e;
                                }
                            }
                        }
                    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                        throw new \Exception("File validation failed for {$options['type']}: " . $e->getMessage());
                    } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
                        throw new \Exception("Invalid spreadsheet file for {$options['type']}. Please ensure the file is not corrupted and try saving it again as .xlsx format. Error: " . $e->getMessage());
                    } catch (\Exception $e) {
                        $errorMessage = $e->getMessage();
                        if (strpos($errorMessage, 'Invalid Spreadsheet file') !== false) {
                            throw new \Exception("The {$options['type']} file appears to be corrupted or in an unsupported format. Please try the following: 1) Open the file in Excel, 2) Save it as a new .xlsx file, 3) Upload the new file.");
                        } else {
                            throw new \Exception("Error processing {$options['type']} file: " . $errorMessage . '. Please try saving the file again in Excel and upload.');
                        }
                    }

                    // If processing was successful, clean up old files and store the new one
                    $this->cleanupOldFiles($options['type'], $billingPeriod);

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

                    // Clean up temporary files
                    $this->cleanupTempFiles($fullTempPath, $importFilePath);
                }
            }

            return redirect()->back()->with('success', 'Files uploaded and data imported successfully!');
        } catch (Exception $e) {
            // Clean up temporary files even if import fails
            if (isset($fullTempPath) && isset($importFilePath)) {
                $this->cleanupTempFiles($fullTempPath, $importFilePath);
            }
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function index()
    {
        $user = Auth::user();
        $billingPeriod = $user->billing_period; // e.g. '2025-05'

        // For admin users, move validation to modal: always enable Upload button,
        // but compute branch approval statuses to drive per-option disabling in the modal
        $hasApprovedBranches = false;
        $branchStatuses = collect();
        // Check if ANY admin or branch user has approved (disables uploads for everyone)
        $hasApprovedUsers = User::whereIn('role', ['admin', 'branch'])
            ->where('billing_approval_status', 'approved')
            ->exists();

        if ($user->role === 'admin-msp') {
            // Admin-MSP always has full access, no approval needed
            $hasApprovedBranches = false;
            $branchStatuses = collect();
            $isApproved = !$hasApprovedUsers; // Disabled if any admin/branch approved
        } elseif ($user->role === 'admin') {
            // Admin needs billing approval status to be pending to upload
            $hasApprovedBranches = User::where('role', 'branch')
                ->where('billing_approval_status', 'approved')
                ->exists();
            $branchStatuses = User::where('role', 'branch')
                ->select('branch_id', 'billing_approval_status')
                ->get()
                ->groupBy('branch_id')
                ->map(function ($rows) {
                    // If any user for the branch is approved, treat branch as approved
                    return $rows->contains(function ($r) { return $r->billing_approval_status === 'approved'; }) ? 'approved' : 'pending';
                });
            $isApproved = $user->billing_approval_status === 'pending' && !$hasApprovedUsers;
        } else {
            // For branch users, check their own billing approval status
            $isApproved = $user->billing_approval_status === 'pending' && !$hasApprovedUsers;
        }

        // Get only the latest 5 files total across all document types
        $documents = DocumentUpload::where('billing_period', $billingPeriod)
            ->orderBy('upload_date', 'desc')
            ->limit(5)
            ->get();

        return view('components.admin.files.file_datatable', compact('documents', 'isApproved', 'hasApprovedBranches', 'branchStatuses'));
    }

    public function index_branch()
    {
        $user = Auth::user();
        $billingPeriod = $user->billing_period; // e.g. '2025-05'

        // Check if user billing approval is pending (enabled) or approved (disabled)
        $isApproved = $user->billing_approval_status === 'pending';

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
