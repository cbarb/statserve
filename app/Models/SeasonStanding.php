<?php

namespace App\Models;

use App\Enums\RewardTier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeasonStanding extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_id',
        'group_id',
        'user_id',
        'rating_start',
        'rating_end',
        'wins',
        'losses',
        'final_rank',
        'reward_tier',
    ];

    protected function casts(): array
    {
        return [
            'reward_tier' => RewardTier::class,
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
