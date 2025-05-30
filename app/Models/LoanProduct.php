<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanProduct extends Model
{
    use HasFactory;

    // Table name (optional if it follows Laravel's naming convention)
    protected $table = 'loans_product';

    // Mass assignable attributes
    protected $fillable = [
        'member_id',
        'product',
        'product_code',
        'prioritization',

    ];

    /**
     * Relationship: A loan product belongs to a member.
     */
public function members()
{
    return $this->belongsToMany(Member::class, 'loan_product_member', 'loan_product_id', 'member_id')->withTimestamps();
}


}
