<?php

namespace Database\Factories;

use App\Enums\TournamentEntryStatus;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TournamentEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'user_id' => User::factory(),
            'partner_id' => null,
            'seed' => null,
            'status' => TournamentEntryStatus::Registered,
        ];
    }
}
