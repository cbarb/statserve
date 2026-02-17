<?php

namespace App\Models;

use App\Enums\SeasonStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => SeasonStatus::class,
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function scopeCurrent($query)
    {
        return $query->where('status', SeasonStatus::Active);
    }

    public function standings(): HasMany
    {
        return $this->hasMany(SeasonStanding::class);
    }
}
