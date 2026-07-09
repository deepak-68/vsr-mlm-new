<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cc_settings', function (Blueprint $table) {
            $table->string('key', 50)->nullable()->unique()->after('id');
        });

        DB::table('cc_settings')->whereNull('key')->update(['key' => 'conversion_rate']);

        DB::table('cc_settings')->insert([
            'key' => 'withdrawal_charge',
            'value' => 0.00,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('cc_settings')->where('key', 'withdrawal_charge')->delete();

        Schema::table('cc_settings', function (Blueprint $table) {
            $table->dropColumn('key');
        });
    }
};
