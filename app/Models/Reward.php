<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    protected $fillable = [
        'rank_id', 'name', 'description',
        'value_cc', 'reward_type', 'is_active'
    ];

    protected $casts = [
        'value_cc' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }
}
