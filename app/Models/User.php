<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Billable, HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
        'timezone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)->withPivot('role', 'joined_at');
    }

    public function ownedGroups(): HasMany
    {
        return $this->hasMany(Group::class, 'created_by');
    }

    public function startedSessions(): HasMany
    {
        return $this->hasMany(GameSession::class, 'started_by');
    }

    public function loggedMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'logged_by');
    }

    public function matchPlayers(): HasMany
    {
        return $this->hasMany(MatchPlayer::class);
    }

    public function xpEvents(): HasMany
    {
        return $this->hasMany(UserXpEvent::class);
    }

    public function level(): HasOne
    {
        return $this->hasOne(UserLevel::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')->withPivot('earned_at', 'is_pinned');
    }

    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(UserChallenge::class);
    }

    public function bonusLogBalance(): HasOne
    {
        return $this->hasOne(UserBonusLog::class);
    }

    public function bonusLogTransactions(): HasMany
    {
        return $this->hasMany(UserBonusLogTransaction::class);
    }

    public function seasonStandings(): HasMany
    {
        return $this->hasMany(SeasonStanding::class);
    }

    public function groupBoosts(): HasMany
    {
        return $this->hasMany(GroupBoost::class);
    }
}
