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
        'last_export_at',
        'last_upload_at',
        'is_enabled'
    ];

    protected $casts = [
        'last_export_at' => 'datetime',
        'last_upload_at' => 'datetime',
        'is_enabled' => 'boolean'
    ];

    /**
     * Mark export as generated
     */
    public static function markExported($billingPeriod, $exportType, $userId = null)
    {
        return static::updateOrCreate(
            [
                'billing_period' => $billingPeriod,
                'export_type' => $exportType,
                'user_id' => $userId
            ],
            [
                'last_export_at' => now(),
                'is_enabled' => false
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
     * Get export status for all types in a billing period
     */
    public static function getStatuses($billingPeriod, $userId = null)
    {
        return static::where('billing_period', $billingPeriod)
            ->where('user_id', $userId)
            ->get()
            ->keyBy('export_type');
    }
}
