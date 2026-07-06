<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'mlm_user_id', 'type', 'title', 'message', 'data', 'is_read'
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(MlmUser::class, 'mlm_user_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
