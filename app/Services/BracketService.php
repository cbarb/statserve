<?php

namespace App\Services;

use App\Enums\BracketType;
use App\Enums\TournamentBracketSide;
use App\Enums\TournamentEntryStatus;
use App\Enums\TournamentStatus;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\TournamentRound;
use DomainException;

class BracketService
{
    public function startTournament(Tournament $tournament): void
    {
        if ($tournament->status !== TournamentStatus::Registration) {
            throw new DomainException('Tournament must be in registration status to start.');
        }

        $entries = $tournament->entries()->get();

        if ($entries->count() < 2) {
            throw new DomainException('At least 2 entries are required to start a tournament.');
        }

        // Shuffle and seed
        $entries = $entries->shuffle()->values();
        foreach ($entries as $i => $entry) {
            $entry->update(['seed' => $i + 1]);
        }

        match ($tournament->bracket_type) {
            BracketType::SingleElimination => $this->generateSingleElimination($tournament, $entries),
            BracketType::DoubleElimination => $this->generateDoubleElimination($tournament, $entries),
            BracketType::RoundRobin => $this->generateRoundRobin($tournament, $entries),
        };

        $tournament->update(['status' => TournamentStatus::InProgress]);
    }

    public function setWinner(TournamentRound $round, TournamentEntry $winner): void
    {
        $tournament = $round->tournament;

        if ($tournament->status !== TournamentStatus::InProgress) {
            throw new DomainException('Tournament is not in progress.');
        }

        if ($round->winner_entry_id !== null) {
            throw new DomainException('Winner has already been set for this round.');
        }

        if ($winner->id !== $round->entry_1_id && $winner->id !== $round->entry_2_id) {
            throw new DomainException('Winner must be one of the entries in this round.');
        }

        $round->update(['winner_entry_id' => $winner->id]);

        // Determine loser
        $loserId = $winner->id === $round->entry_1_id ? $round->entry_2_id : $round->entry_1_id;

        match ($tournament->bracket_type) {
            BracketType::SingleElimination => $this->advanceSingleElim($tournament, $round, $winner, $loserId),
            BracketType::DoubleElimination => $this->advanceDoubleElim($tournament, $round, $winner, $loserId),
            BracketType::RoundRobin => $this->advanceRoundRobin($tournament),
        };
    }

    public function getStandings(Tournament $tournament): array
    {
        if ($tournament->bracket_type === BracketType::RoundRobin) {
            return $this->getRoundRobinStandings($tournament);
        }

        return $this->getEliminationStandings($tournament);
    }

    // ─── Single Elimination ─────────────────────────────────

    private function generateSingleElimination(Tournament $tournament, $entries): void
    {
        $count = $entries->count();
        $bracketSize = $this->nextPowerOfTwo($count);
        $totalRounds = (int) ceil(log($bracketSize, 2));

        // Round 1 matchups
        $matchupsPerRound = $bracketSize / 2;

        for ($i = 0; $i < $matchupsPerRound; $i++) {
            $entry1 = $entries->get($i * 2);
            $entry2 = $entries->get($i * 2 + 1); // null if bye

            $round = TournamentRound::create([
                'tournament_id' => $tournament->id,
                'round_number' => 1,
                'bracket' => TournamentBracketSide::Winners,
                'entry_1_id' => $entry1?->id,
                'entry_2_id' => $entry2?->id,
            ]);

            // Auto-advance byes
            if ($entry1 && !$entry2) {
                $round->update(['winner_entry_id' => $entry1->id]);
            }
        }

        // Pre-create empty rounds for subsequent rounds
        for ($r = 2; $r <= $totalRounds; $r++) {
            $matchupsPerRound = $bracketSize / (int) pow(2, $r);
            for ($i = 0; $i < $matchupsPerRound; $i++) {
                TournamentRound::create([
                    'tournament_id' => $tournament->id,
                    'round_number' => $r,
                    'bracket' => TournamentBracketSide::Winners,
                ]);
            }
        }

        // Advance bye winners to round 2
        $byeWinners = $tournament->rounds()
            ->where('round_number', 1)
            ->whereNotNull('winner_entry_id')
            ->get();

        foreach ($byeWinners as $byeRound) {
            $this->fillNextSlot($tournament, $byeRound->round_number + 1, TournamentBracketSide::Winners, $byeRound->winner_entry_id);
        }
    }

