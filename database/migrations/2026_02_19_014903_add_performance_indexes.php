<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_rounds', function (Blueprint $table) {
            $table->index(['tournament_id', 'bracket', 'round_number'], 'tr_tournament_bracket_round');
            $table->index(['entry_1_id']);
            $table->index(['entry_2_id']);
            $table->index(['winner_entry_id']);
        });

        Schema::table('tournament_entries', function (Blueprint $table) {
            $table->index(['user_id']);
            $table->index(['status']);
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->index(['is_public', 'status', 'starts_at'], 'tournaments_public_status_starts');
            $table->index(['created_by']);
        });

        Schema::table('match_players', function (Blueprint $table) {
            $table->index(['user_id', 'match_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tournament_rounds', function (Blueprint $table) {
            $table->dropIndex('tr_tournament_bracket_round');
            $table->dropIndex(['entry_1_id']);
            $table->dropIndex(['entry_2_id']);
            $table->dropIndex(['winner_entry_id']);
        });

        Schema::table('tournament_entries', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropIndex('tournaments_public_status_starts');
            $table->dropIndex(['created_by']);
        });

        Schema::table('match_players', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'match_id']);
        });
    }
};
