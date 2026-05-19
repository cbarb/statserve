<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use App\Models\UserBonusLog;
use App\Models\UserLevel;
use Illuminate\Database\Seeder;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Main user — login with carlos@test.com / password
        $carlos = User::factory()->create([
            'name' => 'Carlos',
            'email' => 'carlos@test.com',
            'timezone' => 'America/New_York',
        ]);

        // 8 other players
        $playerData = [
            ['name' => 'Mike Torres',    'email' => 'mike@test.com'],
            ['name' => 'Sarah Chen',     'email' => 'sarah@test.com'],
            ['name' => 'Jordan Blake',   'email' => 'jordan@test.com'],
            ['name' => 'Priya Nair',     'email' => 'priya@test.com'],
            ['name' => 'Dave Kowalski',  'email' => 'dave@test.com'],
            ['name' => 'Tina Reeves',    'email' => 'tina@test.com'],
            ['name' => 'Marcus Webb',    'email' => 'marcus@test.com'],
            ['name' => 'Lily Huang',     'email' => 'lily@test.com'],
        ];

        $players = collect([$carlos]);
        foreach ($playerData as $data) {
            $players->push(User::factory()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'timezone' => 'America/New_York',
            ]));
        }

        // Set up XP/level and bonus log for each user
        foreach ($players as $user) {
            UserLevel::firstOrCreate(['user_id' => $user->id], [
                'current_level' => 1,
                'total_xp' => 0,
            ]);
            UserBonusLog::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
        }

        // Create the group
        $group = Group::create([
            'name' => 'Pickle Season 4',
            'created_by' => $carlos->id,
            'timezone' => 'America/New_York',
        ]);

        // Attach all players — Carlos is owner, Mike is admin, rest are members
        foreach ($players as $i => $user) {
            $role = match(true) {
                $user->id === $carlos->id => 'owner',
                $i === 1 => 'admin',
                default => 'member',
            };
            $group->members()->attach($user->id, [
                'role' => $role,
                'joined_at' => now()->subDays(60 - ($i * 5)),
            ]);
        }

        $this->command->info('Test users created. Login: carlos@test.com / password');
        $this->command->info("Group: {$group->name} (slug: {$group->slug})");
    }
}
