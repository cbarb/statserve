<?php

namespace App\Models;

use App\Enums\BonusLogSourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBonusLogTransaction extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'amount',
        'source_type',
        'source_id',
        'balance_after',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => BonusLogSourceType::class,
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
