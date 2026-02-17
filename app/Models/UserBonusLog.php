<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBonusLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
