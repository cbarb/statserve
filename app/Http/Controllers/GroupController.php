<?php

namespace App\Http\Controllers;

use App\Enums\GroupRole;
use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Models\Group;
use App\Services\StatsService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
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
                'role' => $group->pivot->role,
            ]);

        return Inertia::render('Groups/Index', [
            'groups' => $groups,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Groups/Create', [
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $group = Group::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        $group->members()->attach($request->user()->id, [
            'role' => GroupRole::Owner->value,
            'joined_at' => now(),
        ]);

        return Redirect::route('groups.show', $group);
    }

    public function show(Request $request, Group $group): Response
    {
        $this->authorize('view', $group);

        $members = $group->members()
            ->select('users.id', 'users.name', 'users.email', 'users.avatar_url')
            ->get()
            ->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'avatar_url' => $member->avatar_url,
                'role' => $member->pivot->role,
                'joined_at' => $member->pivot->joined_at,
            ]);

        $userRole = $group->getMemberRole($request->user());

        $statsService = app(StatsService::class);
        $leaderboard = $statsService->groupLeaderboard($group->id);
        $membersMap = $group->members()->pluck('users.name', 'users.id')->toArray();

        // Attach names and take top 5 for preview
        foreach ($leaderboard as &$row) {
            $row['name'] = $membersMap[$row['user_id']] ?? 'Unknown';
        }
        $leaderboardPreview = array_slice($leaderboard, 0, 5);

        $myStats = $statsService->playerStats($request->user()->id, $group->id);

        $subscriptionService = app(SubscriptionService::class);
        $hasBoost = $subscriptionService->hasActiveBoost($group);
        $weeklyMatchCount = $hasBoost ? 0 : $subscriptionService->getWeeklyMatchCount($group);

        return Inertia::render('Groups/Show', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'slug' => $group->slug,
                'timezone' => $group->timezone,
                'invite_code' => $group->invite_code,
                'created_at' => $group->created_at,
            ],
            'members' => $members,
            'userRole' => $userRole->value,
            'leaderboardPreview' => $leaderboardPreview,
            'myStats' => $myStats,
            'hasBoost' => $hasBoost,
            'weeklyMatchCount' => $weeklyMatchCount,
        ]);
    }

    public function edit(Request $request, Group $group): Response
    {
        $this->authorize('update', $group);

        $members = $group->members()
            ->select('users.id', 'users.name')
            ->get()
            ->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'role' => $member->pivot->role,
            ]);

        $userRole = $group->getMemberRole($request->user());

        return Inertia::render('Groups/Edit', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'slug' => $group->slug,
                'timezone' => $group->timezone,
            ],
            'members' => $members,
            'userRole' => $userRole->value,
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $group->update($request->validated());

        return Redirect::route('groups.edit', $group)->with('status', 'Group updated.');
    }

    public function destroy(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('delete', $group);

        $group->delete();

        return Redirect::route('groups.index');
    }
}
