<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserReward extends Model
{
    protected $fillable = [
        'mlm_user_id', 'reward_id', 'rank_id',
        'achieved_at', 'claimed_at', 'status', 'notes'
    ];

    protected $casts = [
        'achieved_at' => 'datetime',
        'claimed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(MlmUser::class, 'mlm_user_id');
    }

    public function reward()
    {
        return $this->belongsTo(Reward::class);
    }

    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }
}
