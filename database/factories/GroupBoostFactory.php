<?php

namespace Database\Factories;

use App\Enums\BoostStatus;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupBoostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'user_id' => User::factory(),
            'stripe_subscription_id' => 'sub_' . fake()->bothify('??????????'),
            'status' => BoostStatus::Active,
            'starts_at' => now()->subDays(rand(1, 30)),
            'ends_at' => now()->addDays(rand(1, 30)),
        ];
    }
}
