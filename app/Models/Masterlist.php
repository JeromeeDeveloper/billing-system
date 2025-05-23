<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterList extends Model
{
    protected $table = 'master_list'; // if you really need this name

    protected $fillable = [
        'member_id',
        'loan_forecast_id',
        'branches_id',
        'collection_id',
        'bill_id',
        'remittance_id',
        'atm_module_id',
        'status',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branches_id');
    }

    public function loanForecast()
    {
        return $this->belongsTo(LoanForecast::class);
    }

    public function bill()
    {
        return $this->belongsTo(Billing::class);
    }

    public function remittance()
    {
        return $this->belongsTo(Remittance::class);
    }

    public function atmModule()
    {
        return $this->belongsTo(Atm::class);
    }
}
