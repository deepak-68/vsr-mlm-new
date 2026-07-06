<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->foreignId('mlm_user_id')->constrained('mlm_users')->onDelete('cascade');
            $table->date('invoice_date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_cc', 15, 2)->default(0);
            $table->string('pdf_path')->nullable();
            $table->string('status')->default('GENERATED');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
