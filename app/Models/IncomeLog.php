<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomeLog extends Model
{
    protected $fillable = [
        'user_id', 'from_user_id', 'income_type',
        'cc_amount', 'currency_amount', 'balance_after',
        'reference_type', 'reference_id', 'order_number',
        'remarks',
    ];

    protected $casts = [
        'cc_amount' => 'decimal:2',
        'currency_amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(MlmUser::class, 'user_id');
    }

    public function fromUser()
    {
        return $this->belongsTo(MlmUser::class, 'from_user_id');
    }
}
