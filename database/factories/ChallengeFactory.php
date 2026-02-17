<?php

namespace Database\Factories;

use App\Enums\ChallengeDifficulty;
use App\Enums\ChallengeType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChallengeFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'slug' => Str::slug($name),
            'name' => ucwords($name),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(ChallengeType::cases()),
            'difficulty' => fake()->randomElement(ChallengeDifficulty::cases()),
            'criteria_type' => 'matches_played',
            'criteria_value' => rand(1, 10),
            'xp_reward' => fake()->randomElement([15, 20, 25, 30, 50, 75, 100]),
            'bonus_logs_reward' => 0,
            'is_active' => true,
        ];
    }
}
