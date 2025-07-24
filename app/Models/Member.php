<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Shares;
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
        'principal',
        'regular_principal',
        'special_principal',
        'birth_date',
        'start_date',
        'end_date',
        'date_registered',
        'gender',
        'customer_type',
        'customer_classification',
        'occupation',
        'industry',
        'area_officer',
        'area',
        'account_name',
        'status',
        'approval_no',
        'start_hold',
        'expiry_date',
        'account_status',
        'billing_period',
        'member_tagging'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'date_registered' => 'date',
        'savings_balance' => 'decimal:2',
        'share_balance' => 'decimal:2',
        'loan_balance' => 'decimal:2',
        'principal' => 'decimal:2',
        'regular_principal' => 'decimal:2',
        'special_principal' => 'decimal:2'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function savings()
    {
        return $this->hasMany(Savings::class);
    }

    public function loanForecasts()
    {
        return $this->hasMany(LoanForecast::class);
    }

    public function loanPayments()
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function atmPayments()
    {
        return $this->hasMany(AtmPayment::class);
    }

    public function loanProductMembers()
    {
        return $this->hasMany(LoanProductMember::class);
    }

    public function remittances()
    {
        return $this->hasMany(Remittance::class);
    }

    public function loanProducts()
    {
        return $this->belongsToMany(LoanProduct::class, 'loan_product_member', 'member_id', 'loan_product_id')->withTimestamps();
    }

    public function shares()
    {
        return $this->hasMany(Shares::class);
    }

    public function savingProducts()
    {
        return $this->belongsToMany(SavingProduct::class, 'member_saving_product')
                    ->withTimestamps();
    }

    public function shareProducts()
    {
        return $this->belongsToMany(ShareProduct::class, 'member_share_product')
                    ->withTimestamps();
    }

    public function loanForecastsData(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->loanForecasts->map(function ($loan) {
                return [
                    'loan_acct_no' => $loan->loan_acct_no,
                    'amount_due' => $loan->amount_due,
                    'open_date' => $loan->open_date ? Carbon::parse($loan->open_date)->format('Y-m-d') : null,
                    'maturity_date' => $loan->maturity_date ? Carbon::parse($loan->maturity_date)->format('Y-m-d') : null,
                    'amortization_due_date' => $loan->amortization_due_date ? Carbon::parse($loan->amortization_due_date)->format('Y-m-d') : null,
                    'total_due' => $loan->total_due,
                    'original_total_due' => $loan->original_total_due,
                    'principal_due' => $loan->principal_due,
                    'interest_due' => $loan->interest_due,
                    'original_principal_due' => $loan->original_principal_due,
                    'original_interest_due' => $loan->original_interest_due,
                    'penalty_due' => $loan->penalty_due,
                    'billing_period' => $loan->billing_period,
                    'start_hold' => $loan->start_hold,
                    'expiry_date' => $loan->expiry_date,
                    'account_status' => $loan->account_status,
                    'approval_no' => $loan->approval_no,
                    'remarks' => $loan->remarks,
                    'id' => $loan->id
                ];
            })->toArray()
        );
    }

    public function savingsBalance(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->savings->sum('current_balance')
        );
    }

    public function shareBalance(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->shares->sum('current_balance')
        );
    }

    // Helper method to get full name
    public function getFullNameAttribute()
    {
        return "{$this->fname} {$this->lname}";
    }
}
