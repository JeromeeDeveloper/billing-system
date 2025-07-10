<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingExport extends Model
{
    protected $fillable = [
        'billing_period',
        'filename',
        'filepath',
        'generated_by'
    ];

    /**
     * Get the user that generated the export.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
