<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentUpload extends Model
{
    protected $fillable = [
        'branches_id',
        'member_id',
        'collection_id',
        'bill_id',
        'remittance_id',
        'loan_forecast_id',
        'atm_module_id',
        'document_type',
        'filename',
        'filepath',
        'mime_type',
        'uploaded_by',
        'upload_date',
        'billing_period',
    ];

    protected $casts = [
        'upload_date' => 'datetime',
    ];

    /**
     * Get the user that uploaded the document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

