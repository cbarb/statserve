<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BadgeSeeder::class,
            ChallengeSeeder::class,
            SeasonSeeder::class,
            UserSeeder::class,
            GroupSeeder::class,
            GameSessionSeeder::class,
            GamificationSeeder::class,
            RivalrySeeder::class,
        ]);
    }
}
