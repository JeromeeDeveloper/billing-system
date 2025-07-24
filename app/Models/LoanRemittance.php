<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRemittance extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_forecast_id',
        'member_id',
        'remitted_amount',
        'applied_to_interest',
        'applied_to_principal',
        'remaining_interest_due',
        'remaining_principal_due',
        'remaining_total_due',
        'remittance_date',
        'remittance_tag',
        'billing_period',
    ];

    public function member()
    {
        return $this->belongsTo(\App\Models\Member::class);
    }

    public function loanForecast()
    {
        return $this->belongsTo(\App\Models\LoanForecast::class, 'loan_forecast_id');
    }
}
