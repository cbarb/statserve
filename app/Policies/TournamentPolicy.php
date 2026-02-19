<?php

namespace App\Policies;

use App\Enums\TournamentStatus;
use App\Models\Tournament;
use App\Models\User;
use App\Services\SubscriptionService;

class TournamentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tournament $tournament): bool
    {
        if ($tournament->is_public) {
            return true;
        }

        if ($tournament->group_id) {
            return $tournament->group->isMember($user);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return app(SubscriptionService::class)->isProSubscriber($user);
    }

    public function update(User $user, Tournament $tournament): bool
    {
        return $user->id === $tournament->created_by;
    }

    public function delete(User $user, Tournament $tournament): bool
    {
        return $user->id === $tournament->created_by
            && $tournament->status === TournamentStatus::Registration;
    }

    public function start(User $user, Tournament $tournament): bool
    {
        return $user->id === $tournament->created_by
            && $tournament->status === TournamentStatus::Registration;
    }

    public function setWinner(User $user, Tournament $tournament): bool
    {
        return $user->id === $tournament->created_by
            && $tournament->status === TournamentStatus::InProgress;
    }

    public function join(User $user, Tournament $tournament): bool
    {
        if ($tournament->is_public) {
            return true;
        }

        if ($tournament->group_id) {
            return $tournament->group->isMember($user);
        }

        return false;
    }
}
