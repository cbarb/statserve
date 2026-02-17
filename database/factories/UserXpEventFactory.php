<?php

namespace Database\Factories;

use App\Enums\XpSourceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserXpEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source_type' => fake()->randomElement(XpSourceType::cases()),
            'source_id' => null,
            'xp_amount' => fake()->randomElement([10, 15, 20, 25, 30, 40, 50, 75, 100]),
            'description' => fake()->sentence(4),
        ];
    }
}
