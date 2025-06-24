<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialBilling extends Model
{
    use HasFactory;

    protected $table = 'special_billings';

    protected $fillable = [
        'cid',
        'employee_id',
        'name',
        'amortization',
        'start_date',
        'end_date',
        'gross',
        'office',
    ];
}
