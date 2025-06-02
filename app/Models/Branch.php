<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code'];

    /**
     * Get all members associated with this branch.
     */
    public function members()
    {
        return $this->hasMany(Member::class);
    }

    /**
     * Get all bills associated with this branch.
     */
    public function bills()
    {
        return $this->hasMany(Billing::class, 'branches_id');
    }

     public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
