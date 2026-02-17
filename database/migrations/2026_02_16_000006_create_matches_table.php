<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('game_sessions')->nullOnDelete();
            $table->string('format', 20);
            $table->string('status', 20)->default('in_progress');
            $table->unsignedTinyInteger('team_1_score')->default(0);
            $table->unsignedTinyInteger('team_2_score')->default(0);
            $table->unsignedTinyInteger('winning_team')->nullable();
            $table->foreignId('logged_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('played_at')->useCurrent();
            $table->timestamps();

            $table->index(['group_id', 'status', 'played_at']);
            $table->index(['group_id', 'played_at']);
            $table->index(['logged_by', 'played_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
