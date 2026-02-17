<?php

namespace App\Enums;

enum BracketType: string
{
    case SingleElimination = 'single_elimination';
    case DoubleElimination = 'double_elimination';
    case RoundRobin = 'round_robin';
}
