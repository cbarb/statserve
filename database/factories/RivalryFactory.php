<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RivalryFactory extends Factory
{
    public function definition(): array
    {
        $u1Wins = rand(5, 12);
        $u2Wins = rand(5, 12);

        return [
            'group_id' => Group::factory(),
            'user_1_id' => User::factory(),
            'user_2_id' => User::factory(),
            'user_1_wins' => $u1Wins,
            'user_2_wins' => $u2Wins,
            'total_matches' => $u1Wins + $u2Wins,
            'last_match_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'is_active' => true,
            'detected_at' => fake()->dateTimeBetween('-30 days', '-7 days'),
        ];
    }
}
