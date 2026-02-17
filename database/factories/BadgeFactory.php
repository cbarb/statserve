<?php

namespace Database\Factories;

use App\Enums\BadgeCategory;
use App\Enums\BadgeTier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BadgeFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($name),
            'name' => ucwords($name),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(BadgeCategory::cases()),
            'tier' => fake()->randomElement(BadgeTier::cases()),
            'criteria_type' => 'wins_total',
            'criteria_value' => fake()->randomElement([10, 25, 50, 100, 150, 500]),
            'xp_reward' => 50,
            'icon' => null,
            'is_secret' => false,
        ];
    }
}
