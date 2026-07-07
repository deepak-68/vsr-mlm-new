<?php

use App\Models\Wallet;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (Wallet::count() === 0) {
            Wallet::create([
                'name' => 'Commission Wallet',
                'code' => 'COMMISSION',
                'currency_code' => 'INR',
                'eligibility' => 'ALL',
                'type' => 'BOTH',
                'is_active' => true,
                'min_balance' => 0,
                'description' => 'Default commission wallet for income credits.',
            ]);
        }
    }

    public function down(): void
    {
        Wallet::where('code', 'COMMISSION')->delete();
    }
};
