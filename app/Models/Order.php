<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    
    protected $fillable = [
        'user_id',
        'purchased_for_user_id',
        'package_id',
        'order_date',
        'total_amount',
        'total_cc_points',
        'status',
        'order_type',
        'refund_policy',
        'payment_mode',
        'note',
        'transaction_number',
        'payment_proof',
        'order_number',
    ];

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const TYPE_SELF = 'SELF';
    public const TYPE_ADMIN = 'ADMIN';

    public const PAYMENT_WALLET = 'WALLET';
    public const PAYMENT_CASH = 'CASH';
    public const PAYMENT_MANUAL = 'MANUAL';

    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_cc_points' => 'decimal:2',
        'order_date' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(MlmUser::class, 'user_id');
    }

    public function purchasedForUser()
    {
        return $this->belongsTo(MlmUser::class, 'purchased_for_user_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'order_id');
    }
}