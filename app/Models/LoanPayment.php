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
        'applied_to_interest',
        'applied_to_principal',
        'penalty',
        'payment_date',
        'reference_number',
        'notes'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'applied_to_interest' => 'decimal:2',
        'applied_to_principal' => 'decimal:2',
        'penalty' => 'decimal:2'
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
