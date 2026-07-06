<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mlm_users', function (Blueprint $table) {
            $table->boolean('privacy_policy_accepted')->default(false)->after('is_payout_active');
            $table->boolean('terms_accepted')->default(false)->after('privacy_policy_accepted');
        });
    }

    public function down(): void
    {
        Schema::table('mlm_users', function (Blueprint $table) {
            $table->dropColumn(['privacy_policy_accepted', 'terms_accepted']);
        });
    }
};
