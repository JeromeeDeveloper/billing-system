<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterList extends Model
{
    protected $table = 'master_list';

    protected $fillable = [
        'member_id',
        'loan_forecast_id',
        'branches_id',
        'status',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branches_id');
    }

    public function LoanForeCast()
    {
        return $this->belongsTo(Branch::class, 'loan_forecast_id');
    }
}

