<?php

namespace App\Enums;

enum BonusLogSourceType: string
{
    case ChallengeReward = 'challenge_reward';
    case MatchSpent = 'match_spent';
}
