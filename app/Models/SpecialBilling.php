<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialBilling extends Model
{
    use HasFactory;

    protected $table = 'special_billings';

    protected $fillable = [
        'cid',
        'loan_acct_no',
        'employee_id',
        'name',
        'amortization',
        'start_date',
        'end_date',
        'gross',
        'office',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'cid', 'cid');
    }
}
