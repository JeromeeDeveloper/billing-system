<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Saving extends Model
{
    use HasFactory;

    protected $table = 'savings';

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
        'account_status',
        'deduction_amount',
        'remittance_amount'
    ];

    protected $casts = [
        'open_date' => 'date:Y-m-d',
        'current_balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'interest' => 'decimal:2',
        'start_hold' => 'date:Y-m-d',
        'expiry_date' => 'date:Y-m-d',
        'deduction_amount' => 'decimal:2'
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

    /**
     * Relationship to the SavingProduct model.
     */
    public function savingProduct()
    {
        return $this->belongsTo(SavingProduct::class, 'product_code', 'product_code');
    }
}
