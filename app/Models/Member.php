<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'cid',
        'emp_id',
        'fname',
        'lname',
        'address',
        'savings_balance',
        'share_balance',
        'loan_balance',
        'birth_date',
        'date_registered',
        'gender',
        'customer_type',
        'customer_classification',
        'occupation',
        'industry',
        'area_officer',
        'area',
        'account_status',
        'status',
        'additional_address',
        'billing_period',
        'expiry_date',
    ];

    protected $casts = [
        'savings_balance' => 'decimal:2',
        'share_balance' => 'decimal:2',
        'loan_balance' => 'decimal:2',
        'birth_date' => 'date',
        'date_registered' => 'date',
        'expiry_date' => 'date',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function loanForecasts()
    {
        return $this->hasMany(LoanForecast::class);
    }

    public function loanProducts()
    {
        return $this->hasMany(LoanProduct::class);
    }

     public function savings()
    {
        return $this->hasMany(Saving::class);
    }

   public function loanForecastsData(): Attribute
{
    return Attribute::make(
        get: fn () => $this->loanForecasts->map(function ($loan) {
            return [
                'loan_acct_no' => $loan->loan_acct_no,
                'amount_due' => $loan->amount_due,
                'open_date' => $loan->open_date ? Carbon::parse($loan->open_date)->format('Y-m-d') : null,
                'maturity_date' => $loan->maturity_date ? Carbon::parse($loan->maturity_date)->format('Y-m-d') : null,
                'amortization_due_date' => $loan->amortization_due_date ? Carbon::parse($loan->amortization_due_date)->format('Y-m-d') : null,
                'total_due' => $loan->total_due,
                'principal_due' => $loan->principal_due,
                'interest_due' => $loan->interest_due,
                'penalty_due' => $loan->penalty_due,
                'billing_period' => $loan->billing_period,
            ];
        })->toArray()
    );
}
}
