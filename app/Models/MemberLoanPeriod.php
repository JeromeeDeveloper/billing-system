<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MemberLoanPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'billing_period',
        'principal',
        'start_date',
        'end_date',
        'loan_number',
        'product_code'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'principal' => 'float'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
