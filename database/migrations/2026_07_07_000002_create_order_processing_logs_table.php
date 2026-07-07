<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_processing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('step');
            $table->string('status');
            $table->text('details')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_processing_logs');
    }
};
