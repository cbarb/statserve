<?php

namespace App\Http\Controllers;

use App\Enums\GroupRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class GroupMemberController extends Controller
{
    public function destroy(Request $request, Group $group, User $user): RedirectResponse
    {
        $this->authorize('manageMembers', $group);

        // Can't remove the owner
        if ($group->isOwner($user)) {
            abort(403, 'Cannot remove the group owner.');
        }

        // Admins can't remove other admins (only owner can)
        if ($group->getMemberRole($user) === GroupRole::Admin && !$group->isOwner($request->user())) {
            abort(403, 'Only the owner can remove admins.');
        }

        $group->members()->detach($user->id);

        return Redirect::route('groups.show', $group);
    }

    public function promote(Request $request, Group $group, User $user): RedirectResponse
    {
        $this->authorize('transferOwnership', $group); // Only owner can promote/demote

        if ($group->isOwner($user)) {
            abort(403, 'Cannot change the owner role this way.');
        }

        $currentRole = $group->getMemberRole($user);
        $newRole = $currentRole === GroupRole::Admin ? GroupRole::Member : GroupRole::Admin;

        $group->members()->updateExistingPivot($user->id, ['role' => $newRole->value]);

        return Redirect::route('groups.show', $group);
    }

    public function leave(Request $request, Group $group): RedirectResponse
    {
        $user = $request->user();

        if (!$group->isMember($user)) {
            abort(403);
        }

        if ($group->isOwner($user)) {
            $successor = $group->getLongestTenuredMember();

            if ($successor) {
                $group->transferOwnership($successor);
                $group->members()->detach($user->id);
            } else {
                // Last member — delete the group
                $group->delete();

                return Redirect::route('groups.index');
            }
        } else {
            $group->members()->detach($user->id);
        }

        return Redirect::route('groups.index');
    }

    public function transfer(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('transferOwnership', $group);

        $request->validate([
            'new_owner_id' => ['required', 'exists:users,id'],
        ]);

        $newOwner = User::findOrFail($request->new_owner_id);

        if (!$group->isMember($newOwner)) {
            abort(422, 'User must be a member of the group.');
        }

        $group->transferOwnership($newOwner);

        return Redirect::route('groups.show', $group);
    }
}
