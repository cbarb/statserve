<?php

namespace App\Enums;

enum XpSourceType: string
{
    case Match = 'match';
    case Challenge = 'challenge';
    case Badge = 'badge';
    case Bonus = 'bonus';
}
