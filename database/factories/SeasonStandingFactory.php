<?php

namespace Database\Factories;

use App\Enums\RewardTier;
use App\Models\Group;
use App\Models\Season;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonStandingFactory extends Factory
{
    public function definition(): array
    {
        $wins = rand(5, 40);
        $losses = rand(3, 30);

        return [
            'season_id' => Season::factory(),
            'group_id' => Group::factory(),
            'user_id' => User::factory(),
            'rating_start' => 1000,
            'rating_end' => rand(850, 1300),
            'wins' => $wins,
            'losses' => $losses,
            'final_rank' => null,
            'reward_tier' => RewardTier::None,
        ];
    }
}
