<?php

namespace Database\Factories;

use App\Enums\BracketType;
use App\Enums\MatchFormat;
use App\Enums\TournamentStatus;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TournamentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'created_by' => User::factory(),
            'name' => fake()->words(3, true) . ' Tournament',
            'format' => MatchFormat::Doubles,
            'bracket_type' => BracketType::SingleElimination,
            'max_players' => 16,
            'min_rating' => null,
            'max_rating' => null,
            'status' => TournamentStatus::Registration,
        ];
    }
}
