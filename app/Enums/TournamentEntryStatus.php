<?php

namespace App\Enums;

enum TournamentEntryStatus: string
{
    case Registered = 'registered';
    case CheckedIn = 'checked_in';
    case Eliminated = 'eliminated';
    case Winner = 'winner';
}
