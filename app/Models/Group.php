<?php

namespace App\Models;

use App\Enums\GroupRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'created_by',
        'invite_code',
        'timezone',
    ];

    protected static function booted(): void
    {
        static::creating(function (Group $group) {
            if (empty($group->slug)) {
                $group->slug = Str::slug($group->name) . '-' . Str::lower(Str::random(6));
            }
            if (empty($group->invite_code)) {
                $group->invite_code = Str::lower(Str::random(16));
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role', 'joined_at');
    }

    public function boosts(): HasMany
    {
        return $this->hasMany(GroupBoost::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(GroupMilestone::class);
    }

    public function rivalries(): HasMany
    {
        return $this->hasMany(Rivalry::class);
    }

    public function seasonStandings(): HasMany
    {
        return $this->hasMany(SeasonStanding::class);
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function isOwner(User $user): bool
    {
        return $this->getMemberRole($user) === GroupRole::Owner;
    }

    public function isAdminOrOwner(User $user): bool
    {
        $role = $this->getMemberRole($user);

        return $role === GroupRole::Owner || $role === GroupRole::Admin;
    }

    public function getMemberRole(User $user): ?GroupRole
    {
        $pivot = $this->members()->where('user_id', $user->id)->first()?->pivot;

        return $pivot ? GroupRole::from($pivot->role) : null;
    }

    public function regenerateInviteCode(): void
    {
        $this->update(['invite_code' => Str::lower(Str::random(16))]);
    }

    public function transferOwnership(User $newOwner): void
    {
        DB::transaction(function () use ($newOwner) {
            // Demote current owner to admin
            $this->members()
                ->wherePivot('role', GroupRole::Owner->value)
                ->each(function (User $user) {
                    $this->members()->updateExistingPivot($user->id, ['role' => GroupRole::Admin->value]);
                });

            // Promote new owner
            $this->members()->updateExistingPivot($newOwner->id, ['role' => GroupRole::Owner->value]);

            $this->update(['created_by' => $newOwner->id]);
        });
    }

    public function getLongestTenuredMember(): ?User
    {
        return $this->members()
            ->wherePivotNotIn('role', [GroupRole::Owner->value])
            ->orderByPivot('joined_at', 'asc')
            ->first();
    }
}
