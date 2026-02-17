<?php

namespace Database\Factories;

use App\Enums\SeasonStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Winter 2026',
            'starts_at' => '2025-12-01',
            'ends_at' => '2026-02-28',
            'status' => SeasonStatus::Active,
        ];
    }
}
