<?php

namespace Database\Factories;

use App\Enums\MatchFormat;
use App\Enums\SessionStatus;
use App\Enums\TeamMode;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameSessionFactory extends Factory
{
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'group_id' => Group::factory(),
            'started_by' => User::factory(),
            'format' => fake()->randomElement(MatchFormat::cases()),
            'team_mode' => fake()->randomElement(TeamMode::cases()),
            'status' => SessionStatus::Completed,
            'player_ids' => [],
            'started_at' => $startedAt,
            'finished_at' => (clone $startedAt)->modify('+' . rand(30, 180) . ' minutes'),
        ];
    }
}
