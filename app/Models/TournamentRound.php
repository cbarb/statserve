<?php

namespace App\Models;

use App\Enums\TournamentBracketSide;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentRound extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'round_number',
        'bracket',
        'match_id',
        'entry_1_id',
        'entry_2_id',
        'winner_entry_id',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'bracket' => TournamentBracketSide::class,
            'scheduled_at' => 'datetime',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function entry1(): BelongsTo
    {
        return $this->belongsTo(TournamentEntry::class, 'entry_1_id');
    }

    public function entry2(): BelongsTo
    {
        return $this->belongsTo(TournamentEntry::class, 'entry_2_id');
    }

    public function winnerEntry(): BelongsTo
    {
        return $this->belongsTo(TournamentEntry::class, 'winner_entry_id');
    }
}
