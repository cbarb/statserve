<?php

namespace App\Enums;

enum TournamentStatus: string
{
    case Registration = 'registration';
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
