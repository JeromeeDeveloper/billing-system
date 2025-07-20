<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContraAcc extends Model
{
    use HasFactory;

    protected $table = 'contra_acc';

    protected $fillable = [
        'type',
        'account_number',
        'loan_acc_no',
        'savings_id',
        'shares_id',
        'loan_forecast_id'
    ];

    protected $casts = [
        'type' => 'string'
    ];

    /**
     * Get the savings account associated with this contra account.
     */
    public function savings()
    {
        return $this->belongsTo(Savings::class);
    }

    /**
     * Get the shares account associated with this contra account.
     */
    public function shares()
    {
        return $this->belongsTo(Shares::class);
    }

    /**
     * Get the loan forecast associated with this contra account.
     */
    public function loanForecast()
    {
        return $this->belongsTo(LoanForecast::class);
    }

    /**
     * Get the contra account number based on type
     */
    public function getContraAccountNumberAttribute()
    {
        if ($this->type === 'loans') {
            return $this->loan_acc_no;
        }
        return $this->account_number;
    }
}
