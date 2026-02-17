<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserLevelFactory extends Factory
{
    public function definition(): array
    {
        $level = rand(1, 8);
        $cumulativeXp = [1 => 0, 2 => 100, 3 => 300, 4 => 650, 5 => 1150, 6 => 1850, 7 => 2850, 8 => 4250];
        $xp = ($cumulativeXp[$level] ?? 0) + rand(0, 200);

        return [
            'user_id' => User::factory(),
            'current_level' => $level,
            'total_xp' => $xp,
        ];
    }
}
