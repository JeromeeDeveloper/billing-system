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
        'principal_due',
        'interest_due',
        'penalty_due',
        'billing_period',
        'start_hold',
        'expiry_date',
        'account_status',
        'approval_no',
        'total_due_after_remittance',
        'loan_payment',
        'remarks'
    ];

    protected $casts = [
        'open_date' => 'date:Y-m-d',
        'maturity_date' => 'date:Y-m-d',
        'amortization_due_date' => 'date:Y-m-d',
        'amount_due' => 'decimal:2',
        'total_due' => 'decimal:2',
        'principal_due' => 'decimal:2',
        'interest_due' => 'decimal:2',
        'penalty_due' => 'decimal:2',
        'loan_payment' => 'decimal:2'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}

