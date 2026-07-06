<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('required_self_cc', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->text('reward_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_ranks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mlm_user_id')->constrained('mlm_users')->onDelete('cascade');
            $table->foreignId('rank_id')->constrained('ranks')->onDelete('cascade');
            $table->decimal('current_cc_at_time', 15, 2)->default(0);
            $table->boolean('is_current')->default(true);
            $table->timestamp('achieved_at')->useCurrent();
            $table->timestamps();

            $table->index(['mlm_user_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ranks');
        Schema::dropIfExists('ranks');
    }
};
