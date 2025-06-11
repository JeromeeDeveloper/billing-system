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
        'status',
        'message',
        'type'
    ];

    protected $casts = [
        'savings' => 'array',
        'loans' => 'decimal:2'
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
