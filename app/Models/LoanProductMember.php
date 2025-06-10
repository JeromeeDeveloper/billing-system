<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanProductMember extends Model
{
    use HasFactory;

    protected $table = 'loan_product_member';

    protected $fillable = [
        'member_id',
        'loan_product_id',
        'status'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function loanProduct()
    {
        return $this->belongsTo(LoanProduct::class);
    }
}
