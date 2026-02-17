<?php

namespace Database\Factories;

use App\Models\Badge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserBadgeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'badge_id' => Badge::factory(),
            'earned_at' => fake()->dateTimeBetween('-60 days', 'now'),
            'is_pinned' => fake()->boolean(20),
        ];
    }
}
