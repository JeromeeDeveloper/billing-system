<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class LoanForecast extends Model
{
    use HasFactory;

    protected $table = 'loan_forecast';

    protected $fillable = [
        'member_id',
        'loan_acct_no',
        'amount_due',
        'open_date',
        'maturity_date',
        'amortization_due_date',
        'total_due',
        'original_total_due',
        'principal_due',
        'interest_due',
        'original_principal_due',
        'original_interest_due',
        'billing_period',
        'start_hold',
        'expiry_date',
        'account_status',
        'approval_no',
        'total_due_after_remittance',
        'loan_payment',
        'remarks',
        'principal',
        'interest',
        'total_amort'
    ];

    protected $casts = [
        'open_date' => 'date:Y-m-d',
        'maturity_date' => 'date:Y-m-d',
        'amortization_due_date' => 'date:Y-m-d',
        'amount_due' => 'decimal:2',
        'total_due' => 'decimal:2',
        'original_total_due' => 'decimal:2',
        'principal_due' => 'decimal:2',
        'interest_due' => 'decimal:2',
        'original_principal_due' => 'decimal:2',
        'original_interest_due' => 'decimal:2',
        'loan_payment' => 'decimal:2',
        'principal' => 'decimal:2',
        'interest' => 'decimal:2',
        'total_amort' => 'decimal:2'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}

