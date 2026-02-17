<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserBonusLog;
use App\Models\UserLevel;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Fixed test user
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Free user (no subscription)
        User::factory()->create([
            'name' => 'Free User',
            'email' => 'free@example.com',
        ]);

        // 9 more users
        $users = User::factory(9)->create();

        // Create level and bonus log entries for all users
        foreach (User::all() as $user) {
            UserLevel::factory()->create(['user_id' => $user->id]);
            UserBonusLog::create(['user_id' => $user->id, 'balance' => rand(0, 5)]);
        }
    }
}
