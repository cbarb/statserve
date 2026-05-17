<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use App\Models\UserLevel;
use App\Services\StatsService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatsController extends Controller
{
    public function __construct(
        private StatsService $stats,
        private SubscriptionService $subscriptions,
    ) {}

    public function groupStats(Request $request, Group $group): Response
    {
        $this->authorize('view', $group);

        $canViewAllTime = $this->canViewAllTime($request->user(), $group);
        $range = $this->resolveRange($request->query('range', 'all'), $canViewAllTime);
        [$from, $to] = $this->parseRange($range);

        $leaderboard = $this->stats->groupLeaderboard($group->id, $from, $to);
        $members = $this->getGroupMembersMap($group);

        // Attach names and levels to leaderboard
        $userIds = array_column($leaderboard, 'user_id');
        $levels = UserLevel::whereIn('user_id', $userIds)
            ->pluck('current_level', 'user_id')
            ->toArray();

        foreach ($leaderboard as &$row) {
            $member = $members[$row['user_id']] ?? null;
            $row['name'] = $member['name'] ?? 'Unknown';
            $row['dupr_id'] = $member['dupr_id'] ?? null;
            $row['level'] = $levels[$row['user_id']] ?? 1;
        }

        return Inertia::render('Groups/Stats/Leaderboard', [
            'group' => $this->groupProps($group),
            'leaderboard' => $leaderboard,
            'range' => $range,
            'canViewAllTime' => $canViewAllTime,
        ]);
    }

    public function headToHead(Request $request, Group $group): Response
    {
        $this->authorize('view', $group);

        $canViewAllTime = $this->canViewAllTime($request->user(), $group);
        $range = $this->resolveRange($request->query('range', 'all'), $canViewAllTime);
        [$from, $to] = $this->parseRange($range);

        $members = $group->members()
            ->select('users.id', 'users.name', 'users.dupr_id')
            ->get()
            ->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'dupr_id' => $m->dupr_id]);

        $h2h = null;
        $player1Id = $request->query('player1');
        $player2Id = $request->query('player2');

        if ($player1Id && $player2Id && $player1Id !== $player2Id) {
            $memberIds = $group->members()->pluck('users.id');
            if ($memberIds->contains((int) $player1Id) && $memberIds->contains((int) $player2Id)) {
                $h2h = $this->stats->headToHead((int) $player1Id, (int) $player2Id, $group->id, $from, $to);
            }
        }

        return Inertia::render('Groups/Stats/HeadToHead', [
            'group' => $this->groupProps($group),
            'members' => $members,
            'h2h' => $h2h,
            'player1' => $player1Id ? (int) $player1Id : null,
            'player2' => $player2Id ? (int) $player2Id : null,
            'range' => $range,
            'canViewAllTime' => $canViewAllTime,
        ]);
    }

    public function partnerships(Request $request, Group $group): Response
    {
        $this->authorize('view', $group);

        $canViewAllTime = $this->canViewAllTime($request->user(), $group);
        $range = $this->resolveRange($request->query('range', 'all'), $canViewAllTime);
        [$from, $to] = $this->parseRange($range);

        $partnerships = $this->stats->partnerships($group->id, $from, $to);
        $members = $this->getGroupMembersMap($group);

        foreach ($partnerships as &$pair) {
            $m1 = $members[$pair['player1_id']] ?? null;
            $m2 = $members[$pair['player2_id']] ?? null;
            $pair['player1_name'] = $m1['name'] ?? 'Unknown';
            $pair['player1_dupr_id'] = $m1['dupr_id'] ?? null;
            $pair['player2_name'] = $m2['name'] ?? 'Unknown';
            $pair['player2_dupr_id'] = $m2['dupr_id'] ?? null;
        }

        return Inertia::render('Groups/Stats/Partnerships', [
            'group' => $this->groupProps($group),
            'partnerships' => $partnerships,
            'range' => $range,
            'canViewAllTime' => $canViewAllTime,
        ]);
    }

    public function playerDetail(Request $request, Group $group, User $user): Response
    {
        $this->authorize('view', $group);

        $canViewAllTime = $this->canViewAllTime($request->user(), $group);
        $range = $this->resolveRange($request->query('range', 'all'), $canViewAllTime);
        [$from, $to] = $this->parseRange($range);

        $stats = $this->stats->playerStats($user->id, $group->id, $from, $to);
        $bestPartner = $this->stats->bestPartner($user->id, $group->id, $from, $to);
        $members = $this->getGroupMembersMap($group);

        if ($bestPartner) {
            $bp = $members[$bestPartner['partner_id']] ?? null;
            $bestPartner['partner_name'] = $bp['name'] ?? 'Unknown';
            $bestPartner['partner_dupr_id'] = $bp['dupr_id'] ?? null;
        }

        return Inertia::render('Groups/Stats/PlayerDetail', [
            'group' => $this->groupProps($group),
            'player' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
                'dupr_id' => $user->dupr_id,
            ],
            'stats' => $stats,
            'bestPartner' => $bestPartner,
            'range' => $range,
            'canViewAllTime' => $canViewAllTime,
        ]);
    }

    private function canViewAllTime(User $user, Group $group): bool
    {
        return $this->subscriptions->isProSubscriber($user)
            || $this->subscriptions->hasActiveBoost($group);
    }

    private function resolveRange(string $range, bool $canViewAllTime): string
    {
        if ($range === 'all' && !$canViewAllTime) {
            return 'monthly';
        }

        return $range;
    }

    private function parseRange(string $range): array
    {
        return match ($range) {
            'daily' => [Carbon::today(), Carbon::now()],
            'weekly' => [Carbon::now()->startOfWeek(), Carbon::now()],
            'monthly' => [Carbon::now()->startOfMonth(), Carbon::now()],
            default => [null, null],
        };
    }

    private function groupProps(Group $group): array
    {
        return [
            'id' => $group->id,
            'name' => $group->name,
            'slug' => $group->slug,
        ];
    }

    private function getGroupMembersMap(Group $group): array
    {
        return $group->members()
            ->select('users.id', 'users.name', 'users.dupr_id')
            ->get()
            ->keyBy('id')
            ->map(fn ($m) => ['name' => $m->name, 'dupr_id' => $m->dupr_id])
            ->toArray();
    }
}
