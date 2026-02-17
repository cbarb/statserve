<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('season_standings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('rating_start')->default(1000);
            $table->integer('rating_end')->nullable();
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('final_rank')->nullable();
            $table->string('reward_tier', 10)->default('none');
            $table->timestamps();

            $table->unique(['season_id', 'group_id', 'user_id']);
            $table->index(['season_id', 'group_id', 'final_rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_standings');
    }
};