    private function advanceSingleElim(Tournament $tournament, TournamentRound $round, TournamentEntry $winner, ?int $loserId): void
    {
        // Eliminate loser
        if ($loserId) {
            TournamentEntry::where('id', $loserId)->update(['status' => TournamentEntryStatus::Eliminated]);
        }

        $totalRounds = $this->getTotalRounds($tournament, TournamentBracketSide::Winners);

        // If this was the final round, tournament is complete
        if ($round->round_number >= $totalRounds) {
            $winner->update(['status' => TournamentEntryStatus::Winner]);
            $tournament->update(['status' => TournamentStatus::Completed]);
            return;
        }

        // Advance winner to next round
        $this->fillNextSlot($tournament, $round->round_number + 1, TournamentBracketSide::Winners, $winner->id);
    }

    // ─── Double Elimination ─────────────────────────────────

    private function generateDoubleElimination(Tournament $tournament, $entries): void
    {
        $count = $entries->count();
        $bracketSize = $this->nextPowerOfTwo($count);
        $winnersRounds = (int) ceil(log($bracketSize, 2));

        // Winners bracket — same as single elim
        $matchupsPerRound = $bracketSize / 2;

        for ($i = 0; $i < $matchupsPerRound; $i++) {
            $entry1 = $entries->get($i * 2);
            $entry2 = $entries->get($i * 2 + 1);

            $round = TournamentRound::create([
                'tournament_id' => $tournament->id,
                'round_number' => 1,
                'bracket' => TournamentBracketSide::Winners,
                'entry_1_id' => $entry1?->id,
                'entry_2_id' => $entry2?->id,
            ]);

            if ($entry1 && !$entry2) {
                $round->update(['winner_entry_id' => $entry1->id]);
            }
        }

        for ($r = 2; $r <= $winnersRounds; $r++) {
            $matchupsPerRound = $bracketSize / (int) pow(2, $r);
            for ($i = 0; $i < $matchupsPerRound; $i++) {
                TournamentRound::create([
                    'tournament_id' => $tournament->id,
                    'round_number' => $r,
                    'bracket' => TournamentBracketSide::Winners,
                ]);
            }
        }

        // Losers bracket — 2 * (winnersRounds - 1) rounds
        $losersRounds = 2 * ($winnersRounds - 1);
        for ($r = 1; $r <= $losersRounds; $r++) {
            // Losers bracket halves every 2 rounds
            $matchups = max(1, $bracketSize / (int) pow(2, (int) ceil($r / 2) + 1));
            for ($i = 0; $i < $matchups; $i++) {
                TournamentRound::create([
                    'tournament_id' => $tournament->id,
                    'round_number' => $r,
                    'bracket' => TournamentBracketSide::Losers,
                ]);
            }
        }

        // Grand Finals — 1 round
        TournamentRound::create([
            'tournament_id' => $tournament->id,
            'round_number' => 1,
            'bracket' => TournamentBracketSide::Finals,
        ]);

        // Advance bye winners
        $byeWinners = $tournament->rounds()
            ->where('round_number', 1)
            ->where('bracket', TournamentBracketSide::Winners)
            ->whereNotNull('winner_entry_id')
            ->get();

        foreach ($byeWinners as $byeRound) {
            $this->fillNextSlot($tournament, $byeRound->round_number + 1, TournamentBracketSide::Winners, $byeRound->winner_entry_id);
        }
    }

    private function advanceDoubleElim(Tournament $tournament, TournamentRound $round, TournamentEntry $winner, ?int $loserId): void
    {
        if ($round->bracket === TournamentBracketSide::Finals) {
            // Grand finals complete — winner takes tournament
            $winner->update(['status' => TournamentEntryStatus::Winner]);
            if ($loserId) {
                TournamentEntry::where('id', $loserId)->update(['status' => TournamentEntryStatus::Eliminated]);
            }
            $tournament->update(['status' => TournamentStatus::Completed]);
            return;
        }

        if ($round->bracket === TournamentBracketSide::Winners) {
            $totalWinnersRounds = $this->getTotalRounds($tournament, TournamentBracketSide::Winners);

            if ($round->round_number < $totalWinnersRounds) {
                // Advance winner in winners bracket
                $this->fillNextSlot($tournament, $round->round_number + 1, TournamentBracketSide::Winners, $winner->id);
            } else {
                // Winners bracket final winner goes to grand finals
                $this->fillNextSlot($tournament, 1, TournamentBracketSide::Finals, $winner->id);
            }

            // Drop loser to losers bracket
            if ($loserId) {
                $losersRoundNumber = ($round->round_number * 2) - 1;
                $this->fillNextSlot($tournament, $losersRoundNumber, TournamentBracketSide::Losers, $loserId);
            }
        }

        if ($round->bracket === TournamentBracketSide::Losers) {
            $totalLosersRounds = $this->getTotalRounds($tournament, TournamentBracketSide::Losers);

            if ($loserId) {
                TournamentEntry::where('id', $loserId)->update(['status' => TournamentEntryStatus::Eliminated]);
            }

            if ($round->round_number < $totalLosersRounds) {
                // Advance in losers bracket
                $this->fillNextSlot($tournament, $round->round_number + 1, TournamentBracketSide::Losers, $winner->id);
            } else {
                // Losers bracket champion goes to grand finals
                $this->fillNextSlot($tournament, 1, TournamentBracketSide::Finals, $winner->id);
            }
        }
    }

