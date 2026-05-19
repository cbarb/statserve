<?php

namespace Database\Seeders;

use App\Enums\MatchFormat;
use App\Enums\MatchStatus;
use App\Enums\PlayerPosition;
use App\Enums\SessionStatus;
use App\Enums\TeamMode;
use App\Models\GameMatch;
use App\Models\GameSession;
use App\Models\Group;
use App\Models\MatchPlayer;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestMatchesSeeder extends Seeder
{
    public function run(): void
    {
        $carlos = User::where('email', 'carlos@test.com')->first();

        if (! $carlos) {
            $this->command->error('Run TestUsersSeeder first.');
            return;
        }

        $group = Group::where('created_by', $carlos->id)->first();

        if (! $group) {
            $this->command->error('No group found for carlos@test.com. Run TestUsersSeeder first.');
            return;
        }

        $members = $group->members()->get();
        $totalMatches = 0;

        // Create 20 sessions spread over the past 90 days
        for ($s = 0; $s < 20; $s++) {
            $daysAgo = rand(1, 90);
            $isDoubles = $members->count() >= 4 && fake()->boolean(65);
            $format = $isDoubles ? MatchFormat::Doubles : MatchFormat::Singles;
            $playerPool = $members->shuffle();
            $startedAt = now()->subDays($daysAgo)->setHour(rand(9, 20))->setMinute(0)->setSecond(0);

            $session = GameSession::create([
                'group_id' => $group->id,
                'started_by' => $playerPool->first()->id,
                'format' => $format,
                'team_mode' => fake()->randomElement(TeamMode::cases()),
                'status' => SessionStatus::Completed,
                'player_ids' => $playerPool->pluck('id')->toArray(),
                'started_at' => $startedAt,
                'finished_at' => (clone $startedAt)->modify('+' . rand(45, 150) . ' minutes'),
            ]);

            // 4–12 matches per session
            $matchCount = rand(4, 12);
            for ($m = 0; $m < $matchCount; $m++) {
                $playedAt = (clone $startedAt)->modify('+' . ($m * rand(8, 15)) . ' minutes');

                // Realistic pickleball scores (first to 11, win by 2 — simplified)
                $score1 = rand(0, 11);
                $score2 = $score1 === 11 ? rand(0, 9) : 11;
                // Occasionally a close game
                if (fake()->boolean(20)) {
                    $score1 = rand(9, 13);
                    $score2 = $score1 - rand(1, 2);
                }
                $winningTeam = $score1 > $score2 ? 1 : 2;

                $match = GameMatch::create([
                    'group_id' => $group->id,
                    'session_id' => $session->id,
                    'format' => $format,
                    'status' => MatchStatus::Completed,
                    'team_1_score' => $score1,
                    'team_2_score' => $score2,
                    'winning_team' => $winningTeam,
                    'logged_by' => $playerPool->first()->id,
                    'played_at' => $playedAt,
                ]);

                if ($isDoubles && $playerPool->count() >= 4) {
                    $four = $playerPool->take(4)->values();
                    MatchPlayer::create(['match_id' => $match->id, 'user_id' => $four[0]->id, 'team' => 1, 'position' => PlayerPosition::Left->value]);
                    MatchPlayer::create(['match_id' => $match->id, 'user_id' => $four[1]->id, 'team' => 1, 'position' => PlayerPosition::Right->value]);
                    MatchPlayer::create(['match_id' => $match->id, 'user_id' => $four[2]->id, 'team' => 2, 'position' => PlayerPosition::Left->value]);
                    MatchPlayer::create(['match_id' => $match->id, 'user_id' => $four[3]->id, 'team' => 2, 'position' => PlayerPosition::Right->value]);
                } else {
                    $two = $playerPool->take(2)->values();
                    MatchPlayer::create(['match_id' => $match->id, 'user_id' => $two[0]->id, 'team' => 1, 'position' => PlayerPosition::Solo->value]);
                    MatchPlayer::create(['match_id' => $match->id, 'user_id' => $two[1]->id, 'team' => 2, 'position' => PlayerPosition::Solo->value]);
                }

                $playerPool = $playerPool->shuffle();
                $totalMatches++;
            }
        }

        $this->command->info("Created {$totalMatches} matches across 20 sessions in \"{$group->name}\".");
    }
}
