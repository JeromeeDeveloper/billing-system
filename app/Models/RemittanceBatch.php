<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemittanceBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_period',
        'remittance_tag',
        'imported_at',
        'billing_type',
    ];
}
