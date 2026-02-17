<?php

namespace App\Services;

use App\Enums\MatchFormat;
use App\Enums\MatchStatus;
use App\Enums\XpSourceType;
use App\Models\Badge;
use App\Models\GameMatch;
use App\Models\MatchPlayer;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserXpEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BadgeService
{
    private const MATCH_CRITERIA_TYPES = [
        'wins_total',
        'win_streak',
        'shutouts',
        'comebacks',
        'matches_played',
        'session_matches',
        'h2h_matches',
        'all_partners_in_group',
        'partner_wins',
        'unique_partners_won',
        'pickle_rick',
        'sandwich_pattern',
    ];

    public function __construct(
        private XpService $xpService,
    ) {}

    /**
     * Check all match-triggered badges for every player in a match.
     *
     * @return array<int, list<array{badge_id: int, name: string, tier: string, xp_reward: int}>>
     */
    public function checkAfterMatch(GameMatch $match): array
    {
        $match->loadMissing('players');

        $matchBadges = Badge::whereIn('criteria_type', self::MATCH_CRITERIA_TYPES)->get();
        $results = [];

        foreach ($match->players as $matchPlayer) {
            $user = User::find($matchPlayer->user_id);
            $earnedBadgeIds = $user->userBadges()->pluck('badge_id')->toArray();

            $newBadges = [];

            foreach ($matchBadges as $badge) {
                if (in_array($badge->id, $earnedBadgeIds)) {
                    continue;
                }

                $currentValue = $this->computeCriteriaValue($badge->criteria_type, $user, $match);

                if ($currentValue >= $badge->criteria_value) {
                    $this->awardBadge($user, $badge);
                    $newBadges[] = [
                        'badge_id' => $badge->id,
                        'name' => $badge->name,
                        'slug' => $badge->slug,
                        'tier' => $badge->tier->value,
                        'xp_reward' => $badge->xp_reward,
                    ];
                }
            }

            if (!empty($newBadges)) {
                $results[$user->id] = $newBadges;
            }
        }

        return $results;
    }

    /**
     * Check group-join badges (groups_joined criteria).
     */
    public function checkAfterGroupJoin(User $user): array
    {
        $badges = Badge::where('criteria_type', 'groups_joined')->get();
        $earnedBadgeIds = $user->userBadges()->pluck('badge_id')->toArray();
        $newBadges = [];

        $groupCount = $user->groups()->count();

        foreach ($badges as $badge) {
            if (in_array($badge->id, $earnedBadgeIds)) {
                continue;
            }

            if ($groupCount >= $badge->criteria_value) {
                $this->awardBadge($user, $badge);
                $newBadges[] = [
                    'badge_id' => $badge->id,
                    'name' => $badge->name,
                    'slug' => $badge->slug,
                    'tier' => $badge->tier->value,
                    'xp_reward' => $badge->xp_reward,
                ];
            }
        }

        return $newBadges;
    }

    /**
     * Compute progress for all badges for the badge collection page.
     *
     * @return list<array{id: int, slug: string, name: string, description: string, category: string, tier: string, icon: ?string, is_secret: bool, earned: bool, earned_at: ?string, is_pinned: bool, current: int, target: int}>
     */
    public function computeProgress(User $user): array
    {
        $allBadges = Badge::orderBy('category')->orderBy('criteria_type')->orderBy('criteria_value')->get();
        $earnedBadges = $user->userBadges()->with('badge')->get()->keyBy('badge_id');

        $result = [];

        foreach ($allBadges as $badge) {
            $earned = $earnedBadges->has($badge->id);
            $userBadge = $earnedBadges->get($badge->id);

            // Secret badges: only show if earned
            if ($badge->is_secret && !$earned) {
                $result[] = [
                    'id' => $badge->id,
                    'slug' => $badge->slug,
                    'name' => '???',
                    'description' => 'This is a secret badge. Keep playing to discover it!',
                    'category' => $badge->category->value,
                    'tier' => $badge->tier->value,
                    'icon' => null,
                    'is_secret' => true,
                    'earned' => false,
                    'earned_at' => null,
                    'is_pinned' => false,
                    'current' => 0,
                    'target' => $badge->criteria_value,
                ];
                continue;
            }

            $current = $earned
                ? $badge->criteria_value
                : $this->computeCriteriaValue($badge->criteria_type, $user);

            $result[] = [
                'id' => $badge->id,
                'slug' => $badge->slug,
                'name' => $badge->name,
                'description' => $badge->description,
                'category' => $badge->category->value,
                'tier' => $badge->tier->value,
                'icon' => $badge->icon,
                'is_secret' => $badge->is_secret,
                'earned' => $earned,
                'earned_at' => $userBadge?->earned_at?->toISOString(),
                'is_pinned' => (bool) $userBadge?->is_pinned,
                'current' => min($current, $badge->criteria_value),
                'target' => $badge->criteria_value,
            ];
        }

        return $result;
    }

    /**
     * Award a badge to a user (create record + award XP).
     */
    public function awardBadge(User $user, Badge $badge): void
    {
        UserBadge::create([
            'user_id' => $user->id,
            'badge_id' => $badge->id,
            'earned_at' => now(),
            'is_pinned' => false,
        ]);

        if ($badge->xp_reward > 0) {
            UserXpEvent::create([
                'user_id' => $user->id,
                'source_type' => XpSourceType::Badge,
                'source_id' => $badge->id,
                'xp_amount' => $badge->xp_reward,
                'description' => "Badge earned: {$badge->name}",
            ]);

            $this->xpService->applyXp($user, $badge->xp_reward);
        }
    }

    /**
     * Toggle pin on a badge. Enforces max 3 pinned.
     *
     * @return array{pinned: bool, error: ?string}
     */
    public function pinBadge(User $user, Badge $badge): array
    {
        $userBadge = UserBadge::where('user_id', $user->id)
            ->where('badge_id', $badge->id)
            ->first();

        if (!$userBadge) {
            return ['pinned' => false, 'error' => 'Badge not earned'];
        }

        if ($userBadge->is_pinned) {
            $userBadge->update(['is_pinned' => false]);
            return ['pinned' => false, 'error' => null];
        }

        $pinnedCount = UserBadge::where('user_id', $user->id)
            ->where('is_pinned', true)
            ->count();

        if ($pinnedCount >= 3) {
            return ['pinned' => false, 'error' => 'Maximum 3 badges can be pinned'];
        }

        $userBadge->update(['is_pinned' => true]);
        return ['pinned' => true, 'error' => null];
    }

    /**
     * Get pinned badges for a user.
     */
    public function getPinnedBadges(User $user): Collection
    {
        return $user->badges()
            ->wherePivot('is_pinned', true)
            ->get()
            ->map(fn (Badge $badge) => [
                'id' => $badge->id,
                'slug' => $badge->slug,
                'name' => $badge->name,
                'tier' => $badge->tier->value,
                'icon' => $badge->icon,
            ]);
    }

    /**
     * Compute the current value for a given criteria type.
     */
    private function computeCriteriaValue(string $criteriaType, User $user, ?GameMatch $match = null): int
    {
        return match ($criteriaType) {
            'wins_total' => $this->countWinsTotal($user),
            'win_streak' => $this->maxWinStreak($user),
            'shutouts' => $this->countShutouts($user),
            'comebacks' => $this->countComebacks($user),
            'matches_played' => $this->countMatchesPlayed($user),
            'session_matches' => $match ? $this->countSessionMatches($user, $match) : 0,
            'groups_joined' => $user->groups()->count(),
            'h2h_matches' => $this->maxH2hMatches($user),
            'all_partners_in_group' => $match ? $this->checkAllPartnersInGroup($user, $match) : 0,
            'partner_wins' => $this->maxPartnerWins($user),
            'unique_partners_won' => $this->countUniquePartnersWon($user),
            'pickle_rick' => $this->checkPickleRick($user),
            'sandwich_pattern' => $this->checkSandwichPattern($user),
            'last_to_first' => 0, // Skipped until position tracking exists
            default => 0,
        };
    }

    private function countWinsTotal(User $user): int
    {
        return DB::table('match_players')
            ->join('matches', 'matches.id', '=', 'match_players.match_id')
            ->where('match_players.user_id', $user->id)
            ->where('matches.status', MatchStatus::Completed->value)
            ->whereColumn('match_players.team', 'matches.winning_team')
            ->count();
    }

    private function maxWinStreak(User $user): int
    {
        $matches = $this->getRecentMatchResults($user);

        $maxStreak = 0;
        $currentStreak = 0;

        foreach ($matches as $won) {
            if ($won) {
                $currentStreak++;
                $maxStreak = max($maxStreak, $currentStreak);
            } else {
                $currentStreak = 0;
            }
        }

        return $maxStreak;
    }

    private function countShutouts(User $user): int
    {
        return DB::table('match_players')
            ->join('matches', 'matches.id', '=', 'match_players.match_id')
            ->where('match_players.user_id', $user->id)
            ->where('matches.status', MatchStatus::Completed->value)
            ->whereColumn('match_players.team', 'matches.winning_team')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('matches.winning_team', 1)->where('matches.team_2_score', 0);
                })->orWhere(function ($q2) {
                    $q2->where('matches.winning_team', 2)->where('matches.team_1_score', 0);
                });
            })
            ->count();
    }

    private function countComebacks(User $user): int
    {
        // "Comeback" = winning a close match where loser scored within 2 of winner
        return DB::table('match_players')
            ->join('matches', 'matches.id', '=', 'match_players.match_id')
            ->where('match_players.user_id', $user->id)
            ->where('matches.status', MatchStatus::Completed->value)
            ->whereColumn('match_players.team', 'matches.winning_team')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('matches.winning_team', 1)
                        ->whereRaw('matches.team_2_score >= matches.team_1_score - 2')
                        ->where('matches.team_2_score', '>', 0);
                })->orWhere(function ($q2) {
                    $q2->where('matches.winning_team', 2)
                        ->whereRaw('matches.team_1_score >= matches.team_2_score - 2')
                        ->where('matches.team_1_score', '>', 0);
                });
            })
            ->count();
    }

    private function countMatchesPlayed(User $user): int
    {
        return DB::table('match_players')
            ->join('matches', 'matches.id', '=', 'match_players.match_id')
            ->where('match_players.user_id', $user->id)
            ->where('matches.status', MatchStatus::Completed->value)
            ->count();
    }

    private function countSessionMatches(User $user, GameMatch $match): int
    {
        if (!$match->session_id) {
            return 0;
        }

        return DB::table('match_players')
            ->join('matches', 'matches.id', '=', 'match_players.match_id')
            ->where('match_players.user_id', $user->id)
            ->where('matches.session_id', $match->session_id)
            ->where('matches.status', MatchStatus::Completed->value)
            ->count();
    }

    private function maxH2hMatches(User $user): int
    {
        // Count matches against each opponent (on opposite teams)
        $counts = DB::table('match_players as mp1')
            ->join('match_players as mp2', function ($join) {
                $join->on('mp1.match_id', '=', 'mp2.match_id')
                    ->whereColumn('mp1.team', '!=', 'mp2.team');
            })
            ->join('matches', function ($join) {
                $join->on('matches.id', '=', 'mp1.match_id')
                    ->where('matches.status', '=', MatchStatus::Completed->value);
            })
            ->where('mp1.user_id', $user->id)
            ->groupBy('mp2.user_id')
            ->select(DB::raw('COUNT(*) as cnt'))
            ->pluck('cnt');

        return $counts->isEmpty() ? 0 : (int) $counts->max();
    }

    private function checkAllPartnersInGroup(User $user, GameMatch $match): int
    {
        if (!$match->group_id) {
            return 0;
        }

        $groupMemberIds = DB::table('group_user')
            ->where('group_id', $match->group_id)
            ->where('user_id', '!=', $user->id)
            ->pluck('user_id');

        if ($groupMemberIds->isEmpty()) {
            return 0;
        }

        // Find all users who have been on the same team as this user in doubles
        $partnerIds = DB::table('match_players as mp1')
            ->join('match_players as mp2', function ($join) {
                $join->on('mp1.match_id', '=', 'mp2.match_id')
                    ->whereColumn('mp1.team', '=', 'mp2.team')
                    ->whereColumn('mp1.user_id', '!=', 'mp2.user_id');
            })
            ->join('matches', function ($join) use ($match) {
                $join->on('matches.id', '=', 'mp1.match_id')
                    ->where('matches.group_id', '=', $match->group_id)
                    ->where('matches.status', '=', MatchStatus::Completed->value)
                    ->where('matches.format', '=', MatchFormat::Doubles->value);
            })
            ->where('mp1.user_id', $user->id)
            ->distinct()
            ->pluck('mp2.user_id');

        // Check if all group members have been partnered with
        $allPartnered = $groupMemberIds->every(fn ($id) => $partnerIds->contains($id));

        return $allPartnered ? 1 : 0;
    }

    private function maxPartnerWins(User $user): int
    {
        $counts = DB::table('match_players as mp1')
            ->join('match_players as mp2', function ($join) {
                $join->on('mp1.match_id', '=', 'mp2.match_id')
                    ->whereColumn('mp1.team', '=', 'mp2.team')
                    ->whereColumn('mp1.user_id', '!=', 'mp2.user_id');
            })
            ->join('matches', function ($join) {
                $join->on('matches.id', '=', 'mp1.match_id')
                    ->where('matches.status', '=', MatchStatus::Completed->value);
            })
            ->where('mp1.user_id', $user->id)
            ->whereColumn('mp1.team', 'matches.winning_team')
            ->groupBy('mp2.user_id')
            ->select(DB::raw('COUNT(*) as cnt'))
            ->pluck('cnt');

        return $counts->isEmpty() ? 0 : (int) $counts->max();
    }

    private function countUniquePartnersWon(User $user): int
    {
        return (int) DB::table('match_players as mp1')
            ->join('match_players as mp2', function ($join) {
                $join->on('mp1.match_id', '=', 'mp2.match_id')
                    ->whereColumn('mp1.team', '=', 'mp2.team')
                    ->whereColumn('mp1.user_id', '!=', 'mp2.user_id');
            })
            ->join('matches', function ($join) {
                $join->on('matches.id', '=', 'mp1.match_id')
                    ->where('matches.status', '=', MatchStatus::Completed->value);
            })
            ->where('mp1.user_id', $user->id)
            ->whereColumn('mp1.team', 'matches.winning_team')
            ->distinct('mp2.user_id')
            ->count('mp2.user_id');
    }

    private function checkPickleRick(User $user): int
    {
        // Pattern: 3+ losses then 3+ wins in recent matches
        $results = $this->getRecentMatchResults($user, 50);
        $resultsArr = $results->toArray();

        // Scan for the pattern: at least 3 losses followed by at least 3 wins
        for ($i = 0; $i <= count($resultsArr) - 6; $i++) {
            $losses = 0;
            for ($j = $i; $j < count($resultsArr); $j++) {
                if (!$resultsArr[$j]) {
                    $losses++;
                } else {
                    break;
                }
            }

            if ($losses >= 3) {
                $wins = 0;
                for ($k = $i + $losses; $k < count($resultsArr); $k++) {
                    if ($resultsArr[$k]) {
                        $wins++;
                    } else {
                        break;
                    }
                }

                if ($wins >= 3) {
                    return 1;
                }
            }
        }

        return 0;
    }

    private function checkSandwichPattern(User $user): int
    {
        // Pattern: W,L,W,L,W in last 5 matches
        $results = $this->getRecentMatchResults($user, 5);

        if ($results->count() < 5) {
            return 0;
        }

        $last5 = $results->slice(-5)->values()->toArray();

        // W, L, W, L, W
        if ($last5[0] && !$last5[1] && $last5[2] && !$last5[3] && $last5[4]) {
            return 1;
        }

        return 0;
    }

    /**
     * Get recent match results as a collection of booleans (true = win).
     * Ordered by played_at ASC (oldest first).
     */
    private function getRecentMatchResults(User $user, int $limit = 1000): Collection
    {
        return DB::table('match_players')
            ->join('matches', 'matches.id', '=', 'match_players.match_id')
            ->where('match_players.user_id', $user->id)
            ->where('matches.status', MatchStatus::Completed->value)
            ->whereNotNull('matches.winning_team')
            ->orderBy('matches.played_at', 'asc')
            ->limit($limit)
            ->select('match_players.team', 'matches.winning_team')
            ->get()
            ->map(fn ($row) => $row->team === $row->winning_team);
    }
}
