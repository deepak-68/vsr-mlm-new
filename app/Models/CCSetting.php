<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CCSetting extends Model
{
    protected $table = 'cc_settings';
    protected $fillable = ['key', 'value', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'value' => 'decimal:2',
    ];

    public static function getActiveRate()
    {
        $setting = static::where('key', 'conversion_rate')->where('is_active', true)->first();
        return $setting ? $setting->value : 1.00;
    }

    public static function getWithdrawalChargePercent(): float
    {
        $setting = static::where('key', 'withdrawal_charge')->where('is_active', true)->first();
        return $setting ? (float) $setting->value : 0.00;
    }
}
