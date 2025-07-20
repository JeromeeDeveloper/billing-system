<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsPayment extends Model
{
    use HasFactory;

    protected $table = 'savings_payments';

    protected $fillable = [
        'member_id',
        'savings_id',
        'atm_payment_id',
        'account_number',
        'amount',
        'payment_date',
        'reference_number',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function savings()
    {
        return $this->belongsTo(Savings::class);
    }

    public function atmPayment()
    {
        return $this->belongsTo(AtmPayment::class);
    }
}
