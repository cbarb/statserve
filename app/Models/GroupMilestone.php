<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'milestone_slug',
        'tier_reached',
        'reached_at',
        'progress',
        'target',
    ];

    protected function casts(): array
    {
        return [
            'reached_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