    // ─── Round Robin ────────────────────────────────────────

    private function generateRoundRobin(Tournament $tournament, $entries): void
    {
        $count = $entries->count();
        $roundNumber = 1;

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                TournamentRound::create([
                    'tournament_id' => $tournament->id,
                    'round_number' => $roundNumber,
                    'bracket' => TournamentBracketSide::Winners,
                    'entry_1_id' => $entries[$i]->id,
                    'entry_2_id' => $entries[$j]->id,
                ]);
            }
        }
    }

    private function advanceRoundRobin(Tournament $tournament): void
    {
        // Check if all matches have winners
        $pendingCount = $tournament->rounds()->whereNull('winner_entry_id')->count();

        if ($pendingCount === 0) {
            // Determine winner by most wins
            $standings = $this->getRoundRobinStandings($tournament);
            if (!empty($standings)) {
                TournamentEntry::where('id', $standings[0]['entry_id'])
                    ->update(['status' => TournamentEntryStatus::Winner]);
            }
            $tournament->update(['status' => TournamentStatus::Completed]);
        }
    }

    private function getRoundRobinStandings(Tournament $tournament): array
    {
        $entries = $tournament->entries()->with(['user:id,name', 'partner:id,name'])->get();
        $rounds = $tournament->rounds()->get();

        $stats = [];
        foreach ($entries as $entry) {
            $stats[$entry->id] = [
                'entry_id' => $entry->id,
                'name' => $entry->user->name . ($entry->partner ? ' & ' . $entry->partner->name : ''),
                'wins' => 0,
                'losses' => 0,
            ];
        }

        foreach ($rounds as $round) {
            if ($round->winner_entry_id) {
                $stats[$round->winner_entry_id]['wins']++;
                $loserId = $round->winner_entry_id === $round->entry_1_id ? $round->entry_2_id : $round->entry_1_id;
                if (isset($stats[$loserId])) {
                    $stats[$loserId]['losses']++;
                }
            }
        }

        $standings = array_values($stats);
        usort($standings, fn ($a, $b) => $b['wins'] <=> $a['wins']);

        return $standings;
    }

    // ─── Elimination Standings ──────────────────────────────

    private function getEliminationStandings(Tournament $tournament): array
    {
        $entries = $tournament->entries()
            ->with(['user:id,name', 'partner:id,name'])
            ->get()
            ->map(fn ($entry) => [
                'entry_id' => $entry->id,
                'name' => $entry->user->name . ($entry->partner ? ' & ' . $entry->partner->name : ''),
                'status' => $entry->status->value,
                'seed' => $entry->seed,
            ])
            ->sortBy(function ($entry) {
                return match ($entry['status']) {
                    'winner' => 0,
                    'registered', 'checked_in' => 1,
                    'eliminated' => 2,
                    default => 3,
                };
            })
            ->values()
            ->toArray();

        return $entries;
    }

    // ─── Helpers ────────────────────────────────────────────

    private function nextPowerOfTwo(int $n): int
    {
        $power = 1;
        while ($power < $n) {
            $power *= 2;
        }
        return $power;
    }

    private function getTotalRounds(Tournament $tournament, TournamentBracketSide $bracket): int
    {
        return $tournament->rounds()
            ->where('bracket', $bracket)
            ->max('round_number') ?? 0;
    }

    private function fillNextSlot(Tournament $tournament, int $roundNumber, TournamentBracketSide $bracket, int $entryId): void
    {
        // Find first round in the target that has an empty slot
        $targetRound = $tournament->rounds()
            ->where('round_number', $roundNumber)
            ->where('bracket', $bracket)
            ->where(function ($q) {
                $q->whereNull('entry_1_id')->orWhereNull('entry_2_id');
            })
            ->orderBy('id')
            ->first();

        if (!$targetRound) {
            return;
        }

        if ($targetRound->entry_1_id === null) {
            $targetRound->update(['entry_1_id' => $entryId]);
        } else {
            $targetRound->update(['entry_2_id' => $entryId]);
        }
    }
}
