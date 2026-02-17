<?php

namespace App\Enums;

enum BoostStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
