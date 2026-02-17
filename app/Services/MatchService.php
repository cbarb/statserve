<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Enums\SessionStatus;
use App\Exceptions\MatchLimitExceededException;
use App\Models\GameMatch;
use App\Models\GameSession;
use App\Models\Group;
use App\Models\MatchPlayer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MatchService
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private XpService $xpService,
        private BadgeService $badgeService,
    ) {}
    public function createSession(Group $group, User $user, array $data): GameSession
    {
        return GameSession::create([
            'group_id' => $group->id,
            'started_by' => $user->id,
            'format' => $data['format'],
            'team_mode' => $data['team_mode'],
            'player_ids' => $data['player_ids'],
            'status' => SessionStatus::Active,
            'started_at' => now(),
        ]);
    }

    /**
     * @return array{match: GameMatch, xp_results: array, badge_results: array}
     */
    public function createMatch(GameSession $session, User $user, array $data): array
    {
        $group = $session->group;
        $access = $this->subscriptionService->canLogMatch($user, $group);
        if (!$access['allowed']) {
            throw new MatchLimitExceededException($access['reason']);
        }

        $match = DB::transaction(function () use ($session, $user, $data) {
            $winningTeam = $data['team_1_score'] > $data['team_2_score'] ? 1 : 2;

            $match = GameMatch::create([
                'group_id' => $session->group_id,
                'session_id' => $session->id,
                'format' => $session->format,
                'status' => MatchStatus::Completed,
                'team_1_score' => $data['team_1_score'],
                'team_2_score' => $data['team_2_score'],
                'winning_team' => $winningTeam,
                'logged_by' => $user->id,
                'played_at' => now(),
            ]);

            foreach ($data['players'] as $player) {
                MatchPlayer::create([
                    'match_id' => $match->id,
                    'user_id' => $player['user_id'],
                    'team' => $player['team'],
                    'position' => $player['position'],
                ]);
            }

            return $match;
        });

        // Award XP after the transaction (non-critical)
        $xpResults = $this->xpService->awardMatchXp($match);

        // Check badge criteria after match
        $badgeResults = $this->badgeService->checkAfterMatch($match);

        return ['match' => $match, 'xp_results' => $xpResults, 'badge_results' => $badgeResults];
    }

    public function endSession(GameSession $session): void
    {
        $session->update([
            'status' => SessionStatus::Completed,
            'finished_at' => now(),
        ]);
    }
}
