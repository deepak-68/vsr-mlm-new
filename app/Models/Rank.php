<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    protected $fillable = [
        'name', 'slug', 'required_self_cc',
        'sort_order', 'reward_description', 'is_active'
    ];

    protected $casts = [
        'required_self_cc' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function reward()
    {
        return $this->hasOne(Reward::class);
    }

    public function userRanks()
    {
        return $this->hasMany(UserRank::class);
    }
}
