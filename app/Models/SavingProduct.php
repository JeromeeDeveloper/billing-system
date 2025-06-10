<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SavingProduct extends Model
{
    use HasFactory;

    protected $table = 'saving_products';

    protected $fillable = [
        'product_name',
        'product_code'
    ];

    protected $casts = [
        'interest' => 'decimal:2'
    ];

    /**
     * Get all savings accounts using this product.
     */
    public function savings()
    {
        return $this->hasMany(Savings::class, 'product_code', 'product_code');
    }

    /**
     * Relationship: A saving product can belong to many members.
     */
    public function members()
    {
        return $this->hasManyThrough(
            Member::class,
            Savings::class,
            'product_code', // Foreign key on savings table
            'id', // Foreign key on members table
            'product_code', // Local key on saving_products table
            'member_id' // Local key on savings table
        );
    }
}
