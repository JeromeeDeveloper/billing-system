<?php

namespace App\Http\Controllers;

use App\Services\FileRetentionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\DocumentUpload;
use App\Models\BillingExport;
use Illuminate\Support\Facades\Storage;

class FileRetentionController extends Controller
{
    protected $fileRetentionService;

    public function __construct(FileRetentionService $fileRetentionService)
    {
        $this->fileRetentionService = $fileRetentionService;
    }

    /**
     * Display the file retention dashboard
     */
    public function index()
    {
        $stats = $this->fileRetentionService->getStorageStats();
        $maxFilesPerType = $this->fileRetentionService->getMaxFilesPerType();
        $maxBillingExports = $this->fileRetentionService->getMaxBillingExports();

        return view('components.admin.files.retention_dashboard', compact('stats', 'maxFilesPerType', 'maxBillingExports'));
    }

    /**
     * Get files for a specific document type
     */
    public function getFiles(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string',
        ]);

        $documentType = $request->input('document_type');

        if ($documentType === 'Billing Exports') {
            $files = BillingExport::with('user')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($export) {
                    return [
                        'id' => $export->id,
                        'filename' => $export->filename,
                        'filepath' => $export->filepath,
                        'size' => Storage::disk('public')->exists($export->filepath) ? Storage::disk('public')->size($export->filepath) : 0,
                        'upload_date' => $export->created_at,
                        'uploaded_by_name' => $export->user ? $export->user->name : 'Unknown',
                        'billing_period' => $export->billing_period,
                        'download_url' => route('file.retention.download', ['type' => 'billing', 'id' => $export->id])
                    ];
                });
        } else {
            $files = DocumentUpload::with('user')
                ->where('document_type', $documentType)
                ->orderBy('upload_date', 'desc')
                ->get()
                ->map(function ($upload) {
                    return [
                        'id' => $upload->id,
                        'filename' => $upload->filename,
                        'filepath' => $upload->filepath,
                        'size' => Storage::disk('public')->exists($upload->filepath) ? Storage::disk('public')->size($upload->filepath) : 0,
                        'upload_date' => $upload->upload_date,
                        'uploaded_by_name' => $upload->user ? $upload->user->name : 'Unknown',
                        'billing_period' => $upload->billing_period,
                        'download_url' => route('file.retention.download', ['type' => 'document', 'id' => $upload->id])
                    ];
                });
        }

        return response()->json([
            'success' => true,
            'files' => $files
        ]);
    }

    /**
     * Download a file
     */
    public function download(Request $request, $type, $id)
    {
        try {
            if ($type === 'billing') {
                $file = BillingExport::findOrFail($id);
            } else {
                $file = DocumentUpload::findOrFail($id);
            }

            if (!Storage::disk('public')->exists($file->filepath)) {
                abort(404, 'File not found');
            }

            return response()->download(storage_path('app/public/' . $file->filepath), $file->filename);
        } catch (\Exception $e) {
            Log::error('File download error: ' . $e->getMessage());
            abort(404, 'File not found');
        }
    }

    /**
     * Clean up old files for a specific document type
     */
    public function cleanupType(Request $request)
    {
        $request->validate([
            'document_type' => 'nullable|string',
        ]);

        $documentType = $request->input('document_type');

        if ($documentType === 'Billing Exports') {
            $deletedCount = $this->fileRetentionService->cleanupOldBillingExports();
            $message = "Cleaned up {$deletedCount} old billing export files";
        } else {
            $deletedCount = $this->fileRetentionService->cleanupOldFiles($documentType);
            $message = "Cleaned up {$deletedCount} old {$documentType} files";
        }

        Log::info("Manual cleanup: {$message}");

        return response()->json([
            'success' => true,
            'message' => $message,
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Clean up all old files
     */
    public function cleanupAll(Request $request)
    {
        $stats = $this->fileRetentionService->cleanupAllOldFiles();

        $message = "Cleaned up {$stats['total_deleted']} old files total";
        if ($stats['total_freed_space'] > 0) {
            $message .= " and freed " . round($stats['total_freed_space'] / 1024 / 1024, 2) . " MB";
        }

        Log::info("Manual cleanup all: {$message}");

        return response()->json([
            'success' => true,
            'message' => $message,
            'stats' => $stats
        ]);
    }

    /**
     * Get updated statistics
     */
    public function getStats()
    {
        $stats = $this->fileRetentionService->getStorageStats();
        $maxFilesPerType = $this->fileRetentionService->getMaxFilesPerType();
        $maxBillingExports = $this->fileRetentionService->getMaxBillingExports();

        return response()->json([
            'stats' => $stats,
            'maxFilesPerType' => $maxFilesPerType,
            'maxBillingExports' => $maxBillingExports
        ]);
    }

    /**
     * Preview files that would be deleted for a specific type
     */
    public function previewCleanup(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string',
        ]);

        $documentType = $request->input('document_type');
        $stats = $this->fileRetentionService->getStorageStats();

        if ($documentType === 'Billing Exports') {
            $typeStats = $stats['Billing Exports'] ?? null;
            $maxFiles = $this->fileRetentionService->getMaxBillingExports();
        } else {
            $typeStats = $stats[$documentType] ?? null;
            $maxFiles = $this->fileRetentionService->getMaxFilesPerType();
        }

        if (!$typeStats) {
            return response()->json([
                'success' => false,
                'message' => "No data found for {$documentType}"
            ]);
        }

        $filesToDelete = [];
        if ($typeStats['files_over_limit'] > 0) {
            if ($documentType === 'Billing Exports') {
                $files = BillingExport::orderBy('created_at', 'asc')->get();
                $filesToDelete = $files->slice($maxFiles)->map(function ($file) {
                    return [
                        'filename' => $file->filename,
                        'size' => Storage::disk('public')->exists($file->filepath) ? Storage::disk('public')->size($file->filepath) : 0,
                        'created_at' => $file->created_at->format('Y-m-d H:i:s')
                    ];
                });
            } else {
                $files = DocumentUpload::where('document_type', $documentType)
                    ->orderBy('upload_date', 'asc')
                    ->get();
                $filesToDelete = $files->slice($maxFiles)->map(function ($file) {
                    return [
                        'filename' => $file->filename,
                        'size' => Storage::disk('public')->exists($file->filepath) ? Storage::disk('public')->size($file->filepath) : 0,
                        'upload_date' => $file->upload_date->format('Y-m-d H:i:s')
                    ];
                });
            }
        }

        return response()->json([
            'success' => true,
                'document_type' => $documentType,
            'current_count' => $typeStats['count'],
            'max_files' => $maxFiles,
                'files_over_limit' => $typeStats['files_over_limit'],
            'files_to_delete' => $filesToDelete,
            'total_size_to_free' => $filesToDelete->sum('size')
        ]);
    }

    /**
     * Create a backup zip file containing all files
     */
    public function createBackup()
    {
        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupFilename = "file_retention_backup_{$timestamp}.zip";
            $backupPath = "backups/{$backupFilename}";

            // Create backups directory if it doesn't exist
            if (!Storage::disk('public')->exists('backups')) {
                Storage::disk('public')->makeDirectory('backups');
            }

            // Create a new ZipArchive
            $zip = new \ZipArchive();
            $zipPath = storage_path('app/public/' . $backupPath);

            if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Could not create ZIP file');
            }

            $totalFiles = 0;
            $totalSize = 0;

            // Add document upload files
            $documentUploads = DocumentUpload::with('user')->get();
            foreach ($documentUploads as $upload) {
                if (Storage::disk('public')->exists($upload->filepath)) {
                    $filePath = storage_path('app/public/' . $upload->filepath);
                    $zipPath = "document_uploads/{$upload->document_type}/{$upload->filename}";

                    if ($zip->addFile($filePath, $zipPath)) {
                        $totalFiles++;
                        $totalSize += Storage::disk('public')->size($upload->filepath);
                    }
                }
            }

            // Add billing export files
            $billingExports = BillingExport::with('user')->get();
            foreach ($billingExports as $export) {
                if (Storage::disk('public')->exists($export->filepath)) {
                    $filePath = storage_path('app/public/' . $export->filepath);
                    $zipPath = "billing_exports/{$export->filename}";

                    if ($zip->addFile($filePath, $zipPath)) {
                        $totalFiles++;
                        $totalSize += Storage::disk('public')->size($export->filepath);
                    }
                }
            }

            // Add a manifest file with file information
            $manifest = [
                'backup_created_at' => now()->toISOString(),
                'total_files' => $totalFiles,
                'total_size_bytes' => $totalSize,
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'document_uploads' => $documentUploads->count(),
                'billing_exports' => $billingExports->count(),
                'files' => []
            ];

            // Add file details to manifest
            foreach ($documentUploads as $upload) {
                $manifest['files'][] = [
                    'type' => 'document_upload',
                    'document_type' => $upload->document_type,
                    'filename' => $upload->filename,
                    'filepath' => $upload->filepath,
                    'upload_date' => $upload->upload_date,
                    'uploaded_by' => $upload->user ? $upload->user->name : 'Unknown',
                    'billing_period' => $upload->billing_period,
                    'size_bytes' => Storage::disk('public')->exists($upload->filepath) ? Storage::disk('public')->size($upload->filepath) : 0
                ];
            }

            foreach ($billingExports as $export) {
                $manifest['files'][] = [
                    'type' => 'billing_export',
                    'filename' => $export->filename,
                    'filepath' => $export->filepath,
                    'created_at' => $export->created_at,
                    'generated_by' => $export->user ? $export->user->name : 'Unknown',
                    'billing_period' => $export->billing_period,
                    'size_bytes' => Storage::disk('public')->exists($export->filepath) ? Storage::disk('public')->size($export->filepath) : 0
                ];
            }

            // Add manifest to zip
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

            $zip->close();

            // Create notification about backup creation
            \App\Models\Notification::create([
                'type' => 'file_backup',
                'user_id' => Auth::id(),
                'related_id' => Auth::id(),
                'message' => "File retention backup created: {$backupFilename} ({$totalFiles} files, " . round($totalSize / 1024 / 1024, 2) . " MB)",
                'billing_period' => Auth::user()->billing_period
            ]);

            Log::info("File retention backup created: {$backupFilename} with {$totalFiles} files, " . round($totalSize / 1024 / 1024, 2) . " MB");

            return response()->json([
                'success' => true,
                'message' => "Backup created successfully: {$backupFilename}",
                'filename' => $backupFilename,
                'filepath' => $backupPath,
                'total_files' => $totalFiles,
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'download_url' => route('file.retention.download.backup', ['filename' => $backupFilename])
            ]);

        } catch (\Exception $e) {
            Log::error('File retention backup error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download a backup file
     */
    public function downloadBackup($filename)
    {
        try {
            $backupPath = "backups/{$filename}";

            if (!Storage::disk('public')->exists($backupPath)) {
                abort(404, 'Backup file not found');
            }

            return response()->download(storage_path('app/public/' . $backupPath), $filename);
        } catch (\Exception $e) {
            Log::error('Backup download error: ' . $e->getMessage());
            abort(404, 'Backup file not found');
        }
    }

    // ===== Branch-specific File Retention Methods =====
    public function index_branch()
    {
        $user = Auth::user();
        // Only show files uploaded by users in this branch
        $documentTypes = [
            'Installment File',
            'Savings',
            'Shares',
            'CIF',
            'Loan',
            'CoreID',
            'Savings & Shares Product'
        ];
        $stats = [];
        foreach ($documentTypes as $type) {
            $files = \App\Models\DocumentUpload::where('document_type', $type)
                ->get();
            $totalSize = 0;
            $fileCount = $files->count();
            foreach ($files as $file) {
                if (\Storage::disk('public')->exists($file->filepath)) {
                    $totalSize += \Storage::disk('public')->size($file->filepath);
                }
            }
            $stats[$type] = [
                'count' => $fileCount,
                'total_size_bytes' => $totalSize,
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'oldest_file' => $files->min('upload_date'),
                'newest_file' => $files->max('upload_date'),
                'at_limit' => $fileCount >= 12,
                'files_over_limit' => max(0, $fileCount - 12)
            ];
        }
        // Add Billing Exports
        $billingExports = \App\Models\BillingExport::all();
        $billingExportsSize = 0;
        $billingExportsCount = $billingExports->count();
        foreach ($billingExports as $export) {
            if (\Storage::disk('public')->exists($export->filepath)) {
                $billingExportsSize += \Storage::disk('public')->size($export->filepath);
            }
        }
        $stats['Billing Exports'] = [
            'count' => $billingExportsCount,
            'total_size_bytes' => $billingExportsSize,
            'total_size_mb' => round($billingExportsSize / 1024 / 1024, 2),
            'oldest_file' => $billingExports->min('created_at'),
            'newest_file' => $billingExports->max('created_at'),
            'at_limit' => $billingExportsCount >= 12,
            'files_over_limit' => max(0, $billingExportsCount - 12)
        ];
        $maxFilesPerType = 12;
        $maxBillingExports = 12;
        return view('components.branch.files.retention_dashboard', compact('stats', 'maxFilesPerType', 'maxBillingExports'));
    }

    public function getFiles_branch(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string',
        ]);

        $documentType = $request->input('document_type');

        if ($documentType === 'Billing Exports') {
            $files = \App\Models\BillingExport::with('user')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($export) {
                    return [
                        'id' => $export->id,
                        'filename' => $export->filename,
                        'filepath' => $export->filepath,
                        'size' => \Storage::disk('public')->exists($export->filepath) ? \Storage::disk('public')->size($export->filepath) : 0,
                        'upload_date' => $export->created_at,
                        'uploaded_by_name' => $export->user ? $export->user->name : 'Unknown',
                        'billing_period' => $export->billing_period,
                        'download_url' => route('file.retention.download.branch', ['type' => 'billing', 'id' => $export->id])
                    ];
                });
        } else {
            $files = \App\Models\DocumentUpload::with('user')
                ->where('document_type', $documentType)
                ->orderBy('upload_date', 'desc')
                ->get()
                ->map(function ($upload) {
                    return [
                        'id' => $upload->id,
                        'filename' => $upload->filename,
                        'filepath' => $upload->filepath,
                        'size' => \Storage::disk('public')->exists($upload->filepath) ? \Storage::disk('public')->size($upload->filepath) : 0,
                        'upload_date' => $upload->upload_date,
                        'uploaded_by_name' => $upload->user ? $upload->user->name : 'Unknown',
                        'billing_period' => $upload->billing_period,
                        'download_url' => route('file.retention.download.branch', ['type' => 'document', 'id' => $upload->id])
                    ];
                });
        }

        return response()->json([
            'success' => true,
            'files' => $files
        ]);
    }

    public function download_branch(Request $request, $type, $id)
    {
        try {
            if ($type === 'billing') {
                $file = \App\Models\BillingExport::findOrFail($id);
            } else {
                $file = \App\Models\DocumentUpload::findOrFail($id);
            }

            if (!\Storage::disk('public')->exists($file->filepath)) {
                abort(404, 'File not found');
            }

            return response()->download(storage_path('app/public/' . $file->filepath), $file->filename);
        } catch (\Exception $e) {
            \Log::error('Branch file download error: ' . $e->getMessage());
            abort(404, 'File not found');
        }
    }

    public function cleanupType_branch(Request $request)
    {
        // TODO: Implement branch-specific logic
        return response()->json(['success' => false, 'message' => 'Not implemented']);
    }

    public function cleanupAll_branch(Request $request)
    {
        // TODO: Implement branch-specific logic
        return response()->json(['success' => false, 'message' => 'Not implemented']);
    }

    public function getStats_branch()
    {
        // TODO: Implement branch-specific logic
        return response()->json(['success' => false, 'message' => 'Not implemented']);
    }

    public function previewCleanup_branch(Request $request)
    {
        // TODO: Implement branch-specific logic
        return response()->json(['success' => false, 'message' => 'Not implemented']);
    }

    public function createBackup_branch(Request $request)
    {
        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupFilename = "branch_file_retention_backup_{$timestamp}.zip";
            $backupPath = "backups/{$backupFilename}";

            // Create backups directory if it doesn't exist
            if (!\Storage::disk('public')->exists('backups')) {
                \Storage::disk('public')->makeDirectory('backups');
            }

            $zip = new \ZipArchive();
            $zipPath = storage_path('app/public/' . $backupPath);

            if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Could not create ZIP file');
            }

            $totalFiles = 0;
            $totalSize = 0;

            // Add document upload files
            $documentUploads = \App\Models\DocumentUpload::with('user')->get();
            foreach ($documentUploads as $upload) {
                if (\Storage::disk('public')->exists($upload->filepath)) {
                    $filePath = storage_path('app/public/' . $upload->filepath);
                    $zipPathInZip = "document_uploads/{$upload->document_type}/{$upload->filename}";

                    if ($zip->addFile($filePath, $zipPathInZip)) {
                        $totalFiles++;
                        $totalSize += \Storage::disk('public')->size($upload->filepath);
                    }
                }
            }

            // Add billing export files
            $billingExports = \App\Models\BillingExport::with('user')->get();
            foreach ($billingExports as $export) {
                if (\Storage::disk('public')->exists($export->filepath)) {
                    $filePath = storage_path('app/public/' . $export->filepath);
                    $zipPathInZip = "billing_exports/{$export->filename}";

                    if ($zip->addFile($filePath, $zipPathInZip)) {
                        $totalFiles++;
                        $totalSize += \Storage::disk('public')->size($export->filepath);
                    }
                }
            }

            // Add a manifest file with file information
            $manifest = [
                'backup_created_at' => now()->toISOString(),
                'total_files' => $totalFiles,
                'total_size_bytes' => $totalSize,
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'document_uploads' => $documentUploads->count(),
                'billing_exports' => $billingExports->count(),
                'files' => []
            ];

            foreach ($documentUploads as $upload) {
                $manifest['files'][] = [
                    'type' => 'document_upload',
                    'document_type' => $upload->document_type,
                    'filename' => $upload->filename,
                    'filepath' => $upload->filepath,
                    'upload_date' => $upload->upload_date,
                    'uploaded_by' => $upload->user ? $upload->user->name : 'Unknown',
                    'billing_period' => $upload->billing_period,
                    'size_bytes' => \Storage::disk('public')->exists($upload->filepath) ? \Storage::disk('public')->size($upload->filepath) : 0
                ];
            }

            foreach ($billingExports as $export) {
                $manifest['files'][] = [
                    'type' => 'billing_export',
                    'filename' => $export->filename,
                    'filepath' => $export->filepath,
                    'created_at' => $export->created_at,
                    'generated_by' => $export->user ? $export->user->name : 'Unknown',
                    'billing_period' => $export->billing_period,
                    'size_bytes' => \Storage::disk('public')->exists($export->filepath) ? \Storage::disk('public')->size($export->filepath) : 0
                ];
            }

            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
            $zip->close();

            // Create notification about backup creation (branch-specific type)
            \App\Models\Notification::create([
                'type' => 'branch_file_backup',
                'user_id' => \Auth::id(),
                'related_id' => \Auth::id(),
                'message' => "Branch file retention backup created: {$backupFilename} ({$totalFiles} files, " . round($totalSize / 1024 / 1024, 2) . " MB)",
                'billing_period' => \Auth::user()->billing_period
            ]);

            \Log::info("Branch file retention backup created: {$backupFilename} with {$totalFiles} files, " . round($totalSize / 1024 / 1024, 2) . " MB");

            return response()->json([
                'success' => true,
                'message' => "Backup created successfully: {$backupFilename}",
                'filename' => $backupFilename,
                'filepath' => $backupPath,
                'total_files' => $totalFiles,
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'download_url' => route('file.retention.download.backup.branch', ['filename' => $backupFilename])
            ]);

        } catch (\Exception $e) {
            \Log::error('Branch file retention backup error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create backup: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadBackup_branch($filename)
    {
        try {
            $backupPath = "backups/{$filename}";

            if (!\Storage::disk('public')->exists($backupPath)) {
                abort(404, 'Backup file not found');
            }

            return response()->download(storage_path('app/public/' . $backupPath), $filename);
        } catch (\Exception $e) {
            \Log::error('Branch backup download error: ' . $e->getMessage());
            abort(404, 'Backup file not found');
        }
    }
}
