<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Exceptions\MatchLimitExceededException;
use App\Http\Requests\CreateSessionRequest;
use App\Http\Requests\StoreMatchRequest;
use App\Models\GameSession;
use App\Models\Group;
use App\Models\User;
use App\Services\MatchService;
use App\Services\RotationService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class PlayController extends Controller
{
    public function __construct(
        private MatchService $matchService,
        private RotationService $rotationService,
        private SubscriptionService $subscriptionService,
    ) {}

    public function index(Request $request): Response
    {
        $groups = $request->user()
            ->groups()
            ->withCount('members')
            ->get()
            ->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'slug' => $group->slug,
                'members_count' => $group->members_count,
            ]);

        return Inertia::render('Play/SelectGroup', [
            'groups' => $groups,
        ]);
    }

    public function setup(Request $request, Group $group): Response
    {
        $this->authorize('view', $group);

        $members = $group->members()
            ->select('users.id', 'users.name', 'users.avatar_url', 'users.dupr_id')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'avatar_url' => $m->avatar_url,
                'dupr_id' => $m->dupr_id,
            ]);

        $activeSession = $group->sessions()
            ->where('status', SessionStatus::Active)
            ->where('started_by', $request->user()->id)
            ->first();

        return Inertia::render('Play/Setup', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'slug' => $group->slug,
            ],
            'members' => $members,
            'activeSession' => $activeSession ? [
                'id' => $activeSession->id,
                'format' => $activeSession->format->value,
                'team_mode' => $activeSession->team_mode->value,
                'player_ids' => $activeSession->player_ids,
                'started_at' => $activeSession->started_at,
            ] : null,
        ]);
    }

    public function createSession(CreateSessionRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('view', $group);

        $session = $this->matchService->createSession(
            group: $group,
            user: $request->user(),
            data: $request->validated(),
        );

        return Redirect::route('play.session', [$group, $session]);
    }

    public function session(Request $request, Group $group, GameSession $session): Response
    {
        $this->authorize('view', $group);
        abort_unless($session->group_id === $group->id, 404);

        $session->load(['matches.players.user']);

        $players = User::whereIn('id', $session->player_ids)
            ->select('id', 'name', 'avatar_url', 'dupr_id')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'avatar_url' => $u->avatar_url,
                'dupr_id' => $u->dupr_id,
            ]);

        $nextAssignment = $session->status === SessionStatus::Active
            ? $this->rotationService->getNextAssignment($session)
            : null;

        $matches = $session->matches
            ->sortByDesc('played_at')
            ->values()
            ->map(fn ($m) => [
                'id' => $m->id,
                'team_1_score' => $m->team_1_score,
                'team_2_score' => $m->team_2_score,
                'winning_team' => $m->winning_team,
                'played_at' => $m->played_at,
                'players' => $m->players->map(fn ($p) => [
                    'user_id' => $p->user_id,
                    'name' => $p->user->name,
                    'team' => $p->team,
                    'position' => $p->position->value,
                ]),
            ]);

        $canLogMatch = $this->subscriptionService->canLogMatch($request->user(), $group);
        $hasBoost = $this->subscriptionService->hasActiveBoost($group);
        $weeklyMatchCount = $hasBoost ? 0 : $this->subscriptionService->getWeeklyMatchCount($group);

        return Inertia::render('Play/Session', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'slug' => $group->slug,
            ],
            'session' => [
                'id' => $session->id,
                'format' => $session->format->value,
                'team_mode' => $session->team_mode->value,
                'status' => $session->status->value,
                'player_ids' => $session->player_ids,
                'started_at' => $session->started_at,
            ],
            'players' => $players,
            'matches' => $matches,
            'nextAssignment' => $nextAssignment,
            'canLogMatch' => $canLogMatch['allowed'],
            'hasBoost' => $hasBoost,
            'weeklyMatchCount' => $weeklyMatchCount,
        ]);
    }

    public function storeMatch(StoreMatchRequest $request, Group $group, GameSession $session): RedirectResponse
    {
        $this->authorize('view', $group);
        abort_unless($session->group_id === $group->id, 404);
        abort_unless($session->status === SessionStatus::Active, 422);

        try {
            $result = $this->matchService->createMatch(
                session: $session,
                user: $request->user(),
                data: $request->validated(),
            );
        } catch (MatchLimitExceededException $e) {
            return Redirect::route('play.session', [$group, $session])
                ->with('error', $e->reason);
        }

        return Redirect::route('play.session', [$group, $session])
            ->with('xp_awarded', $result['xp_results'])
            ->with('badges_earned', $result['badge_results']);
    }

    public function endSession(Request $request, Group $group, GameSession $session): RedirectResponse
    {
        $this->authorize('view', $group);
        abort_unless($session->group_id === $group->id, 404);
        abort_unless($session->status === SessionStatus::Active, 422);

        $this->matchService->endSession($session);

        return Redirect::route('play.session', [$group, $session]);
    }
}
