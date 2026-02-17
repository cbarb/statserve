<?php

namespace Database\Seeders;

use App\Models\Season;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    public function run(): void
    {
        Season::create([
            'name' => 'Fall 2025',
            'starts_at' => '2025-09-01',
            'ends_at' => '2025-11-30',
            'status' => 'completed',
        ]);

        Season::create([
            'name' => 'Winter 2026',
            'starts_at' => '2025-12-01',
            'ends_at' => '2026-02-28',
            'status' => 'active',
        ]);

        Season::create([
            'name' => 'Spring 2026',
            'starts_at' => '2026-03-01',
            'ends_at' => '2026-05-31',
            'status' => 'upcoming',
        ]);
    }
}
