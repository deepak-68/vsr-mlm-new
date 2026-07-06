<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mlm_users', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
        Schema::table('grivances', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
    }

    public function down(): void
    {
        Schema::table('mlm_users', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->unique()->after('id');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->unique()->after('id');
        });
        Schema::table('grivances', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->unique()->after('id');
        });
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->unique()->after('id');
        });
    }
};
