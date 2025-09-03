<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemittanceReport extends Model
{
    use HasFactory;

    protected $table = 'remittance_reports';

    protected $fillable = [
        'cid',
        'member_name',
        'remitted_loans',
        'remitted_savings',
        'remitted_shares',
        'period',
        'remittance_tag',
        'remittance_type',
        'billed_amount',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'cid', 'cid');
    }
}
