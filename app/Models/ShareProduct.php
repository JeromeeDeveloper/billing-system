<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ShareProduct extends Model
{
    use HasFactory;

    protected $table = 'share_products';

    protected $fillable = [
        'product_name',
        'product_code'
    ];

    protected $casts = [
        'interest' => 'decimal:2',
    ];

    /**
     * Get all share accounts using this product.
     */
    public function shares()
    {
        return $this->hasMany(Shares::class, 'product_code', 'product_code');
    }

    /**
     * Relationship: A share product can belong to many members.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'member_share_product')
                    ->withTimestamps();
    }
}
