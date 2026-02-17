<?php

namespace App\Models;

use App\Enums\MatchFormat;
use App\Enums\SessionStatus;
use App\Enums\TeamMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    use HasFactory;

    protected $table = 'game_sessions';

    protected $fillable = [
        'group_id',
        'started_by',
        'format',
        'team_mode',
        'status',
        'player_ids',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'format' => MatchFormat::class,
            'team_mode' => TeamMode::class,
            'status' => SessionStatus::class,
            'player_ids' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'session_id');
    }
}
