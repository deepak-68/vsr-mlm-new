<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pair_matching_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('mlm_users')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->unsignedBigInteger('from_user_id');
            $table->integer('pairs_count')->default(0);
            $table->decimal('left_cc_used', 12, 2)->default(0);
            $table->decimal('right_cc_used', 12, 2)->default(0);
            $table->decimal('income_amount', 12, 2)->default(0);
            $table->decimal('left_cc_before', 12, 2)->default(0);
            $table->decimal('left_cc_after', 12, 2)->default(0);
            $table->decimal('right_cc_before', 12, 2)->default(0);
            $table->decimal('right_cc_after', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['user_id', 'order_id']);
            $table->unique(['user_id', 'order_id'], 'pair_match_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pair_matching_logs');
    }
};
