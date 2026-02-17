<?php

namespace App\Services;

use App\Enums\XpSourceType;
use App\Models\GameMatch;
use App\Models\MatchPlayer;
use App\Models\User;
use App\Models\UserLevel;
use App\Models\UserXpEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class XpService
{
    private const XP_PLAY = 20;
    private const XP_WIN = 10;
    private const XP_SHUTOUT = 25;
    private const XP_FIRST_MATCH_OF_DAY = 15;

    private const LEVEL_NAMES = [
        1 => 'Beginner',
        2 => 'Novice',
        3 => 'Rally Ready',
        4 => 'Court Regular',
        5 => 'Kitchen King',
        6 => 'Dink Master',
        7 => 'Net Ninja',
        8 => 'Court Commander',
        9 => 'Grand Dinkster',
        10 => 'Pickle Legend',
    ];

    /**
     * Award XP to all players in a completed match.
     *
     * @return array<int, array{xp_gained: int, leveled_up: bool, new_level: int}>
     */
    public function awardMatchXp(GameMatch $match): array
    {
        $match->loadMissing('players');

        $winningTeam = $match->winning_team;
        $loserScore = $winningTeam === 1 ? $match->team_2_score : $match->team_1_score;
        $isShutout = $loserScore === 0;

        $results = [];

        foreach ($match->players as $matchPlayer) {
            $userId = $matchPlayer->user_id;
            $totalXp = 0;

            // +20 XP for playing
            $this->createXpEvent($userId, $match->id, self::XP_PLAY, 'Played a match');
            $totalXp += self::XP_PLAY;

            // +10 XP for winning
            if ($matchPlayer->team === $winningTeam) {
                $this->createXpEvent($userId, $match->id, self::XP_WIN, 'Won the match');
                $totalXp += self::XP_WIN;

                // +25 XP for shutout win
                if ($isShutout) {
                    $this->createXpEvent($userId, $match->id, self::XP_SHUTOUT, 'Shutout victory');
                    $totalXp += self::XP_SHUTOUT;
                }
            }

            // +15 XP for first match of the day
            if ($this->isFirstMatchToday($userId, $match->id)) {
                $this->createXpEvent($userId, $match->id, self::XP_FIRST_MATCH_OF_DAY, 'First match of the day');
                $totalXp += self::XP_FIRST_MATCH_OF_DAY;
            }

            $levelResult = $this->applyXp(User::find($userId), $totalXp);
            $results[$userId] = [
                'xp_gained' => $totalXp,
                'leveled_up' => $levelResult['leveled_up'],
                'new_level' => $levelResult['new_level'],
            ];
        }

        return $results;
    }

    /**
     * Apply XP to a user's level, handling level-ups.
     *
     * @return array{leveled_up: bool, new_level: int}
     */
    public function applyXp(User $user, int $amount): array
    {
        $level = $user->level ?? UserLevel::create([
            'user_id' => $user->id,
            'current_level' => 1,
            'total_xp' => 0,
        ]);

        $previousLevel = $level->current_level;
        $level->total_xp += $amount;

        // Calculate XP within current level to check for level-ups
        // We need to figure out how much XP the user has "within" their current level
        // total_xp is cumulative, so we need to subtract XP for all previous levels
        $xpInCurrentLevel = $this->getXpInCurrentLevel($level);

        while ($xpInCurrentLevel >= $level->xpForNextLevel()) {
            $xpInCurrentLevel -= $level->xpForNextLevel();
            $level->current_level++;
        }

        $level->save();

        return [
            'leveled_up' => $level->current_level > $previousLevel,
            'new_level' => $level->current_level,
        ];
    }

    public static function levelName(int $level): string
    {
        if ($level >= 10) {
            return self::LEVEL_NAMES[10];
        }

        return self::LEVEL_NAMES[$level] ?? self::LEVEL_NAMES[1];
    }

    /**
     * Get XP progress within the current level.
     *
     * @return array{current: int, needed: int}
     */
    public static function xpProgress(UserLevel $level): array
    {
        $xpForPreviousLevels = self::totalXpForLevel($level->current_level);
        $current = $level->total_xp - $xpForPreviousLevels;
        $needed = $level->xpForNextLevel();

        return [
            'current' => max(0, $current),
            'needed' => $needed,
        ];
    }

    /**
     * Calculate total XP required to reach a given level (sum of all previous thresholds).
     */
    private static function totalXpForLevel(int $level): int
    {
        $thresholds = [
            1 => 100,
            2 => 200,
            3 => 350,
            4 => 500,
            5 => 700,
            6 => 1000,
            7 => 1400,
            8 => 2000,
            9 => 3000,
        ];

        $total = 0;
        for ($i = 1; $i < $level; $i++) {
            if ($i <= 9) {
                $total += $thresholds[$i] ?? 100;
            } else {
                $total += 3000 + (($i - 9) * 500);
            }
        }

        return $total;
    }

    private function getXpInCurrentLevel(UserLevel $level): int
    {
        $xpForPreviousLevels = self::totalXpForLevel($level->current_level);
        return $level->total_xp - $xpForPreviousLevels;
    }

    private function createXpEvent(int $userId, int $matchId, int $amount, string $description): void
    {
        UserXpEvent::create([
            'user_id' => $userId,
            'source_type' => XpSourceType::Match,
            'source_id' => $matchId,
            'xp_amount' => $amount,
            'description' => $description,
        ]);
    }

    private function isFirstMatchToday(int $userId, int $currentMatchId): bool
    {
        $today = Carbon::today();

        return !MatchPlayer::where('user_id', $userId)
            ->whereHas('match', function ($query) use ($today, $currentMatchId) {
                $query->where('id', '!=', $currentMatchId)
                    ->where('played_at', '>=', $today);
            })
            ->exists();
    }
}
