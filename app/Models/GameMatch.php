<?php

namespace App\Models;

use App\Enums\MatchFormat;
use App\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'group_id',
        'session_id',
        'tournament_round_id',
        'format',
        'status',
        'team_1_score',
        'team_2_score',
        'winning_team',
        'logged_by',
        'played_at',
    ];

    protected function casts(): array
    {
        return [
            'format' => MatchFormat::class,
            'status' => MatchStatus::class,
            'played_at' => 'datetime',
        ];
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', MatchStatus::Completed);
    }

    public function scopeInGroup($query, int $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    public function scopePlayedBetween($query, $from, $to)
    {
        return $query->whereBetween('played_at', [$from, $to]);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'session_id');
    }

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function players(): HasMany
    {
        return $this->hasMany(MatchPlayer::class, 'match_id');
    }

    public function tournamentRound(): BelongsTo
    {
        return $this->belongsTo(TournamentRound::class);
    }
}
