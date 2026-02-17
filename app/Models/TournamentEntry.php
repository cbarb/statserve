<?php

namespace App\Models;

use App\Enums\TournamentEntryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'user_id',
        'partner_id',
        'seed',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => TournamentEntryStatus::class,
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }
}
