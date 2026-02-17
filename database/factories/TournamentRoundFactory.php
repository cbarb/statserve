<?php

namespace Database\Factories;

use App\Enums\TournamentBracketSide;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class TournamentRoundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'round_number' => 1,
            'bracket' => TournamentBracketSide::Winners,
            'match_id' => null,
            'entry_1_id' => null,
            'entry_2_id' => null,
            'winner_entry_id' => null,
            'scheduled_at' => null,
        ];
    }
}
