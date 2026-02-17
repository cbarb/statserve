<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('started_by')->constrained('users')->cascadeOnDelete();
            $table->string('format', 20);
            $table->string('team_mode', 20);
            $table->string('status', 20)->default('active');
            $table->json('player_ids');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'status']);
            $table->index(['group_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
