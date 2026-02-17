<?php

namespace App\Services;

use App\Enums\MatchFormat;
use App\Enums\MatchStatus;
use App\Models\GameMatch;
use App\Models\MatchPlayer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatsService
{
    public function playerStats(int $userId, ?int $groupId = null, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $matches = $this->getPlayerMatches($userId, $groupId, $from, $to);

        if ($matches->isEmpty()) {
            return $this->emptyPlayerStats();
        }

        $wins = 0;
        $losses = 0;
        $pointsScored = 0;
        $pointsConceded = 0;
        $currentStreak = 0;
        $currentStreakType = null;
        $longestWinStreak = 0;
        $tempWinStreak = 0;
        $byFormat = [];

        foreach ($matches as $match) {
            $playerTeam = $match->player_team;
            $won = $match->winning_team === $playerTeam;

            if ($won) {
                $wins++;
            } else {
                $losses++;
            }

            $scored = $playerTeam === 1 ? $match->team_1_score : $match->team_2_score;
            $conceded = $playerTeam === 1 ? $match->team_2_score : $match->team_1_score;
            $pointsScored += $scored;
            $pointsConceded += $conceded;

            // Streaks (matches ordered by played_at asc)
            if ($won) {
                $tempWinStreak++;
                $longestWinStreak = max($longestWinStreak, $tempWinStreak);
            } else {
                $tempWinStreak = 0;
            }

            // Format breakdown
            $format = $match->format;
            if (!isset($byFormat[$format])) {
                $byFormat[$format] = ['games' => 0, 'wins' => 0, 'losses' => 0];
            }
            $byFormat[$format]['games']++;
            $byFormat[$format][$won ? 'wins' : 'losses']++;
        }

        // Current streak from most recent matches
        $reversed = $matches->reverse();
        foreach ($reversed as $match) {
            $won = $match->winning_team === $match->player_team;
            $type = $won ? 'W' : 'L';

            if ($currentStreakType === null) {
                $currentStreakType = $type;
            }

            if ($type === $currentStreakType) {
                $currentStreak++;
            } else {
                break;
            }
        }

        $games = $wins + $losses;

        return [
            'games' => $games,
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $games > 0 ? round(($wins / $games) * 100, 1) : 0,
            'points_scored' => $pointsScored,
            'points_conceded' => $pointsConceded,
            'point_diff' => $pointsScored - $pointsConceded,
            'current_streak' => $currentStreak,
            'current_streak_type' => $currentStreakType ?? 'W',
            'longest_win_streak' => $longestWinStreak,
            'by_format' => $byFormat,
        ];
    }

    public function groupLeaderboard(int $groupId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = GameMatch::where('group_id', $groupId)
            ->where('status', MatchStatus::Completed)
            ->whereNotNull('winning_team');

        if ($from) {
            $query->where('played_at', '>=', $from);
        }
        if ($to) {
            $query->where('played_at', '<=', $to);
        }

        $matches = $query->with('players')->orderBy('played_at', 'asc')->get();

        if ($matches->isEmpty()) {
            return [];
        }

        $stats = [];

        foreach ($matches as $match) {
            foreach ($match->players as $player) {
                $uid = $player->user_id;
                if (!isset($stats[$uid])) {
                    $stats[$uid] = [
                        'user_id' => $uid,
                        'games' => 0,
                        'wins' => 0,
                        'losses' => 0,
                        'points_scored' => 0,
                        'points_conceded' => 0,
                        'current_streak' => 0,
                        'current_streak_type' => null,
                        'longest_win_streak' => 0,
                        '_temp_win_streak' => 0,
                    ];
                }

                $won = $match->winning_team === $player->team;
                $stats[$uid]['games']++;
                $stats[$uid][$won ? 'wins' : 'losses']++;

                $scored = $player->team === 1 ? $match->team_1_score : $match->team_2_score;
                $conceded = $player->team === 1 ? $match->team_2_score : $match->team_1_score;
                $stats[$uid]['points_scored'] += $scored;
                $stats[$uid]['points_conceded'] += $conceded;

                if ($won) {
                    $stats[$uid]['_temp_win_streak']++;
                    $stats[$uid]['longest_win_streak'] = max(
                        $stats[$uid]['longest_win_streak'],
                        $stats[$uid]['_temp_win_streak']
                    );
                } else {
                    $stats[$uid]['_temp_win_streak'] = 0;
                }

                // Track current streak
                $type = $won ? 'W' : 'L';
                if ($stats[$uid]['current_streak_type'] === $type) {
                    $stats[$uid]['current_streak']++;
                } else {
                    $stats[$uid]['current_streak'] = 1;
                    $stats[$uid]['current_streak_type'] = $type;
                }
            }
        }

        // Clean up and compute derived fields
        $leaderboard = [];
        foreach ($stats as $uid => $s) {
            unset($s['_temp_win_streak']);
            $s['win_rate'] = $s['games'] > 0 ? round(($s['wins'] / $s['games']) * 100, 1) : 0;
            $s['point_diff'] = $s['points_scored'] - $s['points_conceded'];
            $s['current_streak_type'] = $s['current_streak_type'] ?? 'W';
            $leaderboard[] = $s;
        }

        // Sort by win rate desc, then games desc
        usort($leaderboard, function ($a, $b) {
            if ($a['win_rate'] !== $b['win_rate']) {
                return $b['win_rate'] <=> $a['win_rate'];
            }
            return $b['games'] <=> $a['games'];
        });

        return $leaderboard;
    }

    public function headToHead(int $userId1, int $userId2, int $groupId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        // Find matches in this group where both users played on opposite teams
        $query = DB::table('match_players as mp1')
            ->join('match_players as mp2', function ($join) {
                $join->on('mp1.match_id', '=', 'mp2.match_id')
                    ->whereColumn('mp1.team', '!=', 'mp2.team');
            })
            ->join('matches', function ($join) use ($groupId) {
                $join->on('matches.id', '=', 'mp1.match_id')
                    ->where('matches.group_id', '=', $groupId)
                    ->where('matches.status', '=', MatchStatus::Completed->value)
                    ->whereNotNull('matches.winning_team');
            })
            ->where('mp1.user_id', $userId1)
            ->where('mp2.user_id', $userId2);

        if ($from) {
            $query->where('matches.played_at', '>=', $from);
        }
        if ($to) {
            $query->where('matches.played_at', '<=', $to);
        }

        $matchIds = $query->pluck('mp1.match_id');

        if ($matchIds->isEmpty()) {
            return [
                'games' => 0,
                'player1_wins' => 0,
                'player2_wins' => 0,
                'player1_points' => 0,
                'player2_points' => 0,
                'by_format' => [],
                'recent_matches' => [],
            ];
        }

        $matches = GameMatch::whereIn('id', $matchIds)
            ->with('players')
            ->orderBy('played_at', 'desc')
            ->get();

        $p1Wins = 0;
        $p2Wins = 0;
        $p1Points = 0;
        $p2Points = 0;
        $byFormat = [];
        $recent = [];

        foreach ($matches as $match) {
            $p1Team = $match->players->firstWhere('user_id', $userId1)->team;
            $p2Team = $match->players->firstWhere('user_id', $userId2)->team;

            $p1Won = $match->winning_team === $p1Team;
            if ($p1Won) {
                $p1Wins++;
            } else {
                $p2Wins++;
            }

            $p1Score = $p1Team === 1 ? $match->team_1_score : $match->team_2_score;
            $p2Score = $p2Team === 1 ? $match->team_1_score : $match->team_2_score;
            $p1Points += $p1Score;
            $p2Points += $p2Score;

            $format = $match->format->value;
            if (!isset($byFormat[$format])) {
                $byFormat[$format] = ['games' => 0, 'player1_wins' => 0, 'player2_wins' => 0];
            }
            $byFormat[$format]['games']++;
            $byFormat[$format][$p1Won ? 'player1_wins' : 'player2_wins']++;

            if (count($recent) < 10) {
                $recent[] = [
                    'id' => $match->id,
                    'played_at' => $match->played_at->toISOString(),
                    'format' => $match->format->value,
                    'player1_score' => $p1Score,
                    'player2_score' => $p2Score,
                    'player1_won' => $p1Won,
                ];
            }
        }

        return [
            'games' => $matches->count(),
            'player1_wins' => $p1Wins,
            'player2_wins' => $p2Wins,
            'player1_points' => $p1Points,
            'player2_points' => $p2Points,
            'by_format' => $byFormat,
            'recent_matches' => $recent,
        ];
    }

    public function partnerships(int $groupId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        // Find all doubles matches in the group
        $query = GameMatch::where('group_id', $groupId)
            ->where('status', MatchStatus::Completed)
            ->where('format', MatchFormat::Doubles)
            ->whereNotNull('winning_team');

        if ($from) {
            $query->where('played_at', '>=', $from);
        }
        if ($to) {
            $query->where('played_at', '<=', $to);
        }

        $matches = $query->with('players')->get();

        $pairs = [];

        foreach ($matches as $match) {
            $teams = $match->players->groupBy('team');

            foreach ($teams as $team => $players) {
                if ($players->count() !== 2) {
                    continue;
                }

                $ids = $players->pluck('user_id')->sort()->values();
                $key = $ids[0] . '-' . $ids[1];

                if (!isset($pairs[$key])) {
                    $pairs[$key] = [
                        'player1_id' => $ids[0],
                        'player2_id' => $ids[1],
                        'games' => 0,
                        'wins' => 0,
                        'losses' => 0,
                        'points_scored' => 0,
                        'points_conceded' => 0,
                    ];
                }

                $won = $match->winning_team === $team;
                $pairs[$key]['games']++;
                $pairs[$key][$won ? 'wins' : 'losses']++;

                $scored = $team === 1 ? $match->team_1_score : $match->team_2_score;
                $conceded = $team === 1 ? $match->team_2_score : $match->team_1_score;
                $pairs[$key]['points_scored'] += $scored;
                $pairs[$key]['points_conceded'] += $conceded;
            }
        }

        $result = [];
        foreach ($pairs as $pair) {
            $pair['win_rate'] = $pair['games'] > 0 ? round(($pair['wins'] / $pair['games']) * 100, 1) : 0;
            $pair['avg_point_diff'] = $pair['games'] > 0
                ? round(($pair['points_scored'] - $pair['points_conceded']) / $pair['games'], 1)
                : 0;
            $result[] = $pair;
        }

        // Sort by games desc
        usort($result, fn ($a, $b) => $b['games'] <=> $a['games']);

        return $result;
    }

    public function bestPartner(int $userId, int $groupId, ?Carbon $from = null, ?Carbon $to = null): ?array
    {
        $partnerships = $this->partnerships($groupId, $from, $to);

        $userPairs = array_filter($partnerships, function ($p) use ($userId) {
            return ($p['player1_id'] === $userId || $p['player2_id'] === $userId) && $p['games'] >= 3;
        });

        if (empty($userPairs)) {
            return null;
        }

        usort($userPairs, fn ($a, $b) => $b['win_rate'] <=> $a['win_rate']);

        $best = $userPairs[0];

        return [
            'partner_id' => $best['player1_id'] === $userId ? $best['player2_id'] : $best['player1_id'],
            'games' => $best['games'],
            'wins' => $best['wins'],
            'losses' => $best['losses'],
            'win_rate' => $best['win_rate'],
        ];
    }

    public function dashboardStats(int $userId): array
    {
        $from = Carbon::now()->subDays(30);

        return $this->playerStats($userId, null, $from);
    }

    private function getPlayerMatches(int $userId, ?int $groupId = null, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = DB::table('matches')
            ->join('match_players', 'matches.id', '=', 'match_players.match_id')
            ->where('match_players.user_id', $userId)
            ->where('matches.status', MatchStatus::Completed->value)
            ->whereNotNull('matches.winning_team');

        if ($groupId) {
            $query->where('matches.group_id', $groupId);
        }
        if ($from) {
            $query->where('matches.played_at', '>=', $from);
        }
        if ($to) {
            $query->where('matches.played_at', '<=', $to);
        }

        return $query->orderBy('matches.played_at', 'asc')
            ->select(
                'matches.id',
                'matches.format',
                'matches.team_1_score',
                'matches.team_2_score',
                'matches.winning_team',
                'matches.played_at',
                'match_players.team as player_team'
            )
            ->get();
    }

    private function emptyPlayerStats(): array
    {
        return [
            'games' => 0,
            'wins' => 0,
            'losses' => 0,
            'win_rate' => 0,
            'points_scored' => 0,
            'points_conceded' => 0,
            'point_diff' => 0,
            'current_streak' => 0,
            'current_streak_type' => 'W',
            'longest_win_streak' => 0,
            'by_format' => [],
        ];
    }
}
