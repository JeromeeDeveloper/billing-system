<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_period',
        'export_type',
        'user_id',
        'branch_id',
        'last_export_at',
        'last_upload_at',
        'is_enabled',
        'is_admin_export'
    ];

    protected $casts = [
        'last_export_at' => 'datetime',
        'last_upload_at' => 'datetime',
        'is_enabled' => 'boolean',
        'is_admin_export' => 'boolean'
    ];

    /**
     * Mark export as generated
     */
    public static function markExported($billingPeriod, $exportType, $userId = null, $branchId = null, $isAdminExport = false)
    {
        return static::updateOrCreate(
            [
                'billing_period' => $billingPeriod,
                'export_type' => $exportType,
                'user_id' => $userId
            ],
            [
                'branch_id' => $branchId,
                'last_export_at' => now(),
                'is_enabled' => false,
                'is_admin_export' => $isAdminExport
            ]
        );
    }

    /**
     * Mark new upload for this export type
     */
    public static function markUploaded($billingPeriod, $exportType, $userId = null)
    {
        return static::updateOrCreate(
            [
                'billing_period' => $billingPeriod,
                'export_type' => $exportType,
                'user_id' => $userId
            ],
            [
                'last_upload_at' => now(),
                'is_enabled' => true
            ]
        );
    }

    /**
     * Check if export is enabled for this type
     */
    public static function isEnabled($billingPeriod, $exportType, $userId = null)
    {
        $status = static::where('billing_period', $billingPeriod)
            ->where('export_type', $exportType)
            ->where('user_id', $userId)
            ->first();

        if (!$status) {
            return true; // Default to enabled if no record exists
        }

        return $status->is_enabled;
    }

    /**
     * Check if export is enabled for branch users based on recent admin uploads
     * This method checks if there's a recent admin upload that should enable branch exports
     */
    public static function isEnabledForBranch($billingPeriod, $exportType, $currentUserId = null)
    {
        // First check if the current user has a specific export status
        if ($currentUserId) {
            $userStatus = static::where('billing_period', $billingPeriod)
                ->where('export_type', $exportType)
                ->where('user_id', $currentUserId)
                ->first();

            if ($userStatus) {
                return $userStatus->is_enabled;
            }
        }

        // If no user-specific status, check if there's a recent admin upload
        // Look for any admin user who has uploaded this export type recently
        $adminStatus = static::where('billing_period', $billingPeriod)
            ->where('export_type', $exportType)
            ->whereHas('user', function($query) {
                $query->where('role', 'admin');
            })
            ->where('is_enabled', true)
            ->orderBy('last_upload_at', 'desc')
            ->first();

        if ($adminStatus) {
            // Check if the admin upload is recent (within the last 30 days)
            $daysSinceUpload = now()->diffInDays($adminStatus->last_upload_at);
            return $daysSinceUpload <= 30;
        }

        // Default to enabled if no admin upload found
        return true;
    }

    /**
     * Get export status for all types in a billing period
     */
    public static function getStatuses($billingPeriod, $userId = null)
    {
        return static::where('billing_period', $billingPeriod)
            ->where('user_id', $userId)
            ->get()
            ->keyBy('export_type');
    }

    /**
     * Get export status for all types in a billing period with branch logic
     */
    public static function getStatusesForBranch($billingPeriod, $userId = null)
    {
        $userStatuses = static::where('billing_period', $billingPeriod)
            ->where('user_id', $userId)
            ->get()
            ->keyBy('export_type');

        // For each export type, check if it should be enabled for branch users
        $exportTypes = ['loans_savings', 'loans_savings_with_product', 'shares', 'shares_with_product'];

        foreach ($exportTypes as $exportType) {
            if (!$userStatuses->has($exportType)) {
                // Create a virtual status object for branch users
                $isEnabled = static::isEnabledForBranch($billingPeriod, $exportType, $userId);
                $userStatuses->put($exportType, (object) [
                    'billing_period' => $billingPeriod,
                    'export_type' => $exportType,
                    'user_id' => $userId,
                    'is_enabled' => $isEnabled,
                    'last_export_at' => null,
                    'last_upload_at' => null
                ]);
            }
        }

        return $userStatuses;
    }

    /**
     * Check if edit buttons should be disabled for a specific branch
     */
    public static function isEditDisabledForBranch($billingPeriod, $branchId)
    {
        // Check if there's an admin export (disables all branches)
        $adminExport = static::where('billing_period', $billingPeriod)
            ->where('is_admin_export', true)
            ->where('is_enabled', false)
            ->exists();

        if ($adminExport) {
            return true; // Admin export disables all branches
        }

        // Check if this specific branch has been exported
        $branchExport = static::where('billing_period', $billingPeriod)
            ->where('branch_id', $branchId)
            ->where('is_enabled', false)
            ->exists();

        return $branchExport;
    }

    /**
     * Check if edit buttons should be disabled for all branches (admin export)
     */
    public static function isEditDisabledForAll($billingPeriod)
    {
        return static::where('billing_period', $billingPeriod)
            ->where('is_admin_export', true)
            ->where('is_enabled', false)
            ->exists();
    }

    /**
     * Re-enable all edit buttons for a billing period (called when closing billing period)
     */
    public static function reEnableAllEdits($billingPeriod)
    {
        return static::where('billing_period', $billingPeriod)
            ->update(['is_enabled' => true]);
    }

    /**
     * Relationship to User model
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Relationship to Branch model
     */
    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }
}
