<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Rivalry;
use Illuminate\Database\Seeder;

class RivalrySeeder extends Seeder
{
    public function run(): void
    {
        $groups = Group::with('members')->get();

        foreach ($groups as $group) {
            $members = $group->members;
            if ($members->count() < 4) {
                continue;
            }

            // Create 2 rivalries per group
            $shuffled = $members->shuffle();
            for ($i = 0; $i < min(2, intdiv($members->count(), 2)); $i++) {
                $u1 = $shuffled[$i * 2];
                $u2 = $shuffled[$i * 2 + 1];
                $u1Wins = rand(5, 12);
                $u2Wins = rand(5, 12);

                Rivalry::create([
                    'group_id' => $group->id,
                    'user_1_id' => min($u1->id, $u2->id),
                    'user_2_id' => max($u1->id, $u2->id),
                    'user_1_wins' => $u1Wins,
                    'user_2_wins' => $u2Wins,
                    'total_matches' => $u1Wins + $u2Wins,
                    'last_match_at' => fake()->dateTimeBetween('-7 days', 'now'),
                    'is_active' => true,
                    'detected_at' => fake()->dateTimeBetween('-30 days', '-7 days'),
                ]);
            }
        }
    }
}
