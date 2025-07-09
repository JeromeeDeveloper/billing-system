<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemittancePreview extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'emp_id',
        'name',
        'member_id',
        'loans',
        'savings',
        'share_amount',
        'status',
        'message',
        'type',
        'billing_period',
        'remittance_type'
    ];

    protected $casts = [
        'savings' => 'array',
        'loans' => 'decimal:2',
        'share_amount' => 'decimal:2',
        'remittance_type' => 'string'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
