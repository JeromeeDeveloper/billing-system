<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Remittance extends Model
{
    use HasFactory;

    protected $table = 'remittance';

    protected $fillable = [
        'member_id',
        'branch_id',
        'loan_payment',
        'savings_dep',
        'share_dep'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
