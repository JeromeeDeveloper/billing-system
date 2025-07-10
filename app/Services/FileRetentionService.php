<?php

namespace App\Services;

use App\Models\DocumentUpload;
use App\Models\BillingExport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileRetentionService
{
    /**
     * Maximum number of files to keep per document type
     */
    private const MAX_FILES_PER_TYPE = 12;

    /**
     * Maximum number of billing export files to keep
     */
    private const MAX_BILLING_EXPORTS = 12;

    /**
     * Clean up old files for a specific document type
     *
     * @param string $documentType
     * @param string|null $billingPeriod
     * @return int Number of files deleted
     */
    public function cleanupOldFiles(string $documentType, ?string $billingPeriod = null): int
    {
        $query = DocumentUpload::where('document_type', $documentType);

        if ($billingPeriod) {
            $query->where('billing_period', $billingPeriod);
        }

        $files = $query->orderBy('upload_date', 'desc')->get();

        // If we have more than the maximum allowed files, delete the oldest ones
        if ($files->count() > self::MAX_FILES_PER_TYPE) {
            $filesToDelete = $files->slice(self::MAX_FILES_PER_TYPE);
            $deletedCount = 0;
            $freedSpace = 0;

            foreach ($filesToDelete as $file) {
                // Delete physical file from storage
                if (Storage::disk('public')->exists($file->filepath)) {
                    $freedSpace += Storage::disk('public')->size($file->filepath);
                    Storage::disk('public')->delete($file->filepath);
                }

                // Delete database record
                $file->delete();
                $deletedCount++;
            }

            Log::info("File retention: Deleted {$deletedCount} old files for document type '{$documentType}', freed " . $this->formatBytes($freedSpace));

            return $deletedCount;
        }

        return 0;
    }

    /**
     * Clean up old billing export files
     *
     * @return int Number of files deleted
     */
    public function cleanupOldBillingExports(): int
    {
        $exports = BillingExport::orderBy('created_at', 'desc')->get();

        // If we have more than the maximum allowed files, delete the oldest ones
        if ($exports->count() > self::MAX_BILLING_EXPORTS) {
            $exportsToDelete = $exports->slice(self::MAX_BILLING_EXPORTS);
            $deletedCount = 0;
            $freedSpace = 0;

            foreach ($exportsToDelete as $export) {
                // Delete physical file from storage
                if (Storage::disk('public')->exists($export->filepath)) {
                    $freedSpace += Storage::disk('public')->size($export->filepath);
                    Storage::disk('public')->delete($export->filepath);
                }

                // Delete database record
                $export->delete();
                $deletedCount++;
            }

            Log::info("File retention: Deleted {$deletedCount} old billing export files, freed " . $this->formatBytes($freedSpace));

            return $deletedCount;
        }

        return 0;
    }

    /**
     * Clean up all old files across all document types
     *
     * @return array Statistics about the cleanup
     */
    public function cleanupAllOldFiles(): array
    {
        $documentTypes = [
            'Installment File',
            'Savings',
            'Shares',
            'CIF',
            'Loan',
            'CoreID',
            'Savings & Shares Product'
        ];

        $stats = [
            'total_deleted' => 0,
            'total_freed_space' => 0,
            'by_type' => []
        ];

        foreach ($documentTypes as $type) {
            $query = DocumentUpload::where('document_type', $type);
            $files = $query->orderBy('upload_date', 'desc')->get();

            if ($files->count() > self::MAX_FILES_PER_TYPE) {
                $filesToDelete = $files->slice(self::MAX_FILES_PER_TYPE);
                $deletedCount = 0;
                $freedSpace = 0;

                foreach ($filesToDelete as $file) {
                    if (Storage::disk('public')->exists($file->filepath)) {
                        $freedSpace += Storage::disk('public')->size($file->filepath);
                        Storage::disk('public')->delete($file->filepath);
                    }
                    $file->delete();
                    $deletedCount++;
                }

                $stats['total_deleted'] += $deletedCount;
                $stats['total_freed_space'] += $freedSpace;
                $stats['by_type'][$type] = [
                    'deleted' => $deletedCount,
                    'freed_space' => $freedSpace
                ];

                Log::info("File retention: Deleted {$deletedCount} old files for document type '{$type}', freed " . $this->formatBytes($freedSpace));
            }
        }

        // Clean up billing exports
        $billingExportsDeleted = $this->cleanupOldBillingExports();
        if ($billingExportsDeleted > 0) {
            $stats['total_deleted'] += $billingExportsDeleted;
            $stats['by_type']['Billing Exports'] = [
                'deleted' => $billingExportsDeleted,
                'freed_space' => 0 // Will be calculated in cleanupOldBillingExports
            ];
        }

        return $stats;
    }

    /**
     * Get storage statistics for monitoring
     *
     * @return array
     */
    public function getStorageStats(): array
    {
        $stats = [];
        $documentTypes = [
            'Installment File',
            'Savings',
            'Shares',
            'CIF',
            'Loan',
            'CoreID',
            'Savings & Shares Product'
        ];

        foreach ($documentTypes as $type) {
            $files = DocumentUpload::where('document_type', $type)->get();
            $totalSize = 0;
            $fileCount = $files->count();

            foreach ($files as $file) {
                if (Storage::disk('public')->exists($file->filepath)) {
                    $totalSize += Storage::disk('public')->size($file->filepath);
                }
            }

            $stats[$type] = [
                'count' => $fileCount,
                'total_size_bytes' => $totalSize,
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'oldest_file' => $files->min('upload_date'),
                'newest_file' => $files->max('upload_date'),
                'at_limit' => $fileCount >= self::MAX_FILES_PER_TYPE,
                'files_over_limit' => max(0, $fileCount - self::MAX_FILES_PER_TYPE)
            ];
        }

        // Add billing exports statistics
        $billingExports = BillingExport::all();
        $billingExportsSize = 0;
        $billingExportsCount = $billingExports->count();

        foreach ($billingExports as $export) {
            if (Storage::disk('public')->exists($export->filepath)) {
                $billingExportsSize += Storage::disk('public')->size($export->filepath);
            }
        }

        $stats['Billing Exports'] = [
            'count' => $billingExportsCount,
            'total_size_bytes' => $billingExportsSize,
            'total_size_mb' => round($billingExportsSize / 1024 / 1024, 2),
            'oldest_file' => $billingExports->min('created_at'),
            'newest_file' => $billingExports->max('created_at'),
            'at_limit' => $billingExportsCount >= self::MAX_BILLING_EXPORTS,
            'files_over_limit' => max(0, $billingExportsCount - self::MAX_BILLING_EXPORTS)
        ];

        return $stats;
    }

    /**
     * Get the maximum number of files allowed per type
     *
     * @return int
     */
    public function getMaxFilesPerType(): int
    {
        return self::MAX_FILES_PER_TYPE;
    }

    /**
     * Get the maximum number of billing exports allowed
     *
     * @return int
     */
    public function getMaxBillingExports(): int
    {
        return self::MAX_BILLING_EXPORTS;
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
