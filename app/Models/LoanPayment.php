<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'loan_forecast_id',
        'withdrawal_amount',
        'amount',
        'payment_date',
        'reference_number',
        'notes'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function loanForecast()
    {
        return $this->belongsTo(LoanForecast::class);
    }
}
