<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GroupFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(rand(2, 4), true) . ' Crew';

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(6)),
            'created_by' => User::factory(),
            'invite_code' => Str::lower(Str::random(8)),
            'timezone' => fake()->randomElement(['America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles']),
        ];
    }
}
