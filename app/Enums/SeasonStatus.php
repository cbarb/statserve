<?php

namespace App\Enums;

enum SeasonStatus: string
{
    case Upcoming = 'upcoming';
    case Active = 'active';
    case Completed = 'completed';
}
