<?php

namespace App\Enums;

enum BadgeCategory: string
{
    case Wins = 'wins';
    case Volume = 'volume';
    case Social = 'social';
    case Partnership = 'partnership';
    case Secret = 'secret';
}
