<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('round_number');
            $table->string('bracket', 20);
            $table->foreignId('match_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('entry_1_id')->nullable()->constrained('tournament_entries')->nullOnDelete();
            $table->foreignId('entry_2_id')->nullable()->constrained('tournament_entries')->nullOnDelete();
            $table->foreignId('winner_entry_id')->nullable()->constrained('tournament_entries')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_rounds');
    }
};
