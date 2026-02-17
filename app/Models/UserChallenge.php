<?php

namespace App\Models;

use App\Enums\ChallengeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserChallenge extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'challenge_id',
        'assigned_at',
        'expires_at',
        'progress',
        'target',
        'status',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ChallengeStatus::class,
            'assigned_at' => 'datetime',
            'expires_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', ChallengeStatus::Active);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())->where('status', ChallengeStatus::Active);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }
}
