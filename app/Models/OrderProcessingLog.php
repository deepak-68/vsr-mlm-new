<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderProcessingLog extends Model
{
    protected $table = 'order_processing_logs';

    protected $fillable = [
        'order_id',
        'step',
        'status',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
