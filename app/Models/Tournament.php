<?php

namespace App\Models;

use App\Enums\BracketType;
use App\Enums\MatchFormat;
use App\Enums\TournamentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'created_by',
        'name',
        'description',
        'format',
        'bracket_type',
        'max_players',
        'min_rating',
        'max_rating',
        'status',
        'address',
        'city',
        'state',
        'latitude',
        'longitude',
        'is_public',
        'registration_opens_at',
        'registration_closes_at',
        'starts_at',
    ];

    protected function casts(): array
    {
        return [
            'format' => MatchFormat::class,
            'bracket_type' => BracketType::class,
            'status' => TournamentStatus::class,
            'is_public' => 'boolean',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'starts_at' => 'datetime',
        ];
    }

    public function scopeNearby(Builder $query, float $lat, float $lng, float $radiusMiles = 50): Builder
    {
        $haversine = "(3959 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";

        return $query
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("{$query->getQuery()->from}.*, {$haversine} as distance", [$lat, $lng, $lat])
            ->whereRaw("{$haversine} <= ?", [$lat, $lng, $lat, $radiusMiles])
            ->orderByRaw("{$haversine}", [$lat, $lng, $lat]);
    }

    public function scopePublicOpen(Builder $query): Builder
    {
        return $query->where('is_public', true)
            ->where('status', TournamentStatus::Registration);
    }

    public function isRegistered(User $user): bool
    {
        return $this->entries()->where('user_id', $user->id)->exists();
    }

    public function registeredCount(): int
    {
        $count = $this->entries()->count();

        return $this->format->isDoubles() ? $count * 2 : $count;
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TournamentEntry::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(TournamentRound::class);
    }
}
