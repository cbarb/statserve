<?php

namespace App\Policies;

use App\Enums\GroupRole;
use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    public function view(User $user, Group $group): bool
    {
        return $group->isMember($user);
    }

    public function update(User $user, Group $group): bool
    {
        return $group->isAdminOrOwner($user);
    }

    public function delete(User $user, Group $group): bool
    {
        return $group->isOwner($user);
    }

    public function manageMembers(User $user, Group $group): bool
    {
        return $group->isAdminOrOwner($user);
    }

    public function transferOwnership(User $user, Group $group): bool
    {
        return $group->isOwner($user);
    }
}
