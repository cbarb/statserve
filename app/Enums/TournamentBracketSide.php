<?php

namespace App\Enums;

enum TournamentBracketSide: string
{
    case Winners = 'winners';
    case Losers = 'losers';
    case Finals = 'finals';
}
