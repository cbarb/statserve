<?php

namespace App\Enums;

enum ChallengeType: string
{
    case Daily = 'daily';
    case WeeklyXp = 'weekly_xp';
    case WeeklyBonusLog = 'weekly_bonus_log';
}
