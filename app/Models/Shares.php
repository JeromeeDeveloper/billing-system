<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shares extends Model
{
    use HasFactory;

    protected $table = 'shares';

    protected $fillable = [
        'member_id',
        'account_number',
        'product_code',
        'open_date',
        'current_balance',
        'available_balance',
        'interest',
    ];

    protected $casts = [
        'open_date' => 'date',
        'current_balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'interest' => 'decimal:2',
    ];

    /**
     * Relationship to the Member model.
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
