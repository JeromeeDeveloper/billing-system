<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanForecast extends Model
{
    use HasFactory;

    protected $table = 'loan_forecast'; // Explicit since table is not plural

    protected $fillable = [
        'loan_acct_no', 'amount_due', 'open_date', 'maturity_date',
        'amortization_due_date', 'total_due', 'principal_due',
        'interest_due', 'penalty_due', 'member_id', 'billing_period',
        'start_hold', 'expiry_date', 'account_status', 'approval_no'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}

