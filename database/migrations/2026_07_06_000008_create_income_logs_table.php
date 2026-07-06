<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('mlm_users')->cascadeOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('mlm_users');
            $table->string('income_type', 50);
            $table->decimal('cc_amount', 14, 2);
            $table->decimal('currency_amount', 14, 2);
            $table->decimal('balance_after', 14, 2)->default(0);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('order_number', 50)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'income_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_logs');
    }
};
