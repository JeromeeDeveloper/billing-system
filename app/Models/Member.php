<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
    ];

    protected $casts = [
        'savings_balance' => 'decimal:2',
        'share_balance' => 'decimal:2',
        'loan_balance' => 'decimal:2',
        'birth_date' => 'date',
        'date_registered' => 'date',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function loanForecasts()
    {
        return $this->hasMany(LoanForecast::class);
    }
}
