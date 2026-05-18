<?php

namespace App\Http\Controllers;

use App\Enums\GroupRole;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class GroupInviteController extends Controller
{
    public function show(Request $request, string $code): Response
    {
        $group = Group::where('invite_code', $code)->firstOrFail();

        $isMember = $request->user() ? $group->isMember($request->user()) : false;

        if (! $request->user()) {
            session()->put('url.intended', $request->url());
        }

        return Inertia::render('Groups/Join', [
            'group' => [
                'name' => $group->name,
                'slug' => $group->slug,
                'members_count' => $group->members()->count(),
            ],
            'code' => $code,
            'isMember' => $isMember,
        ]);
    }

    public function join(Request $request, string $code): RedirectResponse
    {
        $group = Group::where('invite_code', $code)->firstOrFail();

        if ($group->isMember($request->user())) {
            return Redirect::route('groups.show', $group);
        }

        $group->members()->attach($request->user()->id, [
            'role' => GroupRole::Member->value,
            'joined_at' => now(),
        ]);

        return Redirect::route('groups.show', $group);
    }

    public function regenerate(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group);

        $group->regenerateInviteCode();

        return Redirect::route('groups.show', $group);
    }
}
