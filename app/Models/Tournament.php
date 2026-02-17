<?php

namespace App\Models;

use App\Enums\BracketType;
use App\Enums\MatchFormat;
use App\Enums\TournamentStatus;
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
        'format',
        'bracket_type',
        'max_players',
        'min_rating',
        'max_rating',
        'status',
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
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'starts_at' => 'datetime',
        ];
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
