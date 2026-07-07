<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('user_bank_detail_id')->nullable()->after('bank_detail_id');
            $table->foreign('user_bank_detail_id')->references('id')->on('user_bank_details')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->dropForeign(['user_bank_detail_id']);
            $table->dropColumn('user_bank_detail_id');
        });
    }
};
