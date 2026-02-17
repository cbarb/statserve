<?php

namespace App\Enums;

enum ChallengeStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Claimed = 'claimed';
    case Expired = 'expired';
}
