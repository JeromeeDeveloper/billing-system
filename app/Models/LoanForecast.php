<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class LoanForecast extends Model
{
    use HasFactory;

    protected $table = 'loan_forecast';

    protected $fillable = [
        'member_id',
        'loan_acct_no',
        'amount_due',
        'open_date',
        'maturity_date',
        'amortization_due_date',
        'total_due',
        'original_total_due',
        'principal_due',
        'interest_due',
        'original_principal_due',
        'original_interest_due',
        'billing_period',
        'start_hold',
        'expiry_date',
        'account_status',
        'approval_no',
        'total_due_after_remittance',
        'loan_payment',
        'remarks',
        'principal',
        'interest',
        'total_amort',
        'status',
        'interest_due_status',
        'principal_due_status',
        'total_due_status',
    ];

    protected $casts = [
        'open_date' => 'date:Y-m-d',
        'maturity_date' => 'date:Y-m-d',
        'amortization_due_date' => 'date:Y-m-d',
        'amount_due' => 'decimal:2',
        'total_due' => 'decimal:2',
        'original_total_due' => 'decimal:2',
        'principal_due' => 'decimal:2',
        'interest_due' => 'decimal:2',
        'original_principal_due' => 'decimal:2',
        'original_interest_due' => 'decimal:2',
        'loan_payment' => 'decimal:2',
        'principal' => 'decimal:2',
        'interest' => 'decimal:2',
        'total_amort' => 'decimal:2'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function loanProduct()
    {
        // Extract product_code from loan_acct_no (3rd segment)
        $productCode = null;
        if ($this->loan_acct_no) {
            $segments = explode('-', $this->loan_acct_no);
            $productCode = $segments[2] ?? null;
        }
        return $this->hasOne(LoanProduct::class, 'product_code', 'product_code')->where('product_code', $productCode);
    }

    public static function boot()
    {
        parent::boot();

                static::updating(function ($model) {
            // Auto-calculate total_due when principal_due or interest_due changes
            if ($model->isDirty('principal_due') || $model->isDirty('interest_due')) {
                $model->total_due = $model->principal_due + $model->interest_due;
            }

            // Protection for paid status
            if ($model->isDirty('principal_due') && $model->getOriginal('principal_due_status') === 'paid') {
                $model->principal_due = $model->getOriginal('principal_due');
            }
            if ($model->isDirty('interest_due') && $model->getOriginal('interest_due_status') === 'paid') {
                $model->interest_due = $model->getOriginal('interest_due');
            }
            if ($model->isDirty('total_due') && $model->getOriginal('total_due_status') === 'paid') {
                $model->total_due = $model->getOriginal('total_due');
            }
        });

        static::saving(function ($model) {
            // Auto-calculate total_due for new records or when principal_due/interest_due are set
            if ($model->isDirty('principal_due') || $model->isDirty('interest_due')) {
                $model->total_due = $model->principal_due + $model->interest_due;
            }
        });
    }
}

