<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallbackRequest extends Model
{
    protected $fillable = [
        'mlm_user_id', 'preferred_date', 'preferred_time',
        'issue_summary', 'status', 'admin_notes'
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'preferred_time' => 'string',
    ];

    const STATUS_PENDING = 'PENDING';
    const STATUS_SCHEDULED = 'SCHEDULED';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_CANCELLED = 'CANCELLED';

    public function user()
    {
        return $this->belongsTo(MlmUser::class, 'mlm_user_id');
    }
}
