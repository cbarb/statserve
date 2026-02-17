<?php

namespace Database\Factories;

use App\Enums\MatchFormat;
use App\Enums\MatchStatus;
use App\Models\GameSession;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameMatchFactory extends Factory
{
    public function definition(): array
    {
        $score1 = rand(0, 11);
        $score2 = $score1 === 11 ? rand(0, 9) : 11;
        $winningTeam = $score1 > $score2 ? 1 : 2;

        return [
            'group_id' => Group::factory(),
            'session_id' => GameSession::factory(),
            'format' => fake()->randomElement(MatchFormat::cases()),
            'status' => MatchStatus::Completed,
            'team_1_score' => $score1,
            'team_2_score' => $score2,
            'winning_team' => $winningTeam,
            'logged_by' => User::factory(),
            'played_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
