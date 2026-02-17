<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rivalry extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'user_1_id',
        'user_2_id',
        'user_1_wins',
        'user_2_wins',
        'total_matches',
        'last_match_at',
        'is_active',
        'detected_at',
    ];

    protected function casts(): array
    {
        return [
            'last_match_at' => 'datetime',
            'is_active' => 'boolean',
            'detected_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInvolvingUser($query, int $userId)
    {
        return $query->where(fn ($q) => $q->where('user_1_id', $userId)->orWhere('user_2_id', $userId));
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_1_id');
    }

    public function user2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_2_id');
    }
}
