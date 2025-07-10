<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Billing extends Model
{
    use HasFactory;

    protected $table = 'bill';

    protected $fillable = [
        'member_id',
        'loan_forecast_id',
        'branches_id',
        'amortization',
        'start',
        'end',
        'gross',
        'status',
    ];

    /**
     * Relationships
     */

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function loanForecast()
    {
        return $this->belongsTo(LoanForecast::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branches_id');
    }
}
