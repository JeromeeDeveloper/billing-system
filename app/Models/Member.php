<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id', 'cid', 'emp_id', 'fname', 'lname',
        'address', 'savings_balance', 'share_balance', 'loan_balance'
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

