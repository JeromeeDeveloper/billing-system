<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtmPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'user_id',
        'withdrawal_amount',
        'total_loan_payment',
        'savings_allocation',
        'savings_account_number',
        'payment_date',
        'reference_number',
        'notes'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'withdrawal_amount' => 'decimal:2',
        'total_loan_payment' => 'decimal:2',
        'savings_allocation' => 'decimal:2'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function loanPayments()
    {
        return $this->hasMany(LoanPayment::class, 'member_id', 'member_id')
            ->where('payment_date', $this->payment_date);
    }
}
