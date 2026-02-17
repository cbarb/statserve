<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $testUser = $users->firstWhere('email', 'test@example.com');

        // Group 1: owned by test user, 6 members
        $group1 = Group::create([
            'name' => 'Court Crushers',
            'created_by' => $testUser->id,
            'timezone' => 'America/New_York',
        ]);
        $group1Members = $users->take(6);
        foreach ($group1Members as $i => $user) {
            $role = $user->id === $testUser->id ? 'owner' : ($i === 1 ? 'admin' : 'member');
            $group1->members()->attach($user->id, [
                'role' => $role,
                'joined_at' => now()->subDays(30 - $i),
            ]);
        }

        // Group 2: owned by another user, 8 members
        $owner2 = $users[1];
        $group2 = Group::create([
            'name' => 'Dink Dynasty',
            'created_by' => $owner2->id,
            'timezone' => 'America/Chicago',
        ]);
        $group2Members = $users->take(8);
        foreach ($group2Members as $i => $user) {
            $role = $user->id === $owner2->id ? 'owner' : ($i === 2 ? 'admin' : 'member');
            $group2->members()->attach($user->id, [
                'role' => $role,
                'joined_at' => now()->subDays(20 - $i),
            ]);
        }

        // Add free user to Dink Dynasty
        $freeUser = $users->firstWhere('email', 'free@example.com');
        if ($freeUser && !$group2->members()->where('user_id', $freeUser->id)->exists()) {
            $group2->members()->attach($freeUser->id, [
                'role' => 'member',
                'joined_at' => now()->subDays(5),
            ]);
        }

        // Group 3: smaller group, 4 members
        $owner3 = $users[3];
        $group3 = Group::create([
            'name' => 'Kitchen Crew',
            'created_by' => $owner3->id,
            'timezone' => 'America/Los_Angeles',
        ]);
        $group3Members = $users->slice(2, 4);
        foreach ($group3Members as $i => $user) {
            $role = $user->id === $owner3->id ? 'owner' : 'member';
            $group3->members()->attach($user->id, [
                'role' => $role,
                'joined_at' => now()->subDays(15 - $i),
            ]);
        }
    }
}
