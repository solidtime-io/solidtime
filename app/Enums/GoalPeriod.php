<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum GoalPeriod: string
{
    use LaravelEnumHelper;

    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
}
