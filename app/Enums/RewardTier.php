<?php

namespace App\Enums;

enum RewardTier: string
{
    case Gold = 'gold';
    case Silver = 'silver';
    case Bronze = 'bronze';
    case None = 'none';
}
