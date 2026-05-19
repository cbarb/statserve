<?php

namespace App\Http\Controllers;

use App\Enums\MatchStatus;
use App\Models\GameMatch;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupMatchController extends Controller
{
    public function index(Request $request, Group $group): Response
    {
        $this->authorize('view', $group);

        $matches = GameMatch::with(['players.user', 'loggedBy'])
            ->where('group_id', $group->id)
            ->where('status', MatchStatus::Completed)
            ->orderByDesc('played_at')
            ->paginate(25)
            ->through(fn ($match) => $this->formatMatch($match));

        return Inertia::render('Groups/Matches', [
            'group' => ['id' => $group->id, 'name' => $group->name, 'slug' => $group->slug],
            'matches' => $matches,
            'userRole' => $group->getMemberRole($request->user())?->value,
        ]);
    }

    public function update(Request $request, Group $group, GameMatch $match): RedirectResponse
    {
        $this->authorize('update', $group);
        abort_if($match->group_id !== $group->id, 404);

        $data = $request->validate([
            'team_1_score' => 'required|integer|min:0|max:99',
            'team_2_score' => 'required|integer|min:0|max:99',
        ]);

        if ($data['team_1_score'] === $data['team_2_score']) {
            return back()->withErrors(['team_2_score' => 'Scores cannot be equal.']);
        }

        $match->update([
            'team_1_score' => $data['team_1_score'],
            'team_2_score' => $data['team_2_score'],
            'winning_team' => $data['team_1_score'] > $data['team_2_score'] ? 1 : 2,
        ]);

        return back()->with('success', 'Match updated.');
    }

    public function destroy(Group $group, GameMatch $match): RedirectResponse
    {
        $this->authorize('update', $group);
        abort_if($match->group_id !== $group->id, 404);

        $match->players()->delete();
        $match->delete();

        return back()->with('success', 'Match deleted.');
    }

    private function formatMatch(GameMatch $match): array
    {
        $byTeam = $match->players->groupBy('team');

        return [
            'id' => $match->id,
            'format' => $match->format->label(),
            'team_1_score' => $match->team_1_score,
            'team_2_score' => $match->team_2_score,
            'winning_team' => $match->winning_team,
            'played_at' => $match->played_at->toIso8601String(),
            'logged_by_name' => $match->loggedBy?->name,
            'team_1' => ($byTeam[1] ?? collect())->map(fn ($p) => ['id' => $p->user_id, 'name' => $p->user->name])->values(),
            'team_2' => ($byTeam[2] ?? collect())->map(fn ($p) => ['id' => $p->user_id, 'name' => $p->user->name])->values(),
        ];
    }
}
