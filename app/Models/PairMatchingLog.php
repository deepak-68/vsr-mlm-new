<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PairMatchingLog extends Model
{
    protected $fillable = [
        'user_id', 'order_id', 'from_user_id',
        'pairs_count', 'left_cc_used', 'right_cc_used', 'income_amount',
        'left_cc_before', 'left_cc_after',
        'right_cc_before', 'right_cc_after',
    ];

    protected $casts = [
        'pairs_count' => 'integer',
        'left_cc_used' => 'decimal:2',
        'right_cc_used' => 'decimal:2',
        'income_amount' => 'decimal:2',
        'left_cc_before' => 'decimal:2',
        'left_cc_after' => 'decimal:2',
        'right_cc_before' => 'decimal:2',
        'right_cc_after' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(MlmUser::class, 'user_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(MlmUser::class, 'from_user_id');
    }
}
