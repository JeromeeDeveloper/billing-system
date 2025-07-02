<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Savings extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'account_number',
        'product_code',
        'product_name',
        'open_date',
        'current_balance',
        'available_balance',
        'interest',
        'approval_no',
        'start_hold',
        'expiry_date',
        'amount_to_deduct',
        'priotization',
        'deduction_amount',
        'account_status',
        'remittance_amount',
        'remarks',
    ];

    protected $casts = [
        'open_date' => 'date:Y-m-d',
        'start_hold' => 'date:Y-m-d',
        'expiry_date' => 'date:Y-m-d',
        'current_balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'interest' => 'decimal:2',
        'amount_to_deduct' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'remittance_amount' => 'decimal:2'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function savingProduct()
    {
        return $this->belongsTo(SavingProduct::class, 'product_code', 'product_code');
    }

    public function setStartHoldAttribute($value)
    {
        $this->attributes['start_hold'] = $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function setExpiryDateAttribute($value)
    {
        $this->attributes['expiry_date'] = $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
    }
}
