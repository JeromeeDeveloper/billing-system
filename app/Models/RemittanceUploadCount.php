<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RemittanceUploadCount extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_period',
        'upload_type',
        'count'
    ];

    /**
     * Increment the count for a specific billing period and upload type
     */
    public static function incrementCount($billingPeriod, $uploadType)
    {
        return static::updateOrCreate(
            [
                'billing_period' => $billingPeriod,
                'upload_type' => $uploadType
            ],
            [
                'count' => DB::raw('count + 1')
            ]
        );
    }

    /**
     * Get the count for a specific billing period and upload type
     */
    public static function getCount($billingPeriod, $uploadType)
    {
        $record = static::where('billing_period', $billingPeriod)
            ->where('upload_type', $uploadType)
            ->first();

        return $record ? $record->count : 0;
    }
}
