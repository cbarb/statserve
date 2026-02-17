<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupMilestoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'milestone_slug' => 'games_played',
            'tier_reached' => 1,
            'reached_at' => now(),
            'progress' => 50,
            'target' => 50,
        ];
    }
}
