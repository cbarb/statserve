<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rivalries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_1_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_2_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('user_1_wins')->default(0);
            $table->unsignedInteger('user_2_wins')->default(0);
            $table->unsignedInteger('total_matches')->default(0);
            $table->timestamp('last_match_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('detected_at')->useCurrent();
            $table->timestamps();

            $table->unique(['group_id', 'user_1_id', 'user_2_id']);
            $table->index(['group_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rivalries');
    }
};
