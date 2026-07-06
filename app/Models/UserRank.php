<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRank extends Model
{
    protected $fillable = [
        'mlm_user_id', 'rank_id', 'current_cc_at_time',
        'is_current', 'achieved_at'
    ];

    protected $casts = [
        'current_cc_at_time' => 'decimal:2',
        'is_current' => 'boolean',
        'achieved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(MlmUser::class, 'mlm_user_id');
    }

    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }
}
