<?php

namespace App\Services;

use App\Enums\MatchFormat;
use App\Enums\TeamMode;
use App\Models\GameSession;
use Illuminate\Support\Collection;

class RotationService
{
    /**
     * Returns the next team assignment for the session.
     *
     * @return array{team_1: array, team_2: array, sitting_out: array}
     */
    public function getNextAssignment(GameSession $session): array
    {
        if ($session->team_mode === TeamMode::Handpick) {
            return $this->handpickAssignment($session);
        }

        return $this->randomAssignment($session);
    }

    private function handpickAssignment(GameSession $session): array
    {
        $lastMatch = $session->matches()->latest('played_at')->with('players')->first();

        if (!$lastMatch) {
            return ['team_1' => [], 'team_2' => [], 'sitting_out' => $session->player_ids];
        }

        $team1 = $lastMatch->players->where('team', 1)->map(fn ($p) => [
            'user_id' => $p->user_id,
            'position' => $p->position->value,
        ])->values()->toArray();

        $team2 = $lastMatch->players->where('team', 2)->map(fn ($p) => [
            'user_id' => $p->user_id,
            'position' => $p->position->value,
        ])->values()->toArray();

        $playingIds = $lastMatch->players->pluck('user_id')->toArray();
        $sittingOut = array_values(array_diff($session->player_ids, $playingIds));

        return ['team_1' => $team1, 'team_2' => $team2, 'sitting_out' => $sittingOut];
    }

    private function randomAssignment(GameSession $session): array
    {
        $allPlayerIds = collect($session->player_ids);
        $matches = $session->matches()->with('players')->orderBy('played_at')->get();
        $playersPerTeam = $session->format === MatchFormat::Doubles ? 2 : 1;
        $totalActive = $playersPerTeam * 2;

        // Step 1: Count games per player
        $gamesPlayed = $allPlayerIds->mapWithKeys(fn ($id) => [$id => 0])->toArray();
        foreach ($matches as $match) {
            foreach ($match->players as $player) {
                if (array_key_exists($player->user_id, $gamesPlayed)) {
                    $gamesPlayed[$player->user_id]++;
                }
            }
        }

        // Step 2: Select players with fewest games (break ties randomly)
        $sorted = collect($gamesPlayed)
            ->map(fn ($count, $id) => ['id' => $id, 'count' => $count, 'rand' => mt_rand()])
            ->sortBy(['count', 'rand'])
            ->pluck('id')
            ->values();

        $selectedIds = $sorted->take($totalActive)->values();
        $sittingOut = $allPlayerIds->diff($selectedIds)->values()->toArray();

        // Step 3: Build partner/opponent history matrices, then pick fairest split
        [$partnerCounts, $oppCounts] = $this->buildPairingMatrices($matches);

        $split = $this->findFairestSplit($selectedIds->toArray(), $playersPerTeam, $partnerCounts, $oppCounts);

        // Step 4: If no valid split from selected players, try other player groups
        if ($split === null && $allPlayerIds->count() > $totalActive) {
            $combos = $this->combinations($allPlayerIds->toArray(), $totalActive);
            shuffle($combos);

            foreach ($combos as $combo) {
                $split = $this->findFairestSplit($combo, $playersPerTeam, $partnerCounts, $oppCounts);
                if ($split !== null) {
                    $selectedIds = collect($combo);
                    $sittingOut = $allPlayerIds->diff($selectedIds)->values()->toArray();
                    break;
                }
            }
        }

        // Fallback: randomize
        if ($split === null) {
            $ids = $selectedIds->shuffle()->values();
            $split = [
                $ids->take($playersPerTeam)->toArray(),
                $ids->skip($playersPerTeam)->take($playersPerTeam)->toArray(),
            ];
        }

        $positions = $playersPerTeam === 2 ? ['left', 'right'] : ['solo'];

        return [
            'team_1' => collect($split[0])->values()->map(fn ($id, $i) => [
                'user_id' => $id,
                'position' => $positions[$i] ?? 'right',
            ])->toArray(),
            'team_2' => collect($split[1])->values()->map(fn ($id, $i) => [
                'user_id' => $id,
                'position' => $positions[$i] ?? 'right',
            ])->toArray(),
            'sitting_out' => $sittingOut,
        ];
    }

    // Returns [partnerCounts, oppCounts] where each is a nested array [id][id] => int.
    private function buildPairingMatrices(Collection $matches): array
    {
        $partnerCounts = [];
        $oppCounts = [];

        $inc = function (array &$matrix, $a, $b): void {
            $matrix[$a][$b] = ($matrix[$a][$b] ?? 0) + 1;
            $matrix[$b][$a] = ($matrix[$b][$a] ?? 0) + 1;
        };

        foreach ($matches as $match) {
            $t1 = $match->players->where('team', 1)->pluck('user_id')->values()->toArray();
            $t2 = $match->players->where('team', 2)->pluck('user_id')->values()->toArray();

            foreach ($t1 as $i => $a) {
                foreach ($t1 as $j => $b) {
                    if ($i < $j) {
                        $inc($partnerCounts, $a, $b);
                    }
                }
            }
            foreach ($t2 as $i => $a) {
                foreach ($t2 as $j => $b) {
                    if ($i < $j) {
                        $inc($partnerCounts, $a, $b);
                    }
                }
            }
            foreach ($t1 as $a) {
                foreach ($t2 as $b) {
                    $inc($oppCounts, $a, $b);
                }
            }
        }

        return [$partnerCounts, $oppCounts];
    }

    // Scores every possible split by total partner + opponent pair history.
    // Lower score = fresher pairings = fairer. Returns the minimum-cost split.
    private function findFairestSplit(array $playerIds, int $playersPerTeam, array $partnerCounts, array $oppCounts): ?array
    {
        $team1Combos = $this->combinations($playerIds, $playersPerTeam);
        shuffle($team1Combos);

        $bestSplit = null;
        $bestCost = PHP_INT_MAX;

        foreach ($team1Combos as $team1) {
            $team2 = array_values(array_diff($playerIds, $team1));
            if (count($team2) !== $playersPerTeam) {
                continue;
            }

            $cost = 0;

            // Partner costs: penalise pairs that have been teammates before
            foreach ($this->pairs($team1) as [$a, $b]) {
                $cost += $partnerCounts[$a][$b] ?? 0;
            }
            foreach ($this->pairs($team2) as [$a, $b]) {
                $cost += $partnerCounts[$a][$b] ?? 0;
            }

            // Opponent costs: penalise cross-team pairs that have faced each other before
            foreach ($team1 as $a) {
                foreach ($team2 as $b) {
                    $cost += $oppCounts[$a][$b] ?? 0;
                }
            }

            if ($cost < $bestCost) {
                $bestCost = $cost;
                $bestSplit = [$team1, $team2];
            }
        }

        return $bestSplit;
    }

    // Returns all unique pairs from an array as [[a,b], ...].
    private function pairs(array $arr): array
    {
        $pairs = [];
        for ($i = 0; $i < count($arr); $i++) {
            for ($j = $i + 1; $j < count($arr); $j++) {
                $pairs[] = [$arr[$i], $arr[$j]];
            }
        }
        return $pairs;
    }

    private function combinations(array $arr, int $k): array
    {
        if ($k === 0) {
            return [[]];
        }
        if (count($arr) < $k) {
            return [];
        }

        $results = [];
        for ($i = 0; $i <= count($arr) - $k; $i++) {
            foreach ($this->combinations(array_slice($arr, $i + 1), $k - 1) as $combo) {
                $results[] = array_merge([$arr[$i]], $combo);
            }
        }

        return $results;
    }
}
