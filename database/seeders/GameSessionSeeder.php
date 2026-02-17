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
use Illuminate\Database\Seeder;

class GameSessionSeeder extends Seeder
{
    public function run(): void
    {
        $groups = Group::with('members')->get();

        foreach ($groups as $group) {
            $members = $group->members;
            if ($members->count() < 2) {
                continue;
            }

            // Create 3-5 sessions per group
            $sessionCount = rand(3, 5);
            for ($s = 0; $s < $sessionCount; $s++) {
                $isDoubles = $members->count() >= 4 && fake()->boolean(70);
                $format = $isDoubles ? MatchFormat::Doubles : MatchFormat::Singles;
                $playerPool = $members->shuffle();
                $poolIds = $playerPool->pluck('id')->toArray();

                $startedAt = now()->subDays(rand(1, 25))->subHours(rand(0, 8));
                $session = GameSession::create([
                    'group_id' => $group->id,
                    'started_by' => $playerPool->first()->id,
                    'format' => $format,
                    'team_mode' => fake()->randomElement(TeamMode::cases()),
                    'status' => SessionStatus::Completed,
                    'player_ids' => $poolIds,
                    'started_at' => $startedAt,
                    'finished_at' => (clone $startedAt)->modify('+' . rand(30, 120) . ' minutes'),
                ]);

                // Create 3-8 matches per session
                $matchCount = rand(3, 8);
                for ($m = 0; $m < $matchCount; $m++) {
                    $playedAt = (clone $startedAt)->modify('+' . ($m * rand(8, 15)) . ' minutes');

                    $score1 = rand(0, 11);
                    $score2 = $score1 === 11 ? rand(0, 9) : 11;
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

                    // Assign players
                    if ($isDoubles && $playerPool->count() >= 4) {
                        $gamePlayers = $playerPool->take(4)->values();
                        MatchPlayer::create(['match_id' => $match->id, 'user_id' => $gamePlayers[0]->id, 'team' => 1, 'position' => PlayerPosition::Left->value]);
                        MatchPlayer::create(['match_id' => $match->id, 'user_id' => $gamePlayers[1]->id, 'team' => 1, 'position' => PlayerPosition::Right->value]);
                        MatchPlayer::create(['match_id' => $match->id, 'user_id' => $gamePlayers[2]->id, 'team' => 2, 'position' => PlayerPosition::Left->value]);
                        MatchPlayer::create(['match_id' => $match->id, 'user_id' => $gamePlayers[3]->id, 'team' => 2, 'position' => PlayerPosition::Right->value]);
                    } else {
                        $gamePlayers = $playerPool->take(2)->values();
                        MatchPlayer::create(['match_id' => $match->id, 'user_id' => $gamePlayers[0]->id, 'team' => 1, 'position' => PlayerPosition::Solo->value]);
                        MatchPlayer::create(['match_id' => $match->id, 'user_id' => $gamePlayers[1]->id, 'team' => 2, 'position' => PlayerPosition::Solo->value]);
                    }

                    // Shuffle for next game variation
                    $playerPool = $playerPool->shuffle();
                }
            }
        }
    }
}
