<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum TimeEntryRoundingType: string
{
    use LaravelEnumHelper;

    case Up = 'up';
    case Down = 'down';
    case Nearest = 'nearest';
}
