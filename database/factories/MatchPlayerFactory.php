<?php

namespace Database\Factories;

use App\Enums\PlayerPosition;
use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatchPlayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'match_id' => GameMatch::factory(),
            'user_id' => User::factory(),
            'team' => fake()->randomElement([1, 2]),
            'position' => fake()->randomElement(PlayerPosition::cases()),
        ];
    }
}
