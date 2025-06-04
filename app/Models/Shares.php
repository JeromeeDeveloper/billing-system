<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        'product_name',
        'approval_no',
        'start_hold',
        'expiry_date',
        'account_status'
    ];

    protected $casts = [
        'open_date' => 'date:Y-m-d',
        'start_hold' => 'date:Y-m-d',
        'expiry_date' => 'date:Y-m-d',
        'current_balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'interest' => 'decimal:2'
    ];

    public function setStartHoldAttribute($value)
    {
        $this->attributes['start_hold'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function setExpiryDateAttribute($value)
    {
        $this->attributes['expiry_date'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    /**
     * Relationship to the Member model.
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
