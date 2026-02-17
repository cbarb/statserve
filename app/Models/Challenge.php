<?php

namespace App\Models;

use App\Enums\ChallengeDifficulty;
use App\Enums\ChallengeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Challenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'type',
        'difficulty',
        'criteria_type',
        'criteria_value',
        'xp_reward',
        'bonus_logs_reward',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => ChallengeType::class,
            'difficulty' => ChallengeDifficulty::class,
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, ChallengeType $type)
    {
        return $query->where('type', $type);
    }

    public function userChallenges(): HasMany
    {
        return $this->hasMany(UserChallenge::class);
    }
}
