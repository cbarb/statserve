<?php

namespace App\Enums;

enum MatchFormat: string
{
    // Legacy formats (used by game sessions)
    case Singles = 'singles';
    case Doubles = 'doubles';

    // Tournament-specific formats
    case MensSingles = 'mens_singles';
    case WomensSingles = 'womens_singles';
    case OpenSingles = 'open_singles';
    case MensDoubles = 'mens_doubles';
    case WomensDoubles = 'womens_doubles';
    case MixedDoubles = 'mixed_doubles';
    case OpenDoubles = 'open_doubles';

    public function isDoubles(): bool
    {
        return match ($this) {
            self::Doubles,
            self::MensDoubles,
            self::WomensDoubles,
            self::MixedDoubles,
            self::OpenDoubles => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Singles => 'Singles',
            self::Doubles => 'Doubles',
            self::MensSingles => "Men's Singles",
            self::WomensSingles => "Women's Singles",
            self::OpenSingles => 'Open Singles',
            self::MensDoubles => "Men's Doubles",
            self::WomensDoubles => "Women's Doubles",
            self::MixedDoubles => 'Mixed Doubles',
            self::OpenDoubles => 'Open Doubles',
        };
    }

    public static function tournamentFormats(): array
    {
        return [
            self::MensSingles,
            self::WomensSingles,
            self::OpenSingles,
            self::MensDoubles,
            self::WomensDoubles,
            self::MixedDoubles,
            self::OpenDoubles,
        ];
    }
}
