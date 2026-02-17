<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_level',
        'total_xp',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function xpForNextLevel(): int
    {
        $thresholds = [
            1 => 100,
            2 => 200,
            3 => 350,
            4 => 500,
            5 => 700,
            6 => 1000,
            7 => 1400,
            8 => 2000,
            9 => 3000,
        ];

        if ($this->current_level >= 10) {
            return 3000 + (($this->current_level - 9) * 500);
        }

        return $thresholds[$this->current_level] ?? 100;
    }
}
