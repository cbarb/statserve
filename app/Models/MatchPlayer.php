<?php

namespace App\Models;

use App\Enums\PlayerPosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchPlayer extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'match_id',
        'user_id',
        'team',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => PlayerPosition::class,
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
