<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Shares extends Model
{
    use HasFactory;

    protected $table = 'shares';

    protected $fillable = [
        'member_id',
        'account_number',
        'product_code',
        'open_date',
        'current_balance',
        'available_balance',
        'interest',
        'product_name',
        'approval_no',
        'start_hold',
        'expiry_date',
        'account_status',
        'deduction_amount',
        'remarks'
    ];

    /**
     * Relationship to the Member model.
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Relationship to the ShareProduct model.
     */
    public function shareProduct()
    {
        return $this->belongsTo(ShareProduct::class, 'product_code', 'product_code');
    }
}
