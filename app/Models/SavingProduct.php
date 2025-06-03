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
        return $this->hasMany(Saving::class, 'product_code', 'product_code');
    }

    /**
     * Relationship: A saving product can belong to many members.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'member_saving_product')
                    ->withTimestamps();
    }
}
