<?php

namespace Database\Factories;

use App\Enums\ChallengeStatus;
use App\Models\Challenge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserChallengeFactory extends Factory
{
    public function definition(): array
    {
        $target = rand(2, 10);

        return [
            'user_id' => User::factory(),
            'challenge_id' => Challenge::factory(),
            'assigned_at' => now()->startOfWeek(),
            'expires_at' => now()->endOfWeek(),
            'progress' => rand(0, $target),
            'target' => $target,
            'status' => ChallengeStatus::Active,
            'claimed_at' => null,
        ];
    }
}
