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

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function savingProduct()
    {
        return $this->belongsTo(SavingProduct::class, 'product_code', 'product_code');
    }
}
