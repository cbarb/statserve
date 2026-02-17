<?php

namespace App\Models;

use App\Enums\BadgeCategory;
use App\Enums\BadgeTier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'category',
        'tier',
        'criteria_type',
        'criteria_value',
        'xp_reward',
        'icon',
        'is_secret',
    ];

    protected function casts(): array
    {
        return [
            'category' => BadgeCategory::class,
            'tier' => BadgeTier::class,
            'is_secret' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')->withPivot('earned_at', 'is_pinned');
    }

    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }
}
