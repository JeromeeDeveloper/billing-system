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

        return response()->json([
            'success' => true,
            'data' => [
                'document_type' => $documentType,
                'total_files' => $typeStats['count'],
                'files_over_limit' => $typeStats['files_over_limit'],
                'max_files_allowed' => $maxFiles,
                'total_size_mb' => $typeStats['total_size_mb'],
                'oldest_file' => $typeStats['oldest_file'],
                'newest_file' => $typeStats['newest_file']
            ]
        ]);
    }
}
