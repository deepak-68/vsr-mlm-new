<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBankDetail extends Model
{
    protected $table = 'user_bank_details';

    protected $fillable = [
        'user_id',
        'account_holder_name',
        'bank_name',
        'account_number',
        'ifsc_code',
        'bank_attachment',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(MlmUser::class, 'user_id');
    }
}
