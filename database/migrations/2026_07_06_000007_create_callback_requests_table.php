<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('callback_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mlm_user_id')->constrained('mlm_users')->onDelete('cascade');
            $table->date('preferred_date');
            $table->time('preferred_time');
            $table->text('issue_summary')->nullable();
            $table->string('status')->default('PENDING');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('callback_requests');
    }
};
