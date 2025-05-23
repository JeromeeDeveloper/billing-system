<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'upload_date' => 'datetime',
    ];
}

