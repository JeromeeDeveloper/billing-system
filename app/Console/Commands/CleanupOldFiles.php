<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FileRetentionService;

class CleanupOldFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:cleanup
                            {--type= : Specific document type to clean up (Installment File, Savings, Shares, CIF, Loan, CoreID, Savings & Shares Product, Billing Exports)}
                            {--billing-exports-only : Clean up only billing export files}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old files based on retention policy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fileRetentionService = new FileRetentionService();
        $type = $this->option('type');
        $billingExportsOnly = $this->option('billing-exports-only');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No files will be deleted');
        }

        if ($billingExportsOnly) {
            $this->cleanupBillingExports($fileRetentionService, $dryRun);
            return;
        }

        if ($type) {
            $this->cleanupSpecificType($fileRetentionService, $type, $dryRun);
        } else {
            $this->cleanupAllTypes($fileRetentionService, $dryRun);
        }
    }

    private function cleanupBillingExports(FileRetentionService $service, bool $dryRun)
    {
        $this->info('🧹 Cleaning up billing export files...');

        if ($dryRun) {
            $stats = $service->getStorageStats();
            $billingStats = $stats['Billing Exports'] ?? null;

            if ($billingStats && $billingStats['files_over_limit'] > 0) {
                $this->warn("Would delete {$billingStats['files_over_limit']} billing export files (keeping newest " . $service->getMaxBillingExports() . ")");
                $this->info("Total billing exports: {$billingStats['count']}");
                $this->info("Total size: {$billingStats['total_size_mb']} MB");
            } else {
                $this->info('No billing export files to clean up');
            }
        } else {
            $deletedCount = $service->cleanupOldBillingExports();
            if ($deletedCount > 0) {
                $this->info("✅ Deleted {$deletedCount} old billing export files");
            } else {
                $this->info('✅ No billing export files to clean up');
            }
        }
    }

    private function cleanupSpecificType(FileRetentionService $service, string $type, bool $dryRun)
    {
        $validTypes = [
            'Installment File',
            'Savings',
            'Shares',
            'CIF',
            'Loan',
            'CoreID',
            'Savings & Shares Product',
            'Billing Exports'
        ];

        if (!in_array($type, $validTypes)) {
            $this->error("❌ Invalid document type: {$type}");
            $this->info("Valid types: " . implode(', ', $validTypes));
            return;
        }

        $this->info("🧹 Cleaning up {$type} files...");

        if ($type === 'Billing Exports') {
            $this->cleanupBillingExports($service, $dryRun);
            return;
        }

        if ($dryRun) {
            $stats = $service->getStorageStats();
            $typeStats = $stats[$type] ?? null;

            if ($typeStats && $typeStats['files_over_limit'] > 0) {
                $this->warn("Would delete {$typeStats['files_over_limit']} {$type} files (keeping newest " . $service->getMaxFilesPerType() . ")");
                $this->info("Total {$type} files: {$typeStats['count']}");
                $this->info("Total size: {$typeStats['total_size_mb']} MB");
            } else {
                $this->info("No {$type} files to clean up");
            }
        } else {
            $deletedCount = $service->cleanupOldFiles($type);
            if ($deletedCount > 0) {
                $this->info("✅ Deleted {$deletedCount} old {$type} files");
            } else {
                $this->info("✅ No {$type} files to clean up");
            }
        }
    }

    private function cleanupAllTypes(FileRetentionService $service, bool $dryRun)
    {
        $this->info('🧹 Cleaning up all file types...');

        if ($dryRun) {
            $stats = $service->getStorageStats();
            $totalFiles = 0;
            $totalSize = 0;
            $filesToDelete = 0;

            foreach ($stats as $type => $typeStats) {
                $totalFiles += $typeStats['count'];
                $totalSize += $typeStats['total_size_bytes'];
                $filesToDelete += $typeStats['files_over_limit'];

                if ($typeStats['files_over_limit'] > 0) {
                    $this->warn("  {$type}: {$typeStats['files_over_limit']} files would be deleted");
                }
            }

            $this->info("Total files: {$totalFiles}");
            $this->info("Total size: " . round($totalSize / 1024 / 1024, 2) . " MB");
            $this->warn("Total files that would be deleted: {$filesToDelete}");
        } else {
            $stats = $service->cleanupAllOldFiles();

            if ($stats['total_deleted'] > 0) {
                $this->info("✅ Deleted {$stats['total_deleted']} old files total");
                $this->info("Freed space: " . round($stats['total_freed_space'] / 1024 / 1024, 2) . " MB");

                foreach ($stats['by_type'] as $type => $typeStats) {
                    if ($typeStats['deleted'] > 0) {
                        $this->info("  {$type}: {$typeStats['deleted']} files deleted");
                    }
                }
            } else {
                $this->info('✅ No files to clean up');
            }
        }
    }
}
