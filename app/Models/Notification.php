<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'type',
        'user_id',
        'related_id',
        'message',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRelatedData()
    {
        if ($this->type === 'document_upload') {
            return DocumentUpload::find($this->related_id);
        } elseif ($this->type === 'billing_report') {
            return BillingExport::find($this->related_id);
        }
        return null;
    }
}
